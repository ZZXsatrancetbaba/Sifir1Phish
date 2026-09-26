# -*- coding: utf-8 -*-
# =============================================================================
# modules/qr_generator.py — QR Kod Oluşturucu
# =============================================================================
# KESİNLİKLE EĞİTİM AMAÇLIDIR
# =============================================================================

import os
import sys
import time
import subprocess
from pathlib import Path
from typing import Optional, List

try:
    import qrcode
    _QRCODE_VAR = True
except ImportError:
    _QRCODE_VAR = False

try:
    from PIL import Image
    _PIL_VAR = True
except ImportError:
    _PIL_VAR = False


class QRKod:
    def __init__(self, cikti_dizini: Optional[Path] = None):
        self.cikti_dizini = cikti_dizini or Path.cwd() / "qr_codes"
        self.cikti_dizini.mkdir(parents=True, exist_ok=True)

    def kutuphane_kontrol(self) -> bool:
        global _QRCODE_VAR, _PIL_VAR
        if not _QRCODE_VAR:
            print("[!] qrcode kütüphanesi eksik, kuruluyor...")
            if self._pip_kur("qrcode"):
                _QRCODE_VAR = True
                print("[+] qrcode kuruldu.")
            else:
                return False
        if not _PIL_VAR:
            if self._pip_kur("Pillow"):
                _PIL_VAR = True
                print("[+] Pillow kuruldu.")
        return True

    def _pip_kur(self, paket: str) -> bool:
        komutlar = [
            f"{sys.executable} -m pip install --break-system-packages --quiet {paket}",
            f"{sys.executable} -m pip install --user --quiet {paket}",
        ]
        for k in komutlar:
            try:
                r = subprocess.run(k, shell=True, capture_output=True, text=True, timeout=120)
                if r.returncode == 0:
                    return True
            except Exception:
                continue
        return False

    def olustur(self, veri: str, dosya_adi: Optional[str] = None,
                kutu_boyut: int = 10, kenarlik: int = 4) -> Optional[Path]:
        if not self.kutuphane_kontrol():
            return None
        try:
            qr = qrcode.QRCode(
                version=None,
                error_correction=qrcode.constants.ERROR_CORRECT_H,
                box_size=kutu_boyut,
                border=kenarlik,
            )
            qr.add_data(veri)
            qr.make(fit=True)
            img = qr.make_image(fill_color="black", back_color="white")
            if dosya_adi is None:
                dosya_adi = f"qr_{time.strftime('%Y%m%d_%H%M%S')}.png"
            if not dosya_adi.endswith(".png"):
                dosya_adi += ".png"
            hedef = self.cikti_dizini / dosya_adi
            img.save(str(hedef))
            print(f"[+] QR kod kaydedildi: {hedef}")
            return hedef
        except Exception as e:
            print(f"[!] QR oluşturma hatası: {e}")
            return None

    def terminal_qr(self, veri: str, ters: bool = True) -> None:
        if not self.kutuphane_kontrol():
            return
        try:
            qr = qrcode.QRCode(border=1)
            qr.add_data(veri)
            qr.make(fit=True)
            matris = qr.get_matrix()
            beyaz = "\033[47m  \033[0m" if ters else "  "
            siyah = "\033[40m  \033[0m" if ters else "██"
            print()
            for satir in matris:
                cizgi = ""
                for hucre in satir:
                    cizgi += siyah if hucre else beyaz
                print(cizgi)
            print()
        except Exception as e:
            print(f"[!] Terminal QR hatası: {e}")

    def toplu_olustur(self, linkler: List[str]) -> List[Path]:
        sonuclar = []
        for i, link in enumerate(linkler, 1):
            print(f"[*] {i}/{len(linkler)}: {link}")
            yol = self.olustur(link, dosya_adi=f"qr_{i:03d}.png")
            if yol:
                sonuclar.append(yol)
        return sonuclar

    def link_qr_goster(self, link: str, kaydet: bool = True) -> None:
        print()
        print("=" * 60)
        print(f"  QR Kod — {link}")
        print("=" * 60)
        self.terminal_qr(link)
        if kaydet:
            self.olustur(link)
        print("=" * 60)
        print()

    def svg_olustur(self, veri: str, dosya_adi: Optional[str] = None) -> Optional[Path]:
        try:
            import qrcode.image.svg
            fabrika = qrcode.image.svg.SvgPathImage
            qr = qrcode.QRCode(
                version=None,
                error_correction=qrcode.constants.ERROR_CORRECT_H,
                box_size=10, border=4, image_factory=fabrika,
            )
            qr.add_data(veri)
            qr.make(fit=True)
            img = qr.make_image()
            if dosya_adi is None:
                dosya_adi = f"qr_{time.strftime('%Y%m%d_%H%M%S')}.svg"
            if not dosya_adi.endswith(".svg"):
                dosya_adi += ".svg"
            hedef = self.cikti_dizini / dosya_adi
            img.save(str(hedef))
            print(f"[+] SVG QR kaydedildi: {hedef}")
            return hedef
        except Exception as e:
            print(f"[!] SVG QR hatası: {e}")
            return None

    def liste_temizle(self, gun: int = 7) -> int:
        simdi = time.time()
        silinen = 0
        for dosya in self.cikti_dizini.glob("qr_*.png"):
            if simdi - dosya.stat().st_mtime > gun * 86400:
                try:
                    dosya.unlink()
                    silinen += 1
                except Exception:
                    pass
        return silinen

    def istatistik(self) -> dict:
        png = list(self.cikti_dizini.glob("qr_*.png"))
        svg = list(self.cikti_dizini.glob("qr_*.svg"))
        toplam = sum(d.stat().st_size for d in png + svg)
        return {
            "png_sayisi": len(png),
            "svg_sayisi": len(svg),
            "toplam_boyut": toplam,
            "dizin": str(self.cikti_dizini),
        }


def qr_menusu() -> None:
    qr = QRKod()
    while True:
        print()
        print("╔══════════════════════════════════════════════╗")
        print("║            QR Kod Oluşturucu                 ║")
        print("║            KESİNLİKLE EĞİTİM AMAÇLIDIR       ║")
        print("╚══════════════════════════════════════════════╝")
        print()
        print("  [1] Link için QR kod (terminal + PNG)")
        print("  [2] Sadece terminal QR göster")
        print("  [3] Sadece PNG kaydet")
        print("  [4] SVG olarak kaydet")
        print("  [5] Toplu QR (her satıra bir link)")
        print("  [6] Eski QR temizle (7 gün+)")
        print("  [7] İstatistik")
        print("  [0] Geri")
        print()
        try:
            secim = input("Seçim: ").strip()
        except (EOFError, KeyboardInterrupt):
            return
        if secim == "0":
            return
        elif secim == "1":
            link = input("Link: ").strip()
            if link: qr.link_qr_goster(link, kaydet=True)
        elif secim == "2":
            link = input("Link: ").strip()
            if link: qr.terminal_qr(link)
        elif secim == "3":
            link = input("Link: ").strip()
            if link: qr.olustur(link)
        elif secim == "4":
            link = input("Link: ").strip()
            if link: qr.svg_olustur(link)
        elif secim == "5":
            print("[i] Linkleri girin (boş satır ile bitir):")
            linkler = []
            while True:
                try:
                    s = input("> ").strip()
                except (EOFError, KeyboardInterrupt):
                    break
                if not s: break
                linkler.append(s)
            if linkler: qr.toplu_olustur(linkler)
        elif secim == "6":
            n = qr.liste_temizle()
            print(f"[+] {n} dosya silindi.")
        elif secim == "7":
            st = qr.istatistik()
            for k, v in st.items():
                print(f"  {k}: {v}")


if __name__ == "__main__":
    qr_menusu()
