# Phase 12 Delivery — Dashboard Analytics + Hardening + UAT

| Field | Value |
|---|---|
| **Phase** | 12 |
| **Name** | Dashboard Analytics + Hardening + UAT |
| **Status** | ✅ APPROVED |
| **Based on** | Delivery Plan + Phase 11 APPROVED |
| **Date** | 2026-08-14 |
| **Approved** | 2026-08-14 |

> **Approval Gate Phase 12**  
> Jalankan QC di bawah, lalu balas **APPROVED** / **REVISE** / **REJECTED**.  
> Label Phase/MVP hanya di dokumen — tidak di UI.

---

## 1. Ringkasan Deliverable

1. **Dashboard operasional**
   - KPI: total item, total qty, low stock, open PO, open borrow, overdue
   - Chart mutasi In vs Out (periode 7/14/30/60 hari)
   - Top item bergerak
   - Widget status asset
   - Alert list (low stock, PO pending, opname pending, overdue)
   - Tabel peminjaman overdue (peminjam + client)
   - Filter lokasi + periode
2. **Hardening**
   - Middleware `SecurityHeaders`
   - Throttle login `10/menit`
   - Checklist OWASP: `docs/security/OWASP-CHECKLIST.md`
3. **UAT**
   - Checklist end-to-end: `docs/UAT-CHECKLIST.md`
4. Seeder existing tetap dipakai untuk data demo konsisten

---

## 2. Setup QC

```bash
php artisan migrate
php artisan db:seed
npm run build
```

Password: `Password123!`

---

## 3. Panduan QC Manual

### QC-01 — Dashboard KPI

1. Login `warehouse@inventpro.local` → **Dashboard**.
2. **Expected:** angka KPI terisi dari data seeder (bukan placeholder “—”).
3. Ubah filter lokasi / periode.
4. **Expected:** chart & KPI menyesuaikan.

### QC-02 — Widget & alert

1. Cek Status Asset, Top item, Alert, tabel Overdue.
2. Klik alert/link bila ada.
3. **Expected:** navigasi ke modul terkait.

### QC-03 — Hardening

1. Response header mengandung `X-Frame-Options: SAMEORIGIN` dan `X-Content-Type-Options: nosniff`.
2. Login spam → throttle.
3. Warehouse buka `/admin/users` → 403.

### QC-04 — UAT smoke

1. Ikuti `docs/UAT-CHECKLIST.md` happy path #1–#10 (ringkas).
2. **Expected:** tidak ada blocker kritis.

---

## 4. File utama

| Area | Path |
|---|---|
| Service | `app/Services/DashboardService.php` |
| Controller | `app/Http/Controllers/DashboardController.php` |
| UI | `resources/js/Pages/Dashboard.vue` |
| Headers | `app/Http/Middleware/SecurityHeaders.php` |
| OWASP | `docs/security/OWASP-CHECKLIST.md` |
| UAT | `docs/UAT-CHECKLIST.md` |

---

## 5. Keputusan PO

| Decision | Tanggal | Catatan |
|---|---|---|
| ✅ APPROVED | 2026-08-14 | Disetujui via chat ("approved"). Delivery plan Phase 3–12 selesai. |
