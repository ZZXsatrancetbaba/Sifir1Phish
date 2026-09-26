# -*- coding: utf-8 -*-
# =============================================================================
# modules/dashboard.py — Web Dashboard
# =============================================================================
# KESİNLİKLE EĞİTİM AMAÇLIDIR
# =============================================================================
# Python built-in http.server kullanır — ekstra bağımlılık yok.
# =============================================================================

import os
import sys
import json
import time
import threading
import webbrowser
from pathlib import Path
from http.server import HTTPServer, BaseHTTPRequestHandler
from urllib.parse import urlparse, parse_qs


DASHBOARD_HTML = """<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Sıfır1Cio — Dashboard</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
body{background:#0d1117;color:#c9d1d9;padding:20px;min-height:100vh}
h1{color:#58a6ff;margin-bottom:8px}
h2{color:#79c0ff;border-bottom:1px solid #30363d;padding-bottom:8px;margin:24px 0 16px}
.uyari{background:#3d1414;border:1px solid #f85149;color:#f85149;padding:12px;border-radius:6px;margin:12px 0;text-align:center;font-weight:700}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px}
.refresh{background:#238636;color:#fff;border:none;padding:8px 16px;border-radius:6px;cursor:pointer;font-size:14px}
.refresh:hover{background:#2ea043}
.ozet{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin:20px 0}
.kart{background:#161b22;border:1px solid #30363d;border-radius:8px;padding:20px}
.kart .sayi{font-size:36px;font-weight:700;color:#58a6ff}
.kart .etiket{font-size:12px;color:#8b949e;margin-top:4px;text-transform:uppercase;letter-spacing:1px}
table{width:100%;border-collapse:collapse;margin:12px 0}
th,td{padding:10px 14px;border:1px solid #30363d;text-align:left;font-size:14px}
th{background:#161b22;color:#58a6ff}
tr:nth-child(even){background:#161b22}
.log-kutu{background:#0d1117;border:1px solid #30363d;border-radius:8px;padding:16px;font-family:monospace;font-size:12px;max-height:400px;overflow-y:auto;white-space:pre-wrap}
.footer{margin-top:32px;text-align:center;color:#8b949e;font-size:12px}
</style>
</head>
<body>
<div class="header">
<h1>Sıfır1Cio — Dashboard</h1>
<button class="refresh" onclick="location.reload()">↻ Yenile</button>
</div>
<div class="uyari">KESİNLİKLE EĞİTİM AMAÇLIDIR</div>

<div class="ozet" id="ozet"></div>

<h2>Site Bazında</h2>
<table id="site-tablo">
<tr><th>Site</th><th>Kayıt</th></tr>
</table>

<h2>Son Kayıtlar</h2>
<div class="log-kutu" id="log-kutu">Yükleniyor...</div>

<div class="footer">Sıfır1Cio v5.0 — Eğitim Amaçlı Dashboard</div>

<script>
async function yukle() {
    try {
        const r = await fetch('/api/ozet');
        const data = await r.json();
        document.getElementById('ozet').innerHTML = `
            <div class="kart"><div class="sayi">${data.toplam_kayit}</div><div class="etiket">Toplam Kayıt</div></div>
            <div class="kart"><div class="sayi">${data.benzersiz_ip}</div><div class="etiket">Benzersiz IP</div></div>
            <div class="kart"><div class="sayi">${Object.keys(data.site_sayilari).length}</div><div class="etiket">Site Sayısı</div></div>
        `;

        let siteHtml = '<tr><th>Site</th><th>Kayıt</th></tr>';
        const siteSiralama = Object.entries(data.site_sayilari).sort((a,b) => b[1] - a[1]);
        for (const [site, sayi] of siteSiralama) {
            siteHtml += `<tr><td>${site}</td><td>${sayi}</td></tr>`;
        }
        document.getElementById('site-tablo').innerHTML = siteHtml;
    } catch(e) {
        console.error(e);
    }

    try {
        const r2 = await fetch('/api/son');
        const satirlar = await r2.json();
        const kutu = document.getElementById('log-kutu');
        if (satirlar.length === 0) {
            kutu.textContent = 'Henüz kayıt yok.';
        } else {
            kutu.textContent = satirlar.join('\\n');
        }
    } catch(e) {
        console.error(e);
    }
}
yukle();
setInterval(yukle, 5000);
</script>
</body>
</html>"""


class DashboardHandler(BaseHTTPRequestHandler):
    kok_dizin = Path.cwd()

    def log_message(self, format, *args):
        pass

    def _gonder(self, kod: int, icerik: bytes, tip: str = "text/html; charset=utf-8"):
        self.send_response(kod)
        self.send_header("Content-Type", tip)
        self.send_header("Content-Length", str(len(icerik)))
        self.end_headers()
        self.wfile.write(icerik)

    def do_GET(self):
        yol = urlparse(self.path).path

        if yol == "/" or yol == "/index.html":
            self._gonder(200, DASHBOARD_HTML.encode("utf-8"))
            return

        if yol == "/api/ozet":
            try:
                sys.path.insert(0, str(self.kok_dizin))
                from modules.statistics import Istatistik
                st = Istatistik(self.kok_dizin / "sites")
                ozet = st.ozet()
                icerik = json.dumps(ozet, ensure_ascii=False).encode("utf-8")
                self._gonder(200, icerik, "application/json; charset=utf-8")
            except Exception as e:
                self._gonder(500, json.dumps({"hata": str(e)}).encode(), "application/json")
            return

        if yol == "/api/son":
            try:
                sys.path.insert(0, str(self.kok_dizin))
                from modules.statistics import Istatistik
                st = Istatistik(self.kok_dizin / "sites")
                kayitlar = st.tum_loglari_topla()
                son = []
                for k in kayitlar[-30:]:
                    parcalar = [f"{kk}: {vv}" for kk, vv in k.items()]
                    son.append(" | ".join(parcalar))
                icerik = json.dumps(son, ensure_ascii=False).encode("utf-8")
                self._gonder(200, icerik, "application/json; charset=utf-8")
            except Exception as e:
                self._gonder(500, json.dumps({"hata": str(e)}).encode(), "application/json")
            return

        self._gonder(404, b"Bulunamadi")


class Dashboard:
    def __init__(self, port: int = 9999, kok_dizin: Path = None):
        self.port = port
        self.kok_dizin = kok_dizin or Path(__file__).parent.parent
        self.sunucu = None
        self.thread = None

    def baslat(self):
        DashboardHandler.kok_dizin = self.kok_dizin
        try:
            self.sunucu = HTTPServer(("0.0.0.0", self.port), DashboardHandler)
        except OSError as e:
            print(f"[!] Port {self.port} kullanılamıyor: {e}")
            return False

        self.thread = threading.Thread(target=self.sunucu.serve_forever, daemon=True)
        self.thread.start()
        url = f"http://localhost:{self.port}"
        print(f"[+] Dashboard çalışıyor: {url}")
        return True

    def durdur(self):
        if self.sunucu:
            self.sunucu.shutdown()
            print("[+] Dashboard durduruldu.")


def dashboard_menusu():
    d = Dashboard()
    while True:
        print()
        print("╔══════════════════════════════════════════════╗")
        print("║           Web Dashboard                      ║")
        print("║           KESİNLİKLE EĞİTİM AMAÇLIDIR        ║")
        print("╚══════════════════════════════════════════════╝")
        print()
        print("  [1] Dashboard başlat (port 9999)")
        print("  [2] Farklı portta başlat")
        print("  [3] Tarayıcıda aç")
        print("  [4] Durdur")
        print("  [0] Geri")
        print()
        try:
            s = input("Seçim: ").strip()
        except (EOFError, KeyboardInterrupt):
            d.durdur()
            return
        if s == "0":
            d.durdur()
            return
        elif s == "1":
            if d.baslat():
                try:
                    input("[i] Durdurmak için ENTER...\n")
                except (EOFError, KeyboardInterrupt):
                    pass
        elif s == "2":
            try:
                p = int(input("Port: ").strip())
                d.port = p
                d.baslat()
            except Exception:
                print("[!] Geçersiz port!")
        elif s == "3":
            webbrowser.open(f"http://localhost:{d.port}")
        elif s == "4":
            d.durdur()


if __name__ == "__main__":
    dashboard_menusu()
