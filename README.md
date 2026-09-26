<div align="center">

# 🎣 Sıfır1Cio Phishing Aracı

**v5.0 — Python tabanlı, 35+ site destekli, çok tünelli phishing simülasyon aracı**

[![Python](https://img.shields.io/badge/Python-3.8%2B-blue?logo=python&logoColor=white)](https://www.python.org/)
[![Platform](https://img.shields.io/badge/Platform-Linux%20%7C%20macOS%20%7C%20Termux-informational)](https://kali.org/)
[![Lisans](https://img.shields.io/badge/Lisans-Eğitim%20Amaçlı-red)](./LICENSE)
[![Sürüm](https://img.shields.io/badge/Sürüm-5.0-success)](https://github.com/)
[![Dil](https://img.shields.io/badge/Dil-TR%20%7C%20EN%20%7C%20DE%20%7C%20FR%20%7C%20RU%20%7C%20AR%20%7C%20JA%20%7C%20ZH-yellow)]()

</div>

---

> ## ⚠️ KESİNLİKLE EĞİTİM AMAÇLIDIR
>
> Bu araç **yalnızca eğitim ve güvenlik araştırması** amacıyla geliştirilmiştir.
> Gerçek kişilere karşı izinsiz kullanımı **yasadışıdır** ve etik değildir.
> Kullanımdan doğacak **tüm sorumluluk kullanıcıya aittir**.
>
> Bu yazılımı kullanarak şunları kabul etmiş sayılırsınız:
> - Sadece kendi sistemlerinizde veya izinli testlerde kullanacaksınız
> - Yerel ve uluslararası yasaları ihlal etmeyeceksiniz
> - Geliştirici hiçbir kötüye kullanımdan sorumlu tutulamaz

---

## 📖 İçindekiler

- [Özellikler](#-özellikler)
- [Ekran Görüntüleri](#-ekran-görüntüleri)
- [Kurulum](#-kurulum)
- [Kullanım](#-kullanım)
- [Dosya Yapısı](#-dosya-yapısı)
- [Modüller](#-modüller)
- [Tüneller](#-tüneller)
- [Desteklenen Siteler](#-desteklenen-siteler)
- [SSS](#-sss)
- [Lisans](#-lisans)
- [Uyarı](#-uyarı)

---

## ✨ Özellikler

### Çekirdek
- 🎯 **35+ site şablonu** — Facebook, Instagram, TikTok, Steam, PayPal, Netflix, GitHub, Discord ve daha fazlası
- 🌐 **5 tünel servisi** — Ngrok, Cloudflared, localhost.run, Serveo, localtunnel
- 🖥️ **PHP + Python** — tek komutla başlat, otomatik kurulum
- 📊 **Canlı log izleme** — kurban yakalandığı anda terminale düşer
- 🎨 **ASCII art banner** — Sıfır1Cio imzası

### 8 Ek Özellik (v5.0)
| # | Modül | Açıklama |
|---|-------|----------|
| 1 | 📱 **QR Kod** | Phishing linki için terminal + PNG QR kod |
| 2 | 🔔 **Bildirim** | Telegram / Discord / Webhook entegrasyonu |
| 3 | 📈 **İstatistik** | CSV / JSON / HTML rapor üretimi |
| 4 | 🔐 **Şifreleme** | AES-256 ile log şifreleme |
| 5 | 🌍 **Çoklu Dil** | TR / EN / DE / FR / RU / AR / JA / ZH |
| 6 | 💾 **Oturum** | Oturum kaydet / yükle / sil |
| 7 | 🔄 **Güncelleyici** | `git pull` ile şablon güncelleme |
| 8 | 🌐 **Dashboard** | Web panel (port 9999) — canlı istatistik |

### Sistem
- ✅ **Otomatik bağımlılık kurulumu** — pip + apt fallback (PEP 668 uyumlu)
- ✅ **Sistem kontrolü** — git, curl, php, wget, ssh
- ✅ **Tünel otomatik kurulum** — ngrok, cloudflared (yoksa indirir)
- ✅ **Fallback şablonlar** — Zphisher yoksa bile çalışır
- ✅ **Temizlik** — Ctrl+C ile tüm process'ler kapanır

---

