# Phase 09 Delivery — Stock Opname

| Field | Value |
|---|---|
| **Phase** | 9 |
| **Name** | Stock Opname |
| **Status** | ⏳ PENDING QC (REVISE applied) |
| **Based on** | Delivery Plan + Phase 8 APPROVED |
| **Date** | 2026-08-14 |
| **REVISE** | 2026-08-14 — Konteks dokumen di Detail Persetujuan |

> **Approval Gate Phase 9**  
> Jalankan QC di bawah, lalu balas **APPROVED** / **REVISE** / **REJECTED**.  
> Label Phase/MVP hanya di dokumen — tidak di UI.

---

## 1. Ringkasan Deliverable

1. Sesi opname **per lokasi** (nomor `OPN-…` via `prefix_opname`)
2. Generate daftar item consumable + rak di lokasi (qty sistem)
3. Input **qty fisik** (UI mobile-friendly + tabel desktop)
4. Hitung **selisih** otomatis (`qty_counted − qty_system`)
5. Ajukan → **Approval Engine** (`document_type=stock_opname`) bila ada selisih
6. Tanpa selisih → sesi langsung **posted** (tidak perlu approval)
7. Setelah approve → **Posting** ke ledger (`opname_adjustment`) + update stok
8. Permissions: `stock_opnames.view|create|update|submit|post|delete`
9. Seeder: 1 sesi draft GU-01 (satu baris contoh selisih)
10. **REVISE:** Detail Persetujuan menampilkan ringkasan + baris selisih + tautan buka dokumen (juga untuk PO)
11. **REVISE:** Popup konfirmasi sebelum Setujui / Tolak di Detail Persetujuan

---

## 2. Alur bisnis

```
Warehouse buat sesi (lokasi)
  → Isi qty fisik semua baris → Simpan
  → Ajukan
       ├─ tanpa selisih → posted
       └─ ada selisih → submitted → Approver setujui → approved
  → Warehouse Posting → stok & ledger berubah
```

Reject mengembalikan ke `rejected` (bisa edit & ajukan ulang).

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
| Warehouse | `warehouse@inventpro.local` | Buat, hitung, ajukan, posting |
| Approver | `approver@inventpro.local` | Setujui selisih |
| Viewer | `viewer@inventpro.local` | View saja |

---

## 4. Panduan QC Manual

### QC-01 — Buat sesi & input

1. Login warehouse → **Stock Opname** → **Buat sesi**.
2. Pilih **GU-01**, simpan.
3. Isi qty fisik semua baris (ubah beberapa agar ada selisih) → **Simpan qty fisik**.
4. **Expected:** selisih tampil; status Draft.

### QC-02 — Ajukan + approve

1. **Ajukan approval**.
2. Login approver → **Persetujuan** → buka OPN.
3. **Expected:** terlihat jenis Stock Opname, ringkasan lokasi/selisih, tabel baris berselisih, tombol **Buka detail Stock Opname**.
4. Setujui.
5. Login warehouse, buka sesi → status **Disetujui — siap posting**.

### QC-03 — Posting → stok berubah

1. Catat qty Klem di rak yang punya selisih.
2. **Posting ke stok**.
3. **Expected:** status Posted; qty on-hand = qty fisik; ledger `opname_adjustment`.

### QC-04 — Tanpa selisih

1. Buat sesi baru, isi qty fisik = qty sistem semua baris → Ajukan.
2. **Expected:** langsung Posted tanpa antrean approval.

### QC-05 — Seeder

1. Setelah seed, ada 1 sesi draft GU-01.
2. **Expected:** minimal satu baris sudah punya contoh selisih (qty belum lengkap → belum bisa ajukan sampai semua diisi).

### QC-06 — Permission

1. Viewer: lihat daftar, tanpa tombol buat/ajukan/post.
2. Warehouse: tombol operasional tersedia.

---

## 5. File utama

| Area | Path |
|---|---|
| Migration | `database/migrations/2026_08_14_140000_create_stock_opname_tables.php` |
| Service | `app/Services/StockOpnameService.php` |
| Controller | `app/Http/Controllers/Admin/StockOpnameController.php` |
| UI | `resources/js/Pages/Admin/StockOpnames/*` |
| Seeder | `database/seeders/StockOpnameSeeder.php` |

---

## 6. Keputusan PO

| Decision | Tanggal | Catatan |
|---|---|---|
| ⏳ PENDING | — | Menunggu QC |
