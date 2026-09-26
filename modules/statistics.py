# -*- coding: utf-8 -*-
# =============================================================================
# modules/statistics.py — İstatistik ve Raporlama
# =============================================================================
# KESİNLİKLE EĞİTİM AMAÇLIDIR
# =============================================================================
# Log dosyalarını analiz eder, CSV/JSON/HTML rapor üretir.
# =============================================================================

import os
import re
import json
import time
import csv
from pathlib import Path
from datetime import datetime
from typing import Optional, List, Dict


class Istatistik:
    def __init__(self, log_dizini: Optional[Path] = None):
        self.log_dizini = log_dizini or Path(__file__).parent.parent / "sites"
        self.rapor_dizini = Path(__file__).parent.parent / "reports"
        self.rapor_dizini.mkdir(exist_ok=True)

    def tum_loglari_topla(self) -> List[Dict]:
        kayitlar = []
        if not self.log_dizini.exists():
            return kayitlar

        for site_dir in self.log_dizini.iterdir():
            if not site_dir.is_dir():
                continue
            log_dosya = site_dir / "logs" / "log.txt"
            if not log_dosya.exists():
                continue
            site_adi = site_dir.name
            try:
                icerik = log_dosya.read_text(encoding="utf-8", errors="ignore")
            except Exception:
                continue

            for blok in icerik.split("════════════════════════════════════════"):
                if not blok.strip():
                    continue
                kayit = {"site": site_adi}
                for satir in blok.splitlines():
                    satir = satir.strip()
                    if not satir or satir == "--- Veriler ---":
                        continue
                    if ":" in satir:
                        k, _, v = satir.partition(":")
                        kayit[k.strip().lower()] = v.strip()
                if "ip" in kayit or "tarih" in kayit:
                    kayitlar.append(kayit)

        return kayitlar

    def ozet(self) -> Dict:
        kayitlar = self.tum_loglari_topla()
        site_sayilari = {}
        ip_sayilari = {}
        saat_dagilimi = {}

        for k in kayitlar:
            site = k.get("site", "?")
            site_sayilari[site] = site_sayilari.get(site, 0) + 1

            ip = k.get("ip", "?")
            if ip and ip != "bilinmiyor":
                ip_sayilari[ip] = ip_sayilari.get(ip, 0) + 1

            tarih = k.get("tarih", "")
            m = re.search(r'(\d{2}):', tarih)
            if m:
                saat = m.group(1) + ":00"
                saat_dagilimi[saat] = saat_dagilimi.get(saat, 0) + 1

        return {
            "toplam_kayit": len(kayitlar),
            "site_sayilari": site_sayilari,
            "benzersiz_ip": len(ip_sayilari),
            "ip_sayilari": ip_sayilari,
            "saat_dagilimi": saat_dagilimi,
        }

    def csv_rapor(self, dosya_adi: Optional[str] = None) -> Path:
        kayitlar = self.tum_loglari_topla()
        if dosya_adi is None:
            dosya_adi = f"rapor_{time.strftime('%Y%m%d_%H%M%S')}.csv"
        hedef = self.rapor_dizini / dosya_adi

        alanlar = set()
        for k in kayitlar:
            alanlar.update(k.keys())
        alanlar = ["site"] + sorted(a for a in alanlar if a != "site")

        with open(hedef, "w", newline="", encoding="utf-8") as f:
            writer = csv.DictWriter(f, fieldnames=alanlar, extrasaction="ignore")
            writer.writeheader()
            for k in kayitlar:
                writer.writerow(k)

        print(f"[+] CSV rapor: {hedef}")
        return hedef

    def json_rapor(self, dosya_adi: Optional[str] = None) -> Path:
        ozet = self.ozet()
        kayitlar = self.tum_loglari_topla()
        if dosya_adi is None:
            dosya_adi = f"rapor_{time.strftime('%Y%m%d_%H%M%S')}.json"
        hedef = self.rapor_dizini / dosya_adi

        veri = {
            "olusturma": datetime.now().isoformat(),
            "ozet": ozet,
            "kayitlar": kayitlar,
            "uyari": "KESİNLİKLE EĞİTİM AMAÇLIDIR",
        }

        with open(hedef, "w", encoding="utf-8") as f:
            json.dump(veri, f, indent=2, ensure_ascii=False)

        print(f"[+] JSON rapor: {hedef}")
        return hedef

    def html_rapor(self, dosya_adi: Optional[str] = None) -> Path:
        ozet = self.ozet()
        kayitlar = self.tum_loglari_topla()

        if dosya_adi is None:
            dosya_adi = f"rapor_{time.strftime('%Y%m%d_%H%M%S')}.html"
        hedef = self.rapor_dizini / dosya_adi

        site_satirlari = "".join(
            f"<tr><td>{s}</td><td>{n}</td></tr>"
            for s, n in sorted(ozet["site_sayilari"].items(), key=lambda x: -x[1])
        )

        kayit_satirlari = ""
        for k in kayitlar[:500]:
            hucreler = "".join(f"<td>{v}</td>" for v in k.values())
            kayit_satirlari += f"<tr>{hucreler}</tr>"

        html = f"""<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Sıfır1Cio Rapor</title>
<style>
body{{font-family:sans-serif;background:#0d1117;color:#c9d1d9;padding:20px}}
h1{{color:#58a6ff}}
h2{{color:#79c0ff;border-bottom:1px solid #30363d;padding-bottom:8px}}
table{{width:100%;border-collapse:collapse;margin:12px 0}}
th,td{{padding:8px 12px;border:1px solid #30363d;text-align:left}}
th{{background:#161b22;color:#58a6ff}}
tr:nth-child(even){{background:#161b22}}
.uyari{{background:#3d1414;border:1px solid #f85149;color:#f85149;padding:12px;border-radius:6px;margin:20px 0}}
.ozet{{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:20px 0}}
.kart{{background:#161b22;border:1px solid #30363d;border-radius:8px;padding:16px}}
.kart .sayi{{font-size:32px;font-weight:700;color:#58a6ff}}
.kart .etiket{{font-size:12px;color:#8b949e}}
</style>
</head>
<body>
<h1>Sıfır1Cio — Rapor</h1>
<div class="uyari">KESİNLİKLE EĞİTİM AMAÇLIDIR</div>
<p>Oluşturma: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}</p>

<div class="ozet">
<div class="kart"><div class="sayi">{ozet['toplam_kayit']}</div><div class="etiket">Toplam Kayıt</div></div>
<div class="kart"><div class="sayi">{ozet['benzersiz_ip']}</div><div class="etiket">Benzersiz IP</div></div>
<div class="kart"><div class="sayi">{len(ozet['site_sayilari'])}</div><div class="etiket">Site Sayısı</div></div>
</div>

<h2>Site Bazında</h2>
<table><tr><th>Site</th><th>Kayıt</th></tr>{site_satirlari}</table>

<h2>Kayıtlar (ilk 500)</h2>
<table><tr><th>Detay</th></tr>{kayit_satirlari}</table>

</body>
</html>"""

        hedef.write_text(html, encoding="utf-8")
        print(f"[+] HTML rapor: {hedef}")
        return hedef

    def yazdir_ozet(self):
        ozet = self.ozet()
        print()
        print("=" * 50)
        print(f"  Toplam Kayıt: {ozet['toplam_kayit']}")
        print(f"  Benzersiz IP: {ozet['benzersiz_ip']}")
        print(f"  Site Sayısı:  {len(ozet['site_sayilari'])}")
        print("=" * 50)
        if ozet["site_sayilari"]:
            print("\n  Site dağılımı:")
            for s, n in sorted(ozet["site_sayilari"].items(), key=lambda x: -x[1]):
                print(f"    {s:20s} {n}")
        print()


def istatistik_menusu():
    st = Istatistik()
    while True:
        print()
        print("╔══════════════════════════════════════════════╗")
        print("║       İstatistik ve Raporlama                ║")
        print("║       KESİNLİKLE EĞİTİM AMAÇLIDIR           ║")
        print("╚══════════════════════════════════════════════╝")
        print()
        print("  [1] Özet göster")
        print("  [2] CSV rapor üret")
        print("  [3] JSON rapor üret")
        print("  [4] HTML rapor üret")
        print("  [5] Hepsi")
        print("  [0] Geri")
        print()
        try:
            s = input("Seçim: ").strip()
        except (EOFError, KeyboardInterrupt):
            return
        if s == "0":
            return
        elif s == "1":
            st.yazdir_ozet()
        elif s == "2":
            st.csv_rapor()
        elif s == "3":
            st.json_rapor()
        elif s == "4":
            st.html_rapor()
        elif s == "5":
            st.csv_rapor()
            st.json_rapor()
            st.html_rapor()
            st.yazdir_ozet()


if __name__ == "__main__":
    istatistik_menusu()
