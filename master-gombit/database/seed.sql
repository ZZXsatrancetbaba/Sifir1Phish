-- =============================================================================
-- Master Gombit - SENTETİK SEED VERİSİ
-- Tüm hesaplar/parolalar/bayraklar eğitim amaçlıdır. Gerçek kullanıcı yok.
-- Parola hash'leri bcrypt(12) ile üretilmiştir; canlı ortamda Argon2id önerilir
-- (PasswordHasher sınıfı runtime'daArgon2id üretir).
--   student@local.test      / MgStudent2026!
--   instructor@local.test   / MgInstructor2026!
--   admin@local.test        / MgAdmin2026!
-- Bayraklar yalnızca SHA-256 HASH olarak saklanır.
-- =============================================================================

INSERT INTO roles (id, name, description) VALUES
 (1,'admin','Lab yöneticisi: tüm modüller, kullanıcı ve audit erişimi'),
 (2,'instructor','Eğitmen: lab içerikleri, puanlama, rapor dışa aktarma'),
 (3,'student','Öğrenci: lab görme/çözme, kendi not ve dosyaları'),
 (4,'guest','Misafir: yalnızca 01-environment önizleme')
ON DUPLICATE KEY UPDATE description=VALUES(description);

INSERT INTO permissions (id, name, description) VALUES
 (1,'lab.view','Lab modüllerini görüntüle'),
 (2,'lab.submit','Bayrak/cevap gönder'),
 (3,'note.own','Kendi notlarını yönet'),
 (4,'file.upload','Dosya yükle (lab 07)'),
 (5,'file.download.own','Kendi dosyalarını indir'),
 (6,'report.export','Rapor dışa aktar'),
 (7,'admin.users','Kullanıcıları yönet'),
 (8,'admin.audit','Audit log oku'),
 (9,'admin.flags','Bayrak tanımla'),
 (10,'demo.insecure','Zafiyetli eğitim demosunu gör (feature flag + uyarı)')
ON DUPLICATE KEY UPDATE description=VALUES(description);

INSERT INTO role_permissions (role_id, permission_id) VALUES
 (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),
 (2,1),(2,2),(2,3),(2,4),(2,5),(2,6),(2,9),
 (3,1),(3,2),(3,3),(3,4),(3,5),
 (4,1)
ON DUPLICATE KEY UPDATE role_id=VALUES(role_id);

INSERT INTO users (id, username, email, password_hash, role_id, is_active) VALUES
 (1,'student','student@local.test','$2a$12$PfKJ9DKXNnXWipYAzMnlpeyPnUax.0w73bmzMkyuhxprSbB53swKO',3,1),
 (2,'instructor','instructor@local.test','$2a$12$dj20B1XRwCRdhO28XecbReXSWOritFdMFS5.vjnedf0ctT604exsK',2,1),
 (3,'admin','admin@local.test','$2a$12$XEyVVd5dfR7GoQlwyh1UfuFQ6AfwVAjbSoiuapYSE5ZRkI.gFTO1.',1,1)
ON DUPLICATE KEY UPDATE username=VALUES(username);

INSERT INTO lab_modules (id, slug, title, description, owasp_category, difficulty, is_enabled, requires_feature_flag) VALUES
 (1,'environment','01 - Ortam & Threat Modeling','Lokal kurulum, Docker izolasyonu, kapsam belirleme, STRIDE tehdit modellemesi, varlık envanteri ve güven sınırları.','A05','easy',1,NULL),
 (2,'sql-awareness','02 - SQL Farkındalığı & Prepared Statements','SQL enjeksiyon kavramı, PDO prepared statements, dinamik sorgu whitelist, en az yetki, hata sızıntısı önleme, fix-it challenge.','A03','medium',1,NULL),
 (3,'xss-awareness','03 - XSS & Output Encoding','Stored/reflected/DOM XSS kavramları, bağlama duyarlı kodlama, CSP, sanitization vs encoding, güvenli şablonlama.','A03','medium',1,NULL),
 (4,'csrf-awareness','04 - CSRF Koruması','Synchronizer token, SameSite cookie, state-changing method koruması, token rotasyonu, AJAX CSRF header.','A01','medium',1,NULL),
 (5,'auth-session','05 - Kimlik Doğrulama & Oturum','Argon2id parola saklama, login rate limiting, oturum yenileme/zaman aşımı, güvenli remember-me, MFA placeholder.','A07','medium',1,NULL),
 (6,'authorization-idor','06 - Yetkilendirme & IDOR','RBAC, nesne düzeyi yetki kontrolü, yatay/dikey privilege sorunları, kaynak sahiplik kontrolü, güvenli API tasarımı.','A01','hard',1,NULL),
 (7,'file-upload','07 - Güvenli Dosya Yükleme','Uzantı whitelist, içerik doğrulama, rastgele dosya adı, webroot dışı depolama, kontrollü indirme.','A04','medium',1,NULL),
 (8,'logging-monitoring','08 - Loglama & İzleme','Audit log tasarımı, hassas veri redaksiyonu, anomali tespiti fikirleri, yanlış pozitif azaltma, retention.','A09','easy',1,NULL),
 (9,'secrets-dependencies','09 - Secret & Tedarik Zinciri','.env yönetimi, composer audit, lock file, bağımlılık incelemesi, SBOM, CI secret scanning.','A06','medium',1,NULL),
 (10,'reporting','10 - Raporlama & Düzeltme','Bulgu raporu şablonu, risk derecelendirme, OWASP eşleme, düzeltme ticket formatı, yönetici özeti.','A05','easy',1,NULL),
 (11,'insecure-compare','EK - Zafiyetli vs Güvenli Karşılaştırma Demo','FEATURE FLAG ARKASINDA: yalnızca savunma odaklı, pasif karşılaştırma anlatımı. Aktif sömürü arayüzü YOKTUR.','A03','easy',0,'FEATURE_INSECURE_DEMOS')
ON DUPLICATE KEY UPDATE title=VALUES(title);

INSERT INTO flags (lab_module_id, flag_hash, hint_level, is_sensitive) VALUES
 (2,'631648991a1bfba401bdfbcb09c9a4099f132c672b19db867c2b947f6d12fa54',0,0),
 (3,'7c4c362045e083d33b9411c0479e08943b288e78652dd4ee25c3f2450560d368',0,0),
 (4,'21bbd9685efffecf797cba477da265381dad88e8aa6fb18264de43d5bec9d7be',0,0),
 (5,'31f3769dc48fb10fa27416d46551754153b83d77141d4e2bbb739d606956797f',0,0),
 (6,'63e3b4193e1eac1ae93c1740fa01fe0ff3f719c13c16f397d94d9d65acd301f5',0,0),
 (7,'5b70bf7fd060c618c41a631d8af0bee9dca00402390186d74c5201788e78b406',0,0),
 (8,'129541f8b1471d37cbc4165048064fce2ee315750b8769812834a57fbf673661',0,0),
 (9,'4d70cd5d14b7f88d318533cdca3a21ba5da5098239517efbdc3e86720f7f4687',0,0),
 (10,'5866a4bfddfadeb069c783392d63be5b146d29a52f0f41540f16f521852050fd',0,0)
ON DUPLICATE KEY UPDATE hint_level=VALUES(hint_level);

INSERT INTO badges (id, code, label, lab_module_id) VALUES
 (1,'sql_defender','SQL Defender',2),
 (2,'xss_shield','XSS Shield',3),
 (3,'csrf_guardian','CSRF Guardian',4),
 (4,'session_sentinel','Session Sentinel',5),
 (5,'upload_gatekeeper','Upload Gatekeeper',7),
 (6,'audit_hawk','Audit Hawk',8)
ON DUPLICATE KEY UPDATE label=VALUES(label);
