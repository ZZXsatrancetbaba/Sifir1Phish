<?php

declare(strict_types=1);

namespace MG\Models;

use MG\Core\Config;
use MG\Security\PasswordHasher;
use PDO;

/**
 * User - veri modeli (aktif record benzeri, TÜM sorgular prepared statement).
 * IDOR notu: findById tek başına yetki DEĞİLDİR; controller nesne-düzeyi
 * sahiplik/rol kontrolü yapmak zorundadır.
 */
final class User
{
    /** @param array<string,mixed> $permissions */
    public function __construct(
        public readonly int $id,
        public readonly string $username,
        public readonly ?string $email,
        public readonly string $passwordHash,
        public readonly int $roleId,
        public readonly string $roleName,
        public readonly bool $isActive,
        public readonly array $permissions = [],
    ) {}

    /** @return self|null */
    public static function findByUsername(PDO $pdo, string $username): ?self
    {
        $stmt = $pdo->prepare(
            'SELECT u.*, r.name AS role_name
               FROM users u JOIN roles r ON r.id = u.role_id
              WHERE u.username = ? LIMIT 1'
        );
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row === false ? null : self::hydrate($pdo, $row);
    }

    public static function findById(PDO $pdo, int $id): ?self
    {
        $stmt = $pdo->prepare(
            'SELECT u.*, r.name AS role_name
               FROM users u JOIN roles r ON r.id = u.role_id
              WHERE u.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : self::hydrate($pdo, $row);
    }

    /** @param array<string,mixed> $row */
    private static function hydrate(PDO $pdo, array $row): self
    {
        $uid = (int)$row['id'];
        $permStmt = $pdo->prepare(
            'SELECT p.name FROM permissions p
              JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = ?'
        );
        $permStmt->execute([(int)$row['role_id']]);
        $perms = $permStmt->fetchAll(PDO::FETCH_COLUMN);

        return new self(
            id: $uid,
            username: (string)$row['username'],
            email: $row['email'] !== null ? (string)$row['email'] : null,
            passwordHash: (string)$row['password_hash'],
            roleId: (int)$row['role_id'],
            roleName: (string)$row['role_name'],
            isActive: (bool)(int)$row['is_active'],
            permissions: is_array($perms) ? $perms : [],
        );
    }

    public function hasPermission(string $name): bool
    {
        return in_array($name, $this->permissions, true);
    }

    public function isAdmin(): bool
    {
        return $this->roleName === 'admin';
    }

    /** Başarısız login sayacı + hesap kilidi (exponential değil sabit süre; eğitim notu). */
    public static function registerFailedLogin(PDO $pdo, int $userId): void
    {
        $window = (int)Config::get('env.rate_limit_login_window', 600);
        $stmt = $pdo->prepare(
            'UPDATE users
                SET failed_login_count = LEAST(failed_login_count + 1, 255),
                    locked_until = CASE WHEN failed_login_count + 1 >= 5
                                        THEN DATE_ADD(NOW(), INTERVAL ? SECOND)
                                        ELSE locked_until END
              WHERE id = ?'
        );
        $stmt->execute([$window, $userId]);
    }

    public static function registerSuccessfulLogin(PDO $pdo, int $userId, string $newHashIfUpgraded): void
    {
        if ($newHashIfUpgraded !== '') {
            $up = $pdo->prepare('UPDATE users SET password_hash = ?, failed_login_count = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?');
            $up->execute([$newHashIfUpgraded, $userId]);
        } else {
            $ok = $pdo->prepare('UPDATE users SET failed_login_count = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?');
            $ok->execute([$userId]);
        }
    }

    public static function create(PDO $pdo, string $username, ?string $email, string $plainPassword, int $roleId): int
    {
        // Parola asla plain text gitmez; Argon2id burada üretilir.
        $hash = PasswordHasher::hash($plainPassword);
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role_id) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$username, $email, $hash, $roleId]);
        return (int)$pdo->lastInsertId();
    }

    public static function isLocked(array|false $rowWithLock): bool
    {
        if ($rowWithLock === false) { return false; }
        $lu = $rowWithLock['locked_until'] ?? null;
        return $lu !== null && strtotime((string)$lu) > time();
    }
}
