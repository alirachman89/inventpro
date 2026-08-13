# Phase 11 Delivery — Laporan Inventory

| Field | Value |
|---|---|
| **Phase** | 11 |
| **Name** | Laporan Inventory |
| **Status** | ✅ APPROVED |
| **Based on** | Delivery Plan + Phase 10 APPROVED |
| **Date** | 2026-08-14 |
| **Approved** | 2026-08-14 |

> **Approval Gate Phase 11**  
> Jalankan QC di bawah, lalu balas **APPROVED** / **REVISE** / **REJECTED**.  
> Label Phase/MVP hanya di dokumen — tidak di UI.

---

## 1. Ringkasan Deliverable

1. Hub **Laporan** dengan 8 laporan:
   - Stok terkini / posisi (lokasi + rak + kategori)
   - Mutasi stok (periode)
   - PO & Receiving
   - Opname & selisih
   - Peminjaman & overdue (filter peminjam/client)
   - Status asset (holder/client/lokasi)
   - Vendor (Should)
   - Client aktifitas pinjam (Should)
2. Filter relevan per laporan
3. Export **Excel** & **PDF** (`reports.export`)
4. Permissions: `reports.view`, `reports.export`  
   - Viewer: view saja  
   - Warehouse / Purchasing / Admin: view + export

---

## 2. Setup QC

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
npm run build
```

Password: `Password123!`

| Role | Email | Fokus |
|---|---|---|
| Viewer | `viewer@inventpro.local` | Lihat laporan, tanpa unduh |
| Warehouse | `warehouse@inventpro.local` | Lihat + Excel/PDF |
| Purchasing | `purchasing@inventpro.local` | PO report + export |

---

## 3. Panduan QC Manual

### QC-01 — Hub laporan

1. Login warehouse → **Laporan**.
2. **Expected:** 8 kartu laporan tampil.

### QC-02 — Stok & mutasi

1. Buka **Stok Terkini / Posisi** — filter lokasi GU-01.
2. Buka **Mutasi Stok** — ada baris ledger.
3. **Expected:** data muncul; filter mengubah hasil.

### QC-03 — PO / Opname / Pinjam / Asset

1. Buka masing-masing laporan; pastikan ada data seeder.
2. Filter client/borrower pada pinjam & asset.

### QC-04 — Export

1. Warehouse: **Unduh Excel** dan **Unduh PDF** dari salah satu laporan.
2. Viewer: tombol unduh tidak muncul.

### QC-05 — Vendor & Client

1. Buka laporan Vendor dan Client.
2. **Expected:** daftar master + hitungan PO / pinjam.

---

## 4. File utama

| Area | Path |
|---|---|
| Service | `app/Services/ReportService.php` |
| Controller | `app/Http/Controllers/Admin/ReportController.php` |
| UI | `resources/js/Pages/Admin/Reports/*` |
| PDF | `resources/views/exports/report-pdf.blade.php` |
| Excel | `app/Exports/SimpleArrayExport.php` |

---

## 5. Keputusan PO

| Decision | Tanggal | Catatan |
|---|---|---|
| ✅ APPROVED | 2026-08-14 | Disetujui via chat ("approved"). |
