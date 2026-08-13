# Phase 04 Delivery — Master Data Inti (UOM, Kategori, Lokasi, Rak, Settings)

| Field | Value |
|---|---|
| **Phase** | 4 |
| **Name** | Master Data Inti |
| **Status** | ✅ APPROVED |
| **Based on** | Delivery Plan + Phase 3 APPROVED + CR lokasi/rak |
| **Date** | 2026-08-13 |
| **Approved** | 2026-08-13 |

> **Approval Gate Phase 4**  
> Jalankan QC di bawah, lalu balas **APPROVED** / **REVISE** / **REJECTED**.  
> Label Phase/MVP hanya di dokumen — tidak di UI.

---

## 1. Ringkasan Deliverable

1. CRUD **Satuan (UOM)** + seeder standar industri/gudang
2. CRUD **Kategori** barang + seeder awal
3. CRUD **Lokasi / Gudang** (≥2 lokasi seeder)
4. CRUD **Rak** per lokasi (`code`, `name`, **`label`**, `is_default`, `is_active`)
5. Auto-create rak **`GENERAL`** saat lokasi dibuat
6. Rak `GENERAL` **tidak dapat dihapus**; **label** tetap bisa diubah
7. **Pengaturan** perusahaan + prefix nomor dokumen + timezone
8. Permissions: `units.*`, `categories.*`, `locations.*`, `racks.*`, `settings.*`
9. Audit create/update/delete (dan update settings)
10. Menu sidebar: Satuan, Kategori, Lokasi & Rak, Pengaturan

---

## 2. Aturan bisnis kunci (CR)

| Aturan | Perilaku |
|---|---|
| Lokasi baru | Sistem membuat rak `code=GENERAL`, `is_default=true`, label default `Umum` |
| Hapus GENERAL | Ditolak (UI + server) |
| Label rak | Wajib; bebas diubah tanpa mengubah `code` (khusus GENERAL: code terkunci) |
| Kode rak | Unik per lokasi; tidak boleh membuat GENERAL manual |

---

## 3. Setup QC

```bash
php artisan migrate
php artisan db:seed
npm run build
```

Akun: lihat `docs/LOGIN-CREDENTIALS.md` (password `Password123!`)

| Role berguna untuk QC | Email |
|---|---|
| Superadmin / Admin (CRUD penuh) | `superadmin@inventpro.local` / `admin@inventpro.local` |
| Warehouse (view master) | `warehouse@inventpro.local` |
| Viewer (view master) | `viewer@inventpro.local` |

---

## 4. Panduan QC Manual

### QC-01 — Satuan (UOM)

1. Login `admin@inventpro.local`.
2. Buka **Satuan (UOM)** — pastikan seeder (`pcs`, `box`, `kg`, …) ada.
3. Tambah satuan baru, lalu edit / nonaktifkan.
4. **Expected:** list + toast sukses; audit tercatat.

### QC-02 — Kategori

1. Buka **Kategori**.
2. Pastikan seeder (Fastener, Scaffolding, dll.).
3. Tambah / edit / hapus (soft delete) kategori uji.
4. **Expected:** CRUD berjalan.

### QC-03 — Lokasi + rak GENERAL otomatis

1. Buka **Lokasi & Rak**.
2. Pastikan seeder: `GU-01` Gudang Utama, `SITE-B` Gudang Site B.
3. **Tambah Lokasi** baru (contoh: `SITE-C`).
4. Setelah simpan, buka Detail.
5. **Expected:** rak `GENERAL` sudah ada (default), label `Umum`.

### QC-04 — Label rak editable; GENERAL tidak bisa dihapus

1. Di lokasi seeder, edit rak `GENERAL` → ubah **label** (mis. `Umum / Default`).
2. **Expected:** label tersimpan; kode tetap `GENERAL`.
3. Coba hapus rak `GENERAL`.
4. **Expected:** tidak ada aksi hapus / ditolak server.
5. Tambah rak baru (mis. `C-03`, label `Rak Baja Zona C`) → sukses.
6. Hapus rak non-GENERAL → sukses.

### QC-05 — Pengaturan

1. Buka **Pengaturan**.
2. Ubah nama perusahaan / prefix PO → Simpan.
3. Refresh halaman.
4. **Expected:** nilai tersimpan; audit `settings` updated.

### QC-06 — Menu by permission

1. Login `warehouse@inventpro.local`.
2. **Expected:** melihat Satuan / Kategori / Lokasi (view); tidak ada tombol tambah (tanpa create).
3. Login `approver@inventpro.local`.
4. **Expected:** menu master data tidak muncul.

### QC-07 — Bahasa & label internal

1. Tidak ada teks “Phase 4” / “MVP” di UI.
2. Copy & validasi Bahasa Indonesia.

---

## 5. Out of scope

- Item & stok per lokasi/rak (Phase 5)
- Bin/slot di dalam rak
- Konversi antar UOM
- Generate nomor dokumen nyata (hanya simpan prefix)

---

## 6. Approval Section

| Role | Decision | Date | Notes |
|---|---|---|---|
| Product Owner | ✅ APPROVED | 2026-08-13 | Chat: "approved" |

Lanjut **Phase 5 — Stock Management & Asset Status**.
