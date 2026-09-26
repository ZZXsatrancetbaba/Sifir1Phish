# -*- coding: utf-8 -*-
# =============================================================================
# modules/i18n.py — Çoklu Dil Desteği
# =============================================================================
# KESİNLİKLE EĞİTİM AMAÇLIDIR
# =============================================================================

import os
import json
from pathlib import Path
from typing import Dict, Optional


DILLER = {
    "tr": "Türkçe",
    "en": "English",
    "de": "Deutsch",
    "fr": "Français",
    "ru": "Русский",
    "ar": "العربية",
    "ja": "日本語",
    "zh": "中文",
}

VARSAYILAN_DIL = "tr"


CEVIRILER: Dict[str, Dict[str, str]] = {
    "tr": {
        "baslik": "Sıfır1Cio Phishing Aracı",
        "egitim_uyari": "KESİNLİKLE EĞİTİM AMAÇLIDIR",
        "egitim_detay": "Bu araç yalnızca eğitim ve güvenlik araştırması amacıyla geliştirilmiştir.",
        "sorumluluk": "Kullanımdan doğacak tüm sorumluluk kullanıcıya aittir.",
        "menu_siteler": "Mevcut Siteler",
        "menu_secim": "Seçim",
        "menu_cikis": "Çıkış",
        "tunel_baslik": "Tünel Servisi Seçin",
        "tunel_ngrok": "Ngrok",
        "tunel_cloudflared": "Cloudflared",
        "tunel_localhost_run": "localhost.run",
        "tunel_serveo": "Serveo",
        "tunel_localtunnel": "localtunnel",
        "port_sor": "Port numarası (varsayılan 8080)",
        "port_kullanimda": "Port kullanımda",
        "baslatiliyor": "Başlatılıyor",
        "site_olusturuldu": "Site oluşturuldu",
        "php_baslatiliyor": "PHP sunucusu başlatılıyor",
        "php_calisiyor": "PHP sunucusu çalışıyor",
        "tunel_baslatiliyor": "Tünel başlatılıyor",
        "tunel_aktif": "Tünel aktif",
        "tunel_hata": "Tünel başlatılamadı",
        "phishing_link": "PHISHING LİNKİ",
        "loglar": "Loglar",
        "canli_log": "Canlı log izleme başlatılıyor",
        "ctrl_c": "Çıkmak için Ctrl+C basın",
        "yeni_kurban": "YENİ KURBAN YAKALANDI",
        "sablon_bulunamadi": "Şablon bulunamadı",
        "kopyalama_hatasi": "Kopyalama hatası",
        "kurulum_gerekli": "Önce install.sh çalıştırın",
        "enter_devam": "Devam etmek için ENTER'a basın",
        "temizlik": "Temizlik yapılıyor",
        "cikis": "Çıkış yapıldı",
        "qr_menu": "QR Kod Oluşturucu",
        "bildirim_menu": "Bildirim Ayarları",
        "istatistik_menu": "İstatistik ve Raporlama",
        "sifreleme_menu": "Log Şifreleme",
        "dil_menu": "Dil Seçimi",
        "oturum_menu": "Oturum Yönetimi",
        "guncelleme_menu": "Şablon Güncelleme",
        "dashboard_menu": "Web Dashboard",
    },
    "en": {
        "baslik": "Sıfır1Cio Phishing Tool",
        "egitim_uyari": "STRICTLY FOR EDUCATIONAL PURPOSES",
        "egitim_detay": "This tool is developed solely for educational and security research purposes.",
        "sorumluluk": "All responsibility arising from use belongs to the user.",
        "menu_siteler": "Available Sites",
        "menu_secim": "Selection",
        "menu_cikis": "Exit",
        "tunel_baslik": "Select Tunnel Service",
        "tunel_ngrok": "Ngrok",
        "tunel_cloudflared": "Cloudflared",
        "tunel_localhost_run": "localhost.run",
        "tunel_serveo": "Serveo",
        "tunel_localtunnel": "localtunnel",
        "port_sor": "Port number (default 8080)",
        "port_kullanimda": "Port in use",
        "baslatiliyor": "Starting",
        "site_olusturuldu": "Site created",
        "php_baslatiliyor": "PHP server starting",
        "php_calisiyor": "PHP server running",
        "tunel_baslatiliyor": "Tunnel starting",
        "tunel_aktif": "Tunnel active",
        "tunel_hata": "Tunnel failed to start",
        "phishing_link": "PHISHING LINK",
        "loglar": "Logs",
        "canli_log": "Live log monitoring starting",
        "ctrl_c": "Press Ctrl+C to exit",
        "yeni_kurban": "NEW VICTIM CAUGHT",
        "sablon_bulunamadi": "Template not found",
        "kopyalama_hatasi": "Copy error",
        "kurulum_gerekli": "Run install.sh first",
        "enter_devam": "Press ENTER to continue",
        "temizlik": "Cleaning up",
        "cikis": "Exited",
        "qr_menu": "QR Code Generator",
        "bildirim_menu": "Notification Settings",
        "istatistik_menu": "Statistics and Reporting",
        "sifreleme_menu": "Log Encryption",
        "dil_menu": "Language Selection",
        "oturum_menu": "Session Management",
        "guncelleme_menu": "Template Update",
        "dashboard_menu": "Web Dashboard",
    },
    "de": {
        "baslik": "Sıfır1Cio Phishing-Tool",
        "egitim_uyari": "AUSSCHLIESSLICH FÜR BILDUNGSZWECKE",
        "egitim_detay": "Dieses Tool wurde ausschließlich für Bildungs- und Sicherheitsforschungszwecke entwickelt.",
        "sorumluluk": "Die gesamte Verantwortung liegt beim Benutzer.",
        "menu_siteler": "Verfügbare Seiten",
        "menu_secim": "Auswahl",
        "menu_cikis": "Beenden",
        "tunel_baslik": "Tunnel-Dienst auswählen",
        "tunel_ngrok": "Ngrok",
        "tunel_cloudflared": "Cloudflared",
        "tunel_localhost_run": "localhost.run",
        "tunel_serveo": "Serveo",
        "tunel_localtunnel": "localtunnel",
        "port_sor": "Portnummer (Standard 8080)",
        "port_kullanimda": "Port belegt",
        "baslatiliyor": "Startet",
        "site_olusturuldu": "Seite erstellt",
        "php_baslatiliyor": "PHP-Server startet",
        "php_calisiyor": "PHP-Server läuft",
        "tunel_baslatiliyor": "Tunnel startet",
        "tunel_aktif": "Tunnel aktiv",
        "tunel_hata": "Tunnel konnte nicht gestartet werden",
        "phishing_link": "PHISHING-LINK",
        "loglar": "Protokolle",
        "canli_log": "Live-Protokollierung startet",
        "ctrl_c": "Ctrl+C zum Beenden",
        "yeni_kurban": "NEUES OPFER GEFANGEN",
        "sablon_bulunamadi": "Vorlage nicht gefunden",
        "kopyalama_hatasi": "Kopierfehler",
        "kurulum_gerekli": "Führen Sie zuerst install.sh aus",
        "enter_devam": "ENTER zum Fortfahren",
        "temizlik": "Aufräumen",
        "cikis": "Beendet",
        "qr_menu": "QR-Code-Generator",
        "bildirim_menu": "Benachrichtigungseinstellungen",
        "istatistik_menu": "Statistik und Berichte",
        "sifreleme_menu": "Protokollverschlüsselung",
        "dil_menu": "Sprachauswahl",
        "oturum_menu": "Sitzungsverwaltung",
        "guncelleme_menu": "Vorlagen-Update",
        "dashboard_menu": "Web-Dashboard",
    },
    "fr": {
        "baslik": "Outil de Phishing Sıfır1Cio",
        "egitim_uyari": "STRICTEMENT À DES FINS ÉDUCATIVES",
        "egitim_detay": "Cet outil est développé uniquement à des fins éducatives et de recherche en sécurité.",
        "sorumluluk": "Toute responsabilité découlant de l'utilisation incombe à l'utilisateur.",
        "menu_siteler": "Sites disponibles",
        "menu_secim": "Sélection",
        "menu_cikis": "Quitter",
        "tunel_baslik": "Sélectionner le service tunnel",
        "tunel_ngrok": "Ngrok",
        "tunel_cloudflared": "Cloudflared",
        "tunel_localhost_run": "localhost.run",
        "tunel_serveo": "Serveo",
        "tunel_localtunnel": "localtunnel",
        "port_sor": "Numéro de port (par défaut 8080)",
        "port_kullanimda": "Port utilisé",
        "baslatiliyor": "Démarrage",
        "site_olusturuldu": "Site créé",
        "php_baslatiliyor": "Démarrage du serveur PHP",
        "php_calisiyor": "Serveur PHP en cours",
        "tunel_baslatiliyor": "Démarrage du tunnel",
        "tunel_aktif": "Tunnel actif",
        "tunel_hata": "Échec du démarrage du tunnel",
        "phishing_link": "LIEN DE PHISHING",
        "loglar": "Journaux",
        "canli_log": "Surveillance en direct des journaux",
        "ctrl_c": "Appuyez sur Ctrl+C pour quitter",
        "yeni_kurban": "NOUVELLE VICTIME CAPTURÉE",
        "sablon_bulunamadi": "Modèle introuvable",
        "kopyalama_hatasi": "Erreur de copie",
        "kurulum_gerekli": "Exécutez d'abord install.sh",
        "enter_devam": "Appuyez sur ENTRÉE pour continuer",
        "temizlik": "Nettoyage",
        "cikis": "Quitté",
        "qr_menu": "Générateur de QR Code",
        "bildirim_menu": "Paramètres de notification",
        "istatistik_menu": "Statistiques et rapports",
        "sifreleme_menu": "Chiffrement des journaux",
        "dil_menu": "Sélection de la langue",
        "oturum_menu": "Gestion de session",
        "guncelleme_menu": "Mise à jour des modèles",
        "dashboard_menu": "Tableau de bord Web",
    },
    "ru": {
        "baslik": "Инструмент фишинга Sıfır1Cio",
        "egitim_uyari": "СТРОГО В ОБРАЗОВАТЕЛЬНЫХ ЦЕЛЯХ",
        "egitim_detay": "Этот инструмент разработан исключительно для образовательных и исследовательских целей.",
        "sorumluluk": "Вся ответственность за использование лежит на пользователе.",
        "menu_siteler": "Доступные сайты",
        "menu_secim": "Выбор",
        "menu_cikis": "Выход",
        "tunel_baslik": "Выберите туннельный сервис",
        "tunel_ngrok": "Ngrok",
        "tunel_cloudflared": "Cloudflared",
        "tunel_localhost_run": "localhost.run",
        "tunel_serveo": "Serveo",
        "tunel_localtunnel": "localtunnel",
        "port_sor": "Номер порта (по умолчанию 8080)",
        "port_kullanimda": "Порт занят",
        "baslatiliyor": "Запуск",
        "site_olusturuldu": "Сайт создан",
        "php_baslatiliyor": "Запуск PHP-сервера",
        "php_calisiyor": "PHP-сервер работает",
        "tunel_baslatiliyor": "Запуск туннеля",
        "tunel_aktif": "Туннель активен",
        "tunel_hata": "Не удалось запустить туннель",
        "phishing_link": "ФИШИНГОВАЯ ССЫЛКА",
        "loglar": "Журналы",
        "canli_log": "Мониторинг журналов в реальном времени",
        "ctrl_c": "Ctrl+C для выхода",
        "yeni_kurban": "НОВАЯ ЖЕРТВА ПОЙМАНА",
        "sablon_bulunamadi": "Шаблон не найден",
        "kopyalama_hatasi": "Ошибка копирования",
        "kurulum_gerekli": "Сначала запустите install.sh",
        "enter_devam": "Нажмите ENTER для продолжения",
        "temizlik": "Очистка",
        "cikis": "Выход выполнен",
        "qr_menu": "Генератор QR-кодов",
        "bildirim_menu": "Настройки уведомлений",
        "istatistik_menu": "Статистика и отчёты",
        "sifreleme_menu": "Шифрование журналов",
        "dil_menu": "Выбор языка",
        "oturum_menu": "Управление сеансом",
        "guncelleme_menu": "Обновление шаблонов",
        "dashboard_menu": "Веб-панель",
    },
    "ar": {
        "baslik": "أداة التصيد Sıfır1Cio",
        "egitim_uyari": "للأغراض التعليمية فقط",
        "egitim_detay": "تم تطوير هذه الأداة فقط للأغراض التعليمية والبحث الأمني.",
        "sorumluluk": "تقع المسؤولية الكاملة على المستخدم.",
        "menu_siteler": "المواقع المتاحة",
        "menu_secim": "اختيار",
        "menu_cikis": "خروج",
        "tunel_baslik": "اختر خدمة النفق",
        "tunel_ngrok": "Ngrok",
        "tunel_cloudflared": "Cloudflared",
        "tunel_localhost_run": "localhost.run",
        "tunel_serveo": "Serveo",
        "tunel_localtunnel": "localtunnel",
        "port_sor": "رقم المنفذ (الافتراضي 8080)",
        "port_kullanimda": "المنفذ مستخدم",
        "baslatiliyor": "جارٍ البدء",
        "site_olusturuldu": "تم إنشاء الموقع",
        "php_baslatiliyor": "جارٍ بدء خادم PHP",
        "php_calisiyor": "خادم PHP يعمل",
        "tunel_baslatiliyor": "جارٍ بدء النفق",
        "tunel_aktif": "النفق نشط",
        "tunel_hata": "فشل بدء النفق",
        "phishing_link": "رابط التصيد",
        "loglar": "السجلات",
        "canli_log": "بدء مراقبة السجل المباشر",
        "ctrl_c": "اضغط Ctrl+C للخروج",
        "yeni_kurban": "تم الإمساك بضحية جديدة",
        "sablon_bulunamadi": "لم يتم العثور على القالب",
        "kopyalama_hatasi": "خطأ في النسخ",
        "kurulum_gerekli": "شغّل install.sh أولاً",
        "enter_devam": "اضغط ENTER للمتابعة",
        "temizlik": "جارٍ التنظيف",
        "cikis": "تم الخروج",
        "qr_menu": "مولد رمز QR",
        "bildirim_menu": "إعدادات الإشعارات",
        "istatistik_menu": "الإحصائيات والتقارير",
        "sifreleme_menu": "تشفير السجلات",
        "dil_menu": "اختيار اللغة",
        "oturum_menu": "إدارة الجلسة",
        "guncelleme_menu": "تحديث القوالب",
        "dashboard_menu": "لوحة التحكم على الويب",
    },
    "ja": {
        "baslik": "Sıfır1Cio フィッシングツール",
        "egitim_uyari": "教育目的のみ",
        "egitim_detay": "このツールは教育およびセキュリティ研究目的でのみ開発されています。",
        "sorumluluk": "使用によって生じるすべての責任はユーザーにあります。",
        "menu_siteler": "利用可能なサイト",
        "menu_secim": "選択",
        "menu_cikis": "終了",
        "tunel_baslik": "トンネルサービスを選択",
        "tunel_ngrok": "Ngrok",
        "tunel_cloudflared": "Cloudflared",
        "tunel_localhost_run": "localhost.run",
        "tunel_serveo": "Serveo",
        "tunel_localtunnel": "localtunnel",
        "port_sor": "ポート番号（デフォルト 8080）",
        "port_kullanimda": "ポート使用中",
        "baslatiliyor": "起動中",
        "site_olusturuldu": "サイトが作成されました",
        "php_baslatiliyor": "PHPサーバー起動中",
        "php_calisiyor": "PHPサーバー実行中",
        "tunel_baslatiliyor": "トンネル起動中",
        "tunel_aktif": "トンネルアクティブ",
        "tunel_hata": "トンネル起動失敗",
        "phishing_link": "フィッシングリンク",
        "loglar": "ログ",
        "canli_log": "ライブログ監視を開始",
        "ctrl_c": "Ctrl+Cで終了",
        "yeni_kurban": "新しい犠牲者を捕まえた",
        "sablon_bulunamadi": "テンプレートが見つかりません",
        "kopyalama_hatasi": "コピーエラー",
        "kurulum_gerekli": "先に install.sh を実行してください",
        "enter_devam": "ENTERで続行",
        "temizlik": "クリーンアップ中",
        "cikis": "終了しました",
        "qr_menu": "QRコードジェネレーター",
        "bildirim_menu": "通知設定",
        "istatistik_menu": "統計とレポート",
        "sifreleme_menu": "ログ暗号化",
        "dil_menu": "言語選択",
        "oturum_menu": "セッション管理",
        "guncelleme_menu": "テンプレート更新",
        "dashboard_menu": "Webダッシュボード",
    },
    "zh": {
        "baslik": "Sıfır1Cio 钓鱼工具",
        "egitim_uyari": "仅供教育用途",
        "egitim_detay": "此工具仅用于教育和安全研究目的。",
        "sorumluluk": "使用产生的所有责任由用户承担。",
        "menu_siteler": "可用站点",
        "menu_secim": "选择",
        "menu_cikis": "退出",
        "tunel_baslik": "选择隧道服务",
        "tunel_ngrok": "Ngrok",
        "tunel_cloudflared": "Cloudflared",
        "tunel_localhost_run": "localhost.run",
        "tunel_serveo": "Serveo",
        "tunel_localtunnel": "localtunnel",
        "port_sor": "端口号（默认 8080）",
        "port_kullanimda": "端口被占用",
        "baslatiliyor": "启动中",
        "site_olusturuldu": "站点已创建",
        "php_baslatiliyor": "PHP 服务器启动中",
        "php_calisiyor": "PHP 服务器运行中",
        "tunel_baslatiliyor": "隧道启动中",
        "tunel_aktif": "隧道已激活",
        "tunel_hata": "隧道启动失败",
        "phishing_link": "钓鱼链接",
        "loglar": "日志",
        "canli_log": "开始实时日志监控",
        "ctrl_c": "按 Ctrl+C 退出",
        "yeni_kurban": "捕获新受害者",
        "sablon_bulunamadi": "未找到模板",
        "kopyalama_hatasi": "复制错误",
        "kurulum_gerekli": "请先运行 install.sh",
        "enter_devam": "按 ENTER 继续",
        "temizlik": "清理中",
        "cikis": "已退出",
        "qr_menu": "二维码生成器",
        "bildirim_menu": "通知设置",
        "istatistik_menu": "统计和报告",
        "sifreleme_menu": "日志加密",
        "dil_menu": "语言选择",
        "oturum_menu": "会话管理",
        "guncelleme_menu": "模板更新",
        "dashboard_menu": "Web 仪表板",
    },
}


class Ceviri:
    def __init__(self, dil: Optional[str] = None):
        self.dil = dil or self._dil_dosyadan_yukle() or VARSAYILAN_DIL
        if self.dil not in DILLER:
            self.dil = VARSAYILAN_DIL

    def _dil_dosyasi(self) -> Path:
        return Path(__file__).parent.parent / "config.json"

    def _dil_dosyadan_yukle(self) -> Optional[str]:
        try:
            yol = self._dil_dosyasi()
            if yol.exists():
                with open(yol, "r", encoding="utf-8") as f:
                    data = json.load(f)
                return data.get("dil")
        except Exception:
            pass
        return None

    def _dil_dosyaya_kaydet(self):
        try:
            yol = self._dil_dosyasi()
            data = {}
            if yol.exists():
                with open(yol, "r", encoding="utf-8") as f:
                    data = json.load(f)
            data["dil"] = self.dil
            with open(yol, "w", encoding="utf-8") as f:
                json.dump(data, f, indent=2, ensure_ascii=False)
        except Exception:
            pass

    def t(self, anahtar: str) -> str:
        return CEVIRILER.get(self.dil, CEVIRILER[VARSAYILAN_DIL]).get(anahtar, anahtar)

    def dil_degistir(self, yeni_dil: str) -> bool:
        if yeni_dil in DILLER:
            self.dil = yeni_dil
            self._dil_dosyaya_kaydet()
            return True
        return False

    def dil_listesi(self):
        return DILLER

    def mevcut_dil(self) -> str:
        return self.dil

    def dil_menusu(self) -> bool:
        print()
        print("╔══════════════════════════════════════════════╗")
        print("║              Dil Seçimi / Language           ║")
        print("╚══════════════════════════════════════════════╝")
        print()
        diller = list(DILLER.items())
        for i, (kod, isim) in enumerate(diller, 1):
            isaret = "●" if kod == self.dil else "○"
            print(f"  [{i}] {isaret} {isim} ({kod})")
        print(f"  [0] Geri")
        print()
        try:
            secim = input("Seçim: ").strip()
        except (EOFError, KeyboardInterrupt):
            return False
        if secim == "0":
            return False
        try:
            idx = int(secim) - 1
            if 0 <= idx < len(diller):
                kod = diller[idx][0]
                if self.dil_degistir(kod):
                    print(f"[+] Dil değiştirildi: {DILLER[kod]}")
                    return True
        except (ValueError, IndexError):
            pass
        print("[!] Geçersiz seçim!")
        return False


_ceviri = None


def ceviri_al() -> Ceviri:
    global _ceviri
    if _ceviri is None:
        _ceviri = Ceviri()
    return _ceviri


def t(anahtar: str) -> str:
    return ceviri_al().t(anahtar)


if __name__ == "__main__":
    c = Ceviri()
    print(f"Mevcut dil: {c.dil}")
    c.dil_menusu()
