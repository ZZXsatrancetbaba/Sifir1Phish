# Lab 10 — Raporlama & Düzeltme

**OWASP:** A05/A09 süreç · **Zorluk:** easy · **Puan:** 10
**Öğrenme hedefi:** Bulgu raporu yazmak, risk derecelendirmek, düzeltme ticket'ına çevirmek, yönetici özeti üretmek.

## 📄 Bulgu raporu şablonu
```markdown
# [MG-FIND-###] Kısa bulgu adı
- Etkilenen: modül/dosya/satır (commit linki)
- OWASP: A0x kategori | CWE: nnnn
- Açıklama (1 paragraf, jargon az)
- Tekrarlanabilirlik: adım-adım YALNIZCA lokal lab ortamı için
- Etki: hangi varlık, hangi zarar (C/I/A)
- Olasılık: Low/Med/High (kanıtla)
- Risk skoru: CVSS v3.1 vector (ör. AV:N/AC:L/UI:N/S:U/C:H/I:N/A:N)
- Kanıt: kod parçası + beklenen/gözlenen güvenli davranış
- Öneri: fix diff + tespit kuralı (grep/CI)
- Referans: OWASP/PHP docs (güvenilir kaynak only)
```

## 🎚️ Risk derecelendirme kısayolu
| Etki\Olasılık | Low | Med | High |
|---|---|---|---|
| Yüksek (auth bypass, SQLi) | Orta | Yüksek | Kritik |
| Orta (info leak) | Düşük | Orta | Yüksek |
| Düşük (gürültü) | Bilgi | Düşük | Orta |

## 🎫 Düzeltme ticket formatı
`Title: Fix <bulgu> in <dosya>` / `DoD: test eklendi + grep kuralı CI'da + retest geçti` / `Labels: security, owasp-a03`.

## 🧑‍💼 Executive summary örneği (3 cümle)
> Laboratuva denetiminde 3 bulgu: 1 kritik (parametresiz sorgu), 2 orta (eksik CSP directive, log redaksiyon boşluğu). Toplam düzeltme tahmini 1.5 gün; kritik bulgunun kapatılması ile veri sızıntısı riski elimine olur.

## ✅ Tespit → Rapor → Düzeltme döngüsü
1. Kod incelemesi/bulgular (Lab 02–09 checklist'leri)
2. Rapor (bu şablon) → eğitmene PR comment
3. Fix PR + regression testi
4. Retest + kapanış notu

## 🚩 Bayrak
`MG{f1x-first-r3p0rt-cl34rly}`

## ⚠️ Uyarı
Raporlama, gerçek sistemlerde bulunabilecek zafiyetlerin ifşa/satış kanalı DEĞİLDİR. Sorumlu açıklama (responsible disclosure) süreci SECURITY.md'dedir: özel bildirim → makul süre → koordineli yayın.
