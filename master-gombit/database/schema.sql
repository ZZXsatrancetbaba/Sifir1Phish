-- =============================================================================
-- Master Gombit - Veritabanı Şeması (MySQL 8 / MariaDB 11 uyumlu)
-- Eğitim amaçlı, normalize, FK'li, utf8mb4.
-- GÜVENLİK NOTLARI:
--  * password_hash: Argon2id çıktıları saklanır (plain text YOK).
--  * flags: yalnızca SHA-256 HASH saklanır; ham bayrak şemada durmaz.
--  * submissions: cevap hash'i saklanır (yanlış bilgi sızıntısını azaltır).
--  * audit_logs: IP/UA hash'lenir (pepper ile), ham veri loglanmaz.
--  * files: rastgele depolama adı; orijinal ad sanitize edilip saklanır.
--  * Uygulama DB kullanıcısı en az yetki ile tanımlanmalı (GRANT ayrı).
-- =============================================================================

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(32)  NOT NULL UNIQUE,       -- admin|instructor|student|guest
  description   VARCHAR(255) NOT NULL DEFAULT '',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(64)  NOT NULL UNIQUE,       -- lab.view|lab.submit|admin.users|...
  description   VARCHAR(255) NOT NULL DEFAULT '',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
  role_id       INT UNSIGNED NOT NULL,
  permission_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username           VARCHAR(64)  NOT NULL UNIQUE,
  email              VARCHAR(255) NULL UNIQUE,
  password_hash      VARCHAR(255) NOT NULL,           -- Argon2id
  role_id            INT UNSIGNED NOT NULL,
  is_active          TINYINT(1)   NOT NULL DEFAULT 1,
  failed_login_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until       DATETIME     NULL,
  last_login_at      DATETIME     NULL,
  mfa_secret_enc     VARBINARY(255) NULL,             -- MFA placeholder (şifreli yer tutucu)
  created_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id),
  INDEX idx_users_role (role_id),
  INDEX idx_users_locked (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS lab_modules (
  id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug                VARCHAR(64)  NOT NULL UNIQUE,
  title               VARCHAR(128) NOT NULL,
  description         TEXT         NOT NULL,
  owasp_category      VARCHAR(16)  NOT NULL,          -- A01..A10
  difficulty          ENUM('easy','medium','hard') NOT NULL DEFAULT 'easy',
  is_enabled          TINYINT(1)   NOT NULL DEFAULT 1,
  requires_feature_flag VARCHAR(64) NULL,             -- ör. FEATURE_INSECURE_DEMOS
  created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_lab_owasp (owasp_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS flags (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  lab_module_id  INT UNSIGNED NOT NULL,
  flag_hash      CHAR(64)     NOT NULL,               -- sha256(flag + per-flag salt yok; hash-only)
  hint_level     TINYINT UNSIGNED NOT NULL DEFAULT 0, -- 0=hintsız, 1..n kademeli ipucu
  is_sensitive   TINYINT(1)   NOT NULL DEFAULT 0,
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_flags_lab FOREIGN KEY (lab_module_id) REFERENCES lab_modules(id) ON DELETE CASCADE,
  UNIQUE KEY uq_flag (flag_hash, lab_module_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS submissions (
  id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id              BIGINT UNSIGNED NOT NULL,
  lab_module_id        INT UNSIGNED NOT NULL,
  submitted_answer_hash CHAR(64) NOT NULL,            -- ham cevap saklanmaz
  status               ENUM('correct','incorrect','pending') NOT NULL DEFAULT 'pending',
  score                SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_sub_lab  FOREIGN KEY (lab_module_id) REFERENCES lab_modules(id) ON DELETE CASCADE,
  INDEX idx_sub_user_lab (user_id, lab_module_id),
  INDEX idx_sub_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Badge sistemi (dashboard): kullanıcı x lab tamamlandı -> rozet
CREATE TABLE IF NOT EXISTS badges (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code       VARCHAR(32) NOT NULL UNIQUE,   -- sql_defender, xss_shield ...
  label      VARCHAR(64) NOT NULL,
  lab_module_id INT UNSIGNED NULL,
  CONSTRAINT fk_badge_lab FOREIGN KEY (lab_module_id) REFERENCES lab_modules(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_badges (
  user_id    BIGINT UNSIGNED NOT NULL,
  badge_id   INT UNSIGNED NOT NULL,
  awarded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, badge_id),
  CONSTRAINT fk_ub_user  FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ub_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id        BIGINT UNSIGNED NULL,
  event_type     VARCHAR(48)  NOT NULL,      -- login_ok|login_fail|logout|flag_submit|upload|admin_action|perm_change
  ip_hash        CHAR(64)     NOT NULL,      -- sha256(pepper+ip) -> anonim
  user_agent_hash CHAR(64)    NOT NULL,
  target         VARCHAR(128) NOT NULL DEFAULT '',
  result         ENUM('success','failure','blocked') NOT NULL,
  metadata_json  JSON         NULL,          -- secret İÇERMEZ; redakte edilir
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_audit_event (event_type, created_at),
  INDEX idx_audit_user (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notes (
  id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id          BIGINT UNSIGNED NOT NULL,
  lab_module_id    INT UNSIGNED NOT NULL,
  content_safe     MEDIUMTEXT     NOT NULL,   -- context-encode edilmiş/güvenli metin
  created_at       DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notes_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_notes_lab  FOREIGN KEY (lab_module_id) REFERENCES lab_modules(id) ON DELETE CASCADE,
  INDEX idx_notes_user_lab (user_id, lab_module_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS files (
  id                     BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id                BIGINT UNSIGNED NOT NULL,
  original_name_sanitized VARCHAR(255) NOT NULL,
  stored_path_random     VARCHAR(255) NOT NULL,   -- rastgele ad; webroot DIŞINDA
  mime_type              VARCHAR(128) NOT NULL,
  size_bytes             INT UNSIGNED NOT NULL,
  checksum_sha256        CHAR(64) NOT NULL,
  uploaded_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_files_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_files_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate limit sayaçları (basit, DB tabanlı; prod'da Redis önerilir - eğitim notu)
CREATE TABLE IF NOT EXISTS rate_limits (
  bucket_key  VARCHAR(128) NOT NULL PRIMARY KEY,   -- ör. login:user:alice
  hits        SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  window_start DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_rl_window (window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- EN AZ YETKİ ÖRNEĞİ (root ile ayrıca çalıştırılmalı; schema.sql içinde GRANT
-- uygulanmaz çünkü compose entrypoint mg_user'u zaten oluşturur):
--   CREATE USER 'mg_user'@'%' IDENTIFIED BY '<env>';
--   GRANT SELECT,INSERT,UPDATE,DELETE ON mg_lab.* TO 'mg_user'@'%';
--   -- DDL/GRANT/DROP YOK. Uygulama hesabı şema değiştiremez.
-- =============================================================================
