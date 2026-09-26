# -*- coding: utf-8 -*-
# =============================================================================
# modules/session.py — Oturum Kaydet/Yükle
# =============================================================================
# KESİNLİKLE EĞİTİM AMAÇLIDIR
# =============================================================================

import os
import json
import time
from pathlib import Path
from typing import Optional, Dict, List


class Oturum:
    def __init__(self, oturum_dizini: Optional[Path] = None):
        self.oturum_dizini = oturum_dizini or Path(__file__).parent.parent / "sessions"
        self.oturum_dizini.mkdir(exist_ok=True)

    def kaydet(self, isim: Optional[str] = None, veri: Optional[Dict] = None) -> Path:
        if isim is None:
            isim = f"oturum_{time.strftime('%Y%m%d_%H%M%S')}"
        if veri is None:
            veri = {}
        veri["kayit_zamani"] = time.strftime("%Y-%m-%d %H:%M:%S")
        veri["uyari"] = "KESİNLİKLE EĞİTİM AMAÇLIDIR"

        hedef = self.oturum_dizini / f"{isim}.json"
        with open(hedef, "w", encoding="utf-8") as f:
            json.dump(veri, f, indent=2, ensure_ascii=False)
        print(f"[+] Oturum kaydedildi: {hedef}")
        return hedef

    def yukle(self, isim: str) -> Optional[Dict]:
        if not isim.endswith(".json"):
            isim += ".json"
        yol = self.oturum_dizini / isim
        if not yol.exists():
            print(f"[!] Oturum bulunamadı: {yol}")
            return None
        try:
            with open(yol, "r", encoding="utf-8") as f:
                return json.load(f)
        except Exception as e:
            print(f"[!] Okuma hatası: {e}")
            return None

    def listele(self) -> List[Dict]:
        sonuclar = []
        for dosya in sorted(self.oturum_dizini.glob("*.json"), key=lambda p: -p.stat().st_mtime):
            try:
                with open(dosya, "r", encoding="utf-8") as f:
                    data = json.load(f)
                sonuclar.append({
                    "isim": dosya.stem,
                    "yol": str(dosya),
                    "zaman": data.get("kayit_zamani", "?"),
                    "site": data.get("site", "?"),
                })
            except Exception:
                continue
        return sonuclar

    def sil(self, isim: str) -> bool:
        if not isim.endswith(".json"):
            isim += ".json"
        yol = self.oturum_dizini / isim
        if yol.exists():
            yol.unlink()
            print(f"[+] Oturum silindi: {yol}")
            return True
        print(f"[!] Bulunamadı: {yol}")
        return False


def oturum_menusu():
    o = Oturum()
    while True:
        print()
        print("╔══════════════════════════════════════════════╗")
        print("║          Oturum Yönetimi                     ║")
        print("║          KESİNLİKLE EĞİTİM AMAÇLIDIR        ║")
        print("╚══════════════════════════════════════════════╝")
        print()
        print("  [1] Oturumları listele")
        print("  [2] Oturum yükle")
        print("  [3] Oturum sil")
        print("  [0] Geri")
        print()
        try:
            s = input("Seçim: ").strip()
        except (EOFError, KeyboardInterrupt):
            return

        if s == "0":
            return
        elif s == "1":
            liste = o.listele()
            if not liste:
                print("[i] Kayıtlı oturum yok.")
            else:
                for i, ot in enumerate(liste, 1):
                    print(f"  [{i}] {ot['isim']} — {ot['zaman']} — {ot['site']}")
        elif s == "2":
            isim = input("Oturum adı: ").strip()
            if isim:
                data = o.yukle(isim)
                if data:
                    print(json.dumps(data, indent=2, ensure_ascii=False)[:1500])
        elif s == "3":
            isim = input("Silinecek oturum: ").strip()
            if isim:
                o.sil(isim)


if __name__ == "__main__":
    oturum_menusu()
