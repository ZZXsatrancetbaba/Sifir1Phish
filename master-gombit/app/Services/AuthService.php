<?php

declare(strict_types=1);

namespace MG\Services;

use MG\Core\Config;
use MG\Exceptions\AuthException;
use MG\Models\Role;
use MG\Models\User;
use MG\Security\AuditLogger;
use MG\Security\PasswordHasher;
use MG\Security\RateLimiter;
use MG\Security\SessionGuard;
use PDO;

/**
 * AuthService - güvenli login akışı:
 *  1) Rate limit (kullanıcı + IP bucket)
 *  2) Prepared statement ile kullanıcı yükleme
 *  3) Sabit-zamanlı parola doğrulama; kullanıcı YOKSA da dummy-hash ile
 *     timing eşitleme (user enumeration azaltma)
 *  4) Kilit kontrolü, failed sayaç, Argon2id rehash yükseltmesi
 *  5) Session regenerate + audit log
 */
final class AuthService
{
    /** Sentetik "dummy" hash: kullanıcı yoksa bile benzer süre harcanır. */
    private const DUMMY_HASH = '$2y$12$C6UzMDM.H6dfI/f/IKcEeO7Q8vD8uZ0uR8r7t1Yx1s9m0n2p3q4r5';

    public function __construct(
        private readonly PDO $pdo,
        private readonly SessionGuard $session,
        private readonly RateLimiter $limiter,
        private readonly AuditLogger $audit,
    ) {}

    public function login(string $username, string $password, string $ip, string $ua): User
    {
        $maxAttempts = (int)Config::get('env.rate_limit_login_attempts', 5);
        $window = (int)Config::get('env.rate_limit_login_window', 600);

        $ipBucket = 'login:ip:' . AuditLogger::pepperedHash($ip);
        $userBucket = 'login:user:' . $username;
        if ($this->limiter->exceeded($userBucket, $maxAttempts, $window)
            || $this->limiter->exceeded($ipBucket, $maxAttempts * 4, $window)) {
            $this->audit->log(null, 'login_blocked', $ip, $ua, $username, 'blocked');
            throw AuthException::locked();
        }

        $user = User::findByUsername($this->pdo, $username);

        // Kullanıcı yoksa: dummy verify ile timing düzleştir, jenerik hata ver
        if ($user === null) {
            PasswordHasher::verify($password, self::DUMMY_HASH);
            $this->audit->log(null, 'login_fail', $ip, $ua, $username, 'failure', ['reason' => 'unknown_user']);
            throw AuthException::invalidCredentials();
        }

        // Kilit kontrolü (DB seviyesi)
        $row = $this->pdo->prepare('SELECT locked_until FROM users WHERE id = ? LIMIT 1');
        $row->execute([$user->id]);
        if (User::isLocked($row->fetch())) {
            $this->audit->log($user->id, 'login_locked', $ip, $ua, $username, 'blocked');
            throw AuthException::locked();
        }

        if (!$user->isActive || !PasswordHasher::verify($password, $user->passwordHash)) {
            User::registerFailedLogin($this->pdo, $user->id);
            $this->audit->log($user->id, 'login_fail', $ip, $ua, $username, 'failure', ['reason' => 'bad_password']);
            throw AuthException::invalidCredentials();   // asla "parola yanlış/değiş" ayrımı yok
        }

        // Başarılı: eski hash ise Argon2id'ye yükselt (seed bcrypt -> runtime argon2id)
        $newHash = '';
        if (PasswordHasher::needsRehash($user->passwordHash)) {
            $newHash = PasswordHasher::hash($password);
        }
        User::registerSuccessfulLogin($this->pdo, $user->id, $newHash);
        $this->limiter->clear($userBucket);

        $this->session->loginUser($user->id, $user->roleName);   // regenerate burada çağrılır
        $this->audit->log($user->id, 'login_ok', $ip, $ua, $username, 'success', ['role' => $user->roleName]);

        return $user;
    }

    public function logout(?int $userId, string $ip, string $ua): void
    {
        $this->audit->log($userId, 'logout', $ip, $ua, '', 'success');
        $this->session->logout();
    }

    /** Kayıt: yalnızca student rolü verilir (privilege escalation önleme - role ID client'tan ALINMAZ). */
    public function register(string $username, ?string $email, string $password): User
    {
        if (!PasswordHasher::isStrongEnough($password)) {
            throw new \MG\Exceptions\ValidationException(['password' => 'Parola en az 10 karakter ve 3 karakter sınıfı içermeli.']);
        }
        $role = Role::findByName($this->pdo, Role::STUDENT);
        if ($role === null) {
            throw new AuthException('Sistem hatası: rol tanımsız.', 500);
        }
        $id = User::create($this->pdo, $username, $email, $password, (int)$role['id']);
        $user = User::findById($this->pdo, $id);
        if ($user === null) {
            throw new AuthException('Kayıt başarısız.', 500);
        }
        return $user;
    }
}
