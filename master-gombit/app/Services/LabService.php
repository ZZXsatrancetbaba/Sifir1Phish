<?php

declare(strict_types=1);

namespace MG\Services;

use MG\Core\Config;
use MG\Exceptions\AppException;
use MG\Models\User;
use MG\Security\AuditLogger;
use PDO;

/**
 * LabService - lab içeriği sunumu + nesne-düzeyi yetki (IDOR önleme) örnekleri.
 *  - getLabOrFail: slug whitelist + feature flag filtresi
 *  - notes: her okuma/yazma user_id filtreli (owner check SQL içinde)
 */
final class LabService
{
    public function __construct(private readonly PDO $pdo, private readonly AuditLogger $audit) {}

    /** @return array<string,mixed> */
    public function saveNote(User $user, int $labId, string $content): array
    {
        // OBJECT-LEVEL AUTHORIZATION: not yalnızca kendi lab notudur;
        // INSERT ... user_id = oturumdaki kullanıcı (client'tan gelmez).
        $stmt = $this->pdo->prepare(
            'INSERT INTO notes (user_id, lab_module_id, content_safe) VALUES (?, ?, ?)'
        );
        $stmt->execute([$user->id, $labId, $content]);
        return ['id' => (int)$this->pdo->lastInsertId()];
    }

    /** @return array<int,array<string,mixed>> owner-filtered liste (IDOR yok) */
    public function listNotes(User $user, int $labId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, lab_module_id, content_safe, created_at
               FROM notes WHERE user_id = ? AND lab_module_id = ? ORDER BY id DESC LIMIT 50'
        );
        $stmt->execute([$user->id, $labId]);
        return $stmt->fetchAll();
    }

    /** Dosya yükleme servisi (Lab 07 güvenli implementasyon). */
    /** @param array{name:string,tmp_name:string,size:int,error:int,type:string} $file */
    public function storeUpload(User $user, array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new AppException('Yükleme alınamadı.', 422);
        }
        $maxBytes = (int)Config::get('env.upload_max_bytes', 2097152);
        if ($file['size'] <= 0 || $file['size'] > $maxBytes) {
            throw new AppException('Dosya boyutu izin verilen sınırın dışında.', 422);
        }

        // 1) Uzantı WHITELIST (blacklist değil!)
        $allowed = explode(',', strtolower((string)Config::get('env.upload_allowed_ext', 'png,jpg,gif,txt,pdf,md')));
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION) ?? '');
        if (!in_array($ext, $allowed, true)) {
            throw new AppException('Dosya türü izin verilmiyor.', 422);
        }

        // 2) İÇERİK doğrulama: magic-byte kontrolü (mime spoofing önleme)
        $tmp = (string)$file['tmp_name'];
        if (!is_uploaded_file($tmp)) {
            throw new AppException('Geçersiz yükleme kaynağı.', 422);
        }
        $finfo = new \finfo(FILE_MIME_ONLY);
        $detected = (string)$finfo->file($tmp);
        $okMimes = [
            'image/png' => ['png'], 'image/jpeg' => ['jpg'], 'image/gif' => ['gif'],
            'text/plain' => ['txt','md'], 'application/pdf' => ['pdf'],
        ];
        $extMatches = $okMimes[$detected] ?? [];
        if ($extMatches === [] || !in_array($ext, $extMatches, true)) {
            throw new AppException('Dosya içeriği ile türü uyuşmuyor.', 422);
        }

        // 3) Orijinal adı sanitize et (yalnız kayıt amaçlı; yolda kullanılmaz)
        $safeName = preg_replace('/[^\w.\-]+/u', '_', (string)$file['name']) ?? 'file';
        $safeName = substr((string)preg_replace('/\.{2,}/', '.', $safeName), 0, 200);

        // 4) Rastgele ad + webroot DIŞI depolama (storage/) - path traversal imkânsız
        $rand = bin2hex(random_bytes(16));
        $storedName = sprintf('%s.%s', $rand, $ext);   // ext zaten whitelist'ten
        $dir = dirname(__DIR__, 2) . '/storage/uploads';
        if (!is_dir($dir)) { mkdir($dir, 0750, true); }
        $dest = $dir . '/' . $storedName;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new AppException('Dosya saklanamadı.', 500);
        }
        chmod($dest, 0640);

        // 5) Bütünlük checksum + DB kaydı (ham içerik DB'de değil)
        $checksum = hash_file('sha256', $dest);
        $stmt = $this->pdo->prepare(
            'INSERT INTO files (user_id, original_name_sanitized, stored_path_random, mime_type, size_bytes, checksum_sha256)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$user->id, $safeName, $storedName, $detected, (int)$file['size'], $checksum]);
        $id = (int)$this->pdo->lastInsertId();

        $this->audit->log($user->id, 'upload', '', '', 'file:' . $id, 'success', ['bytes' => (int)$file['size'], 'mime' => $detected]);

        // Virus scan hook placeholder: gerçek projede ClamAV entegrasyonu buraya (docs/RUNBOOK)
        return ['id' => $id, 'name' => $safeName, 'mime' => $detected];
    }

    /** Kontrollü indirme: owner check SQL seviyesinde (IDOR kapalı). */
    public function getOwnFile(User $user, int $fileId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM files WHERE id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$fileId, $user->id]);
        $row = $stmt->fetch();
        return $row === false ? null : (array)$row;
    }
}
