# Phase 05 Delivery — Stock Management & Asset Status

| Field | Value |
|---|---|
| **Phase** | 5 |
| **Name** | Stock Management & Asset Status (+ Posisi Lokasi/Rak) |
| **Status** | ✅ APPROVED |
| **Based on** | Delivery Plan + Phase 4 APPROVED + CR lokasi/rak |
| **Date** | 2026-08-13 |
| **Approved** | 2026-08-13 |

> **Approval Gate Phase 5**  
> Jalankan QC di bawah, lalu balas **APPROVED** / **REVISE** / **REJECTED**.  
> Label Phase/MVP hanya di dokumen — tidak di UI.

---

## 1. Ringkasan Deliverable

1. CRUD **Barang** (`consumable` / `asset`, UOM, kategori, min stock)
2. `item_stocks` keyed by **item + lokasi + rak** (+ kondisi)
3. Detail barang: breakdown **Lokasi → Rak (code + label) → Qty**
4. Filter/cari stok by lokasi & rak (code/label)
5. Default rak = `GENERAL` bila rak tidak dipilih (penyesuaian stok / unit asset)
6. Penyesuaian stok consumable + **stock ledger** foundation
7. Unit asset (tag/serial, status, lokasi, rak) + aksi ubah status
8. Riwayat status asset (immutable)
9. Seeder **Klem** multi lokasi/rak + contoh asset Frame Scaffold
10. Flag **stok rendah** di list/detail
11. Permissions `items.*`, `item_stocks.*`, `asset_units.*`, `stock_ledgers.view`

---

## 2. Data seeder QC (Klem)

| Item | Lokasi | Rak | Label | Qty |
|---|---|---|---|---|
| Klem (`KLEM-001`) | Gudang Utama | B-02 | Rak Besi Zona B | 120 |
| Klem | Gudang Utama | A-01 | Rak Besi Zona A | 30 |
| Klem | Gudang Utama | GENERAL | Umum | 5 |
| Klem | Gudang Site B | RACK-01 | Rak Outdoor Site B | 15 |

Min stock Klem = **50** → total available 170 (tidak low). Turunkan qty / naikkan min untuk uji flag.

---

## 3. Setup QC

```bash
php artisan migrate
php artisan db:seed
npm run build
```

Akun: `docs/LOGIN-CREDENTIALS.md` — password `Password123!`

| Role | Email |
|---|---|
| Admin / Warehouse | `admin@inventpro.local` / `warehouse@inventpro.local` |

---

## 4. Panduan QC Manual

### QC-01 — List barang & stok rendah

1. Login `warehouse@inventpro.local`.
2. Buka menu **Barang**.
3. Pastikan **Klem** muncul beserta qty available.
4. Edit min stock Klem jadi `200` → **Expected:** flag “Stok rendah”.

### QC-02 — Breakdown posisi Klem (wajib)

1. Buka detail **Klem**.
2. **Expected:** tabel posisi menampilkan lokasi, kode rak, **label rak**, qty.
3. Pastikan ada baris `GENERAL` / Umum dengan qty 5.
4. Pastikan ada `B-02` / Rak Besi Zona B dengan qty 120.

### QC-03 — Filter lokasi / rak

1. Di detail Klem, filter lokasi = Gudang Utama.
2. Filter rak = `Besi` atau `B-02`.
3. **Expected:** hanya posisi yang cocok.
4. Ulangi dari list Barang (filter lokasi/rak).

### QC-04 — Penyesuaian stok + default GENERAL

1. Di detail Klem, sesuaikan stok: pilih lokasi SITE-B, **kosongkan rak** (GENERAL), qty `+10`.
2. **Expected:** posisi GENERAL Site B bertambah; ledger tercatat.

### QC-05 — Unit asset & status

1. Buka barang **Frame Scaffold**.
2. Lihat unit asset seeder / tambah unit baru.
3. Ubah status ke **Perawatan** / **Rusak** / **Karantina**.
4. **Expected:** status berubah + riwayat tercatat.

### QC-06 — Permission

1. Login `approver@inventpro.local` → menu **Barang** tidak muncul.
2. Login `viewer@inventpro.local` → bisa lihat, tidak bisa adjust/create.

### QC-07 — Label internal

1. Tidak ada teks “Phase 5” / “MVP” di UI.

---

## 5. Out of scope

- Mutasi in/out/transfer penuh (phase Stock Movement)
- Vendor / Client master (Phase 6) — `current_client_id` masih nullable tanpa FK UI
- Approval dispose asset terintegrasi workflow
- Foto item / barcode scanner

---

## 6. Approval Section

| Role | Decision | Date | Notes |
|---|---|---|---|
| Product Owner | ✅ APPROVED | 2026-08-13 | Chat: "approved" |

Lanjut **Phase 6 — Vendor & Client Management**.
