# -*- coding: utf-8 -*-
# =============================================================================
# modules/crypto.py — Log Şifreleme (AES-256)
# =============================================================================
# KESİNLİKLE EĞİTİM AMAÇLIDIR
# =============================================================================

import os
import sys
import json
import base64
import hashlib
import subprocess
from pathlib import Path
from typing import Optional

try:
    from cryptography.fernet import Fernet
    from cryptography.hazmat.primitives.kdf.pbkdf2 import PBKDF2HMAC
    from cryptography.hazmat.primitives import hashes
    _CRYPTO_VAR = True
except ImportError:
    _CRYPTO_VAR = False


class Sifreleyici:
    def __init__(self, anahtar_dosyasi: Optional[Path] = None):
        self.anahtar_dosyasi = anahtar_dosyasi or Path(__file__).parent.parent / ".master.key"

    def kutuphane_kontrol(self) -> bool:
        global _CRYPTO_VAR
        if not _CRYPTO_VAR:
            print("[!] cryptography kütüphanesi eksik, kuruluyor...")
            if self._pip_kur("cryptography"):
                _CRYPTO_VAR = True
                print("[+] cryptography kuruldu.")
                # Yeniden import et
                try:
                    from cryptography.fernet import Fernet
                    globals()["Fernet"] = Fernet
                except ImportError:
                    return False
            else:
                return False
        return True

    def _pip_kur(self, paket: str) -> bool:
        komutlar = [
            f"{sys.executable} -m pip install --break-system-packages --quiet {paket}",
            f"{sys.executable} -m pip install --user --quiet {paket}",
        ]
        for k in komutlar:
            try:
                r = subprocess.run(k, shell=True, capture_output=True, text=True, timeout=180)
                if r.returncode == 0:
                    return True
            except Exception:
                continue
        return False

    def _anahtar_uret(self, sifre: str, salt: bytes) -> bytes:
        kdf = PBKDF2HMAC(
            algorithm=hashes.SHA256(),
            length=32,
            salt=salt,
            iterations=100000,
        )
        anahtar = kdf.derive(sifre.encode())
        return base64.urlsafe_b64encode(anahtar)

    def _fernet_al(self, sifre: str, salt: bytes):
        anahtar = self._anahtar_uret(sifre, salt)
        return Fernet(anahtar)

    def sifrele(self, veri: str, sifre: str) -> str:
        if not self.kutuphane_kontrol():
            return veri
        salt = os.urandom(16)
        f = self._fernet_al(sifre, salt)
        token = f.encrypt(veri.encode())
        return base64.urlsafe_b64encode(salt).decode() + ":" + token.decode()

    def coz(self, sifreli: str, sifre: str) -> Optional[str]:
        if not self.kutuphane_kontrol():
            return None
        try:
            parcalar = sifreli.split(":", 1)
            if len(parcalar) != 2:
                return None
            salt = base64.urlsafe_b64decode(parcalar[0].encode())
            token = parcalar[1].encode()
            f = self._fernet_al(sifre, salt)
            return f.decrypt(token).decode()
        except Exception as e:
            print(f"[!] Çözme hatası: {e}")
            return None

    def dosya_sifrele(self, kaynak: Path, hedef: Optional[Path] = None, sifre: str = "") -> Optional[Path]:
        if not kaynak.exists():
            print(f"[!] Dosya yok: {kaynak}")
            return None
        try:
            icerik = kaynak.read_text(encoding="utf-8", errors="ignore")
        except Exception as e:
            print(f"[!] Okuma hatası: {e}")
            return None

        sifreli = self.sifrele(icerik, sifre)
        if hedef is None:
            hedef = kaynak.with_suffix(kaynak.suffix + ".enc")

        hedef.write_text(sifreli, encoding="utf-8")
        print(f"[+] Şifrelendi: {hedef}")
        return hedef

    def dosya_coz(self, kaynak: Path, hedef: Optional[Path] = None, sifre: str = "") -> Optional[Path]:
        if not kaynak.exists():
            print(f"[!] Dosya yok: {kaynak}")
            return None
        try:
            sifreli = kaynak.read_text(encoding="utf-8", errors="ignore")
        except Exception as e:
            print(f"[!] Okuma hatası: {e}")
            return None

        cozulen = self.coz(sifreli, sifre)
        if cozulen is None:
            print("[!] Çözme başarısız (yanlış şifre?)")
            return None

        if hedef is None:
            hedef = kaynak.with_suffix("")

        hedef.write_text(cozulen, encoding="utf-8")
        print(f"[+] Çözüldü: {hedef}")
        return hedef

    def log_dizini_sifrele(self, log_dizini: Path, sifre: str) -> int:
        sayac = 0
        for log_dosya in log_dizini.rglob("log.txt"):
            if self.dosya_sifrele(log_dosya, sifre=sifre):
                sayac += 1
        return sayac


def crypto_menusu():
    s = Sifreleyici()
    while True:
        print()
        print("╔══════════════════════════════════════════════╗")
        print("║          Log Şifreleme (AES-256)             ║")
        print("║          KESİNLİKLE EĞİTİM AMAÇLIDIR        ║")
        print("╚══════════════════════════════════════════════╝")
        print()
        print("  [1] Metin şifrele")
        print("  [2] Metin çöz")
        print("  [3] Dosya şifrele")
        print("  [4] Dosya çöz")
        print("  [5] Tüm logları şifrele")
        print("  [0] Geri")
        print()
        try:
            secim = input("Seçim: ").strip()
        except (EOFError, KeyboardInterrupt):
            return
        if secim == "0":
            return
        elif secim == "1":
            veri = input("Metin: ").strip()
            sifre = input("Şifre: ").strip()
            if veri and sifre:
                print(f"Şifreli: {s.sifrele(veri, sifre)}")
        elif secim == "2":
            veri = input("Şifreli metin: ").strip()
            sifre = input("Şifre: ").strip()
            if veri and sifre:
                sonuc = s.coz(veri, sifre)
                print(f"Çözülen: {sonuc}" if sonuc else "Çözülemedi.")
        elif secim == "3":
            yol = input("Dosya yolu: ").strip()
            sifre = input("Şifre: ").strip()
            if yol and sifre:
                s.dosya_sifrele(Path(yol), sifre=sifre)
        elif secim == "4":
            yol = input("Şifreli dosya: ").strip()
            sifre = input("Şifre: ").strip()
            if yol and sifre:
                s.dosya_coz(Path(yol), sifre=sifre)
        elif secim == "5":
            sifre = input("Şifre: ").strip()
            if sifre:
                n = s.log_dizini_sifrele(Path(__file__).parent.parent / "sites", sifre)
                print(f"[+] {n} dosya şifrelendi.")


if __name__ == "__main__":
    crypto_menusu()
