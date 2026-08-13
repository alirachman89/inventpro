# Phase 08 Delivery — Stock In / Out / Transfer

| Field | Value |
|---|---|
| **Phase** | 8 |
| **Name** | Stock In / Out / Transfer |
| **Status** | ✅ APPROVED |
| **Based on** | Delivery Plan + Phase 7 APPROVED |
| **Date** | 2026-08-14 |
| **Approved** | 2026-08-14 |

> **Approval Gate Phase 8**  
> Jalankan QC di bawah, lalu balas **APPROVED** / **REVISE** / **REJECTED**.  
> Label Phase/MVP hanya di dokumen — tidak di UI.

---

## 1. Ringkasan Deliverable

1. **Mutasi Stok** (In / Out / Transfer) dengan header + baris
2. Setiap baris wajib **lokasi + rak** (default GENERAL bila tidak dipilih)
3. Stock Out: reason `issue_to_client` wajib `client_id`
4. Validasi stok cukup di posisi sumber (out & transfer)
5. Transfer consumable: ledger `transfer_out` + `transfer_in`
6. Transfer asset (opsional via `asset_unit_id`): status `in_transit` lalu update lokasi/rak
7. Nomor dokumen otomatis (`prefix_movement` / default `MOV`)
8. Permissions: `stock_movements.view`, `stock_movements.create`
9. Seeder: 1 In, 1 Out(+Client A), 1 Transfer GU-01/A-01 → SITE-B/RACK-01

---

## 2. Alur bisnis

```
Warehouse buat mutasi
  → In: stok naik di lokasi/rak tujuan
  → Out: stok turun di lokasi/rak sumber (+ client bila issue_to_client)
  → Transfer: stok turun sumber + naik tujuan
  → Ledger + audit tercatat
```

---

## 3. Setup QC

```bash
php artisan migrate
php artisan db:seed
npm run build
```

Password: `Password123!`

| Role | Email | Fokus |
|---|---|---|
| Warehouse | `warehouse@inventpro.local` | Buat & lihat mutasi |
| Admin | `admin@inventpro.local` | Full akses |
| Viewer | `viewer@inventpro.local` | View saja |

---

## 4. Panduan QC Manual

### QC-01 — Stock In

1. Login warehouse → **Mutasi Stok** → **Stock In**.
2. Pilih item **Klem**, qty, lokasi/rak tujuan (mis. GU-01 / GENERAL).
3. Simpan.
4. **Expected:** nomor MOV-…; stok di posisi tujuan naik; ledger `stock_in`.

### QC-02 — Stock Out ditolak jika melebihi stok

1. **Stock Out** dari rak yang stoknya kecil, isi qty lebih besar dari stok tersedia.
2. **Expected:** error validasi; mutasi tidak tersimpan.

### QC-03 — Issue ke Client

1. Stock Out, reason **Issue ke Client**, pilih **CLI-A**, sumber GU-01 / A-01, qty valid.
2. **Expected:** mutasi tersimpan dengan client; stok sumber turun.

### QC-04 — Transfer antar lokasi/rak

1. **Transfer**: dari GU-01 / A-01 ke SITE-B / RACK-01, qty valid.
2. **Expected:** stok A-01 turun, RACK-01 naik; ledger transfer_out + transfer_in.

### QC-05 — Seeder sample

1. Setelah seed, buka **Mutasi Stok**.
2. **Expected:** ada 3 sample (In, Out+Client, Transfer).

### QC-06 — Permission

1. Login viewer → Mutasi Stok terlihat, tombol buat tidak ada.
2. Login warehouse → tombol Stock In / Out / Transfer tersedia.

---

## 5. File utama

| Area | Path |
|---|---|
| Migration | `database/migrations/2026_08_14_120000_create_stock_movement_tables.php` |
| Service | `app/Services/StockMovementService.php` |
| Controller | `app/Http/Controllers/Admin/StockMovementController.php` |
| UI | `resources/js/Pages/Admin/StockMovements/*` |
| Seeder | `database/seeders/StockMovementSeeder.php` |

---

## 6. Keputusan PO

| Decision | Tanggal | Catatan |
|---|---|---|
| ✅ APPROVED | 2026-08-14 | Disetujui via chat ("approved"). |
