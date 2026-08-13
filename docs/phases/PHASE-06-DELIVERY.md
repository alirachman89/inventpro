# Phase 06 Delivery — Vendor & Client Management

| Field | Value |
|---|---|
| **Phase** | 6 |
| **Name** | Vendor & Client Management |
| **Status** | ✅ APPROVED |
| **Based on** | Delivery Plan + Phase 5 APPROVED |
| **Date** | 2026-08-13 |
| **Approved** | 2026-08-14 |

> **Approval Gate Phase 6**  
> Jalankan QC di bawah, lalu balas **APPROVED** / **REVISE** / **REJECTED**.  
> Label Phase/MVP hanya di dokumen — tidak di UI.

---

## 1. Ringkasan Deliverable

1. CRUD **Vendor** (pemasok inbound) + seeder
2. CRUD **Client** (konteks pemakaian / proyek / site) + seeder termasuk **Client A**
3. Detail vendor: siap histori PO (kosong dulu)
4. Detail client: slot peminjaman aktif/histori (kosong dulu) + daftar unit asset yang menunjuk client
5. Menu terpisah jelas: **Vendor** vs **Client**
6. Permissions `vendors.*`, `clients.*` + audit
7. Relasi `asset_units.current_client_id` → `clients` (FK)
8. UI asset: **Pemegang (karyawan)** vs **Dipakai di Client** terpisah
9. Seeder contoh: Frame `AST-FRAME-003` → Client A + pemegang warehouse user

---

## 2. Konsep bisnis (dikunci)

| Peran | Arti | Menu |
|---|---|---|
| Vendor | Pemasok barang masuk | **Vendor** |
| Client | Tempat barang digunakan (proyek/site) | **Client** |
| Peminjam | Karyawan/user penanggung jawab | (modul Pinjam nanti) |

Contoh: Scaffold dipinjam **Karyawan A** untuk dipakai di **Client A**.

---

## 3. Setup QC

```bash
php artisan migrate
php artisan db:seed
npm run build
```

Password: `Password123!` — lihat `docs/LOGIN-CREDENTIALS.md`

| Role | Email |
|---|---|
| Admin | `admin@inventpro.local` |
| Purchasing (CRUD vendor) | `purchasing@inventpro.local` |
| Warehouse (view vendor/client) | `warehouse@inventpro.local` |

---

## 4. Panduan QC Manual

### QC-01 — Menu terpisah

1. Login admin.
2. **Expected:** sidebar punya **Vendor** dan **Client** sebagai dua menu berbeda.

### QC-02 — CRUD Vendor

1. Buka **Vendor** — pastikan seeder (min. PT Scaffoldindo, dll.).
2. Tambah / edit / nonaktifkan vendor uji.
3. Buka Detail — histori PO masih kosong (placeholder).

### QC-03 — CRUD Client + Client A

1. Buka **Client** — pastikan **CLI-A / Client A** ada.
2. Baca banner: Client ≠ Peminjam ≠ Vendor.
3. Filter tipe / status.
4. Edit Client A; nonaktifkan client uji → tidak muncul di dropdown asset (hanya `is_active`).

### QC-04 — Detail Client & asset

1. Buka detail **Client A**.
2. **Expected:** unit asset `AST-FRAME-003` muncul (dipakai di Client A).
3. Slot peminjaman aktif/histori masih placeholder.

### QC-05 — Asset: pemegang vs client

1. Buka Barang → Frame Scaffold → `AST-FRAME-003`.
2. **Expected:**
   - Pemegang (karyawan): nama user warehouse (contoh)
   - Dipakai di Client: `CLI-A — Client A`

### QC-06 — Permission

1. `purchasing@…` → CRUD Vendor; lihat Client.
2. `approver@…` → tidak ada menu Vendor/Client.
3. `viewer@…` → view saja.

### QC-07 — Label internal

1. Tidak ada teks “Phase 6” / “MVP” di UI.

---

## 5. Out of scope

- Modul Purchase / GR (Phase 7)
- Modul Peminjaman penuh (Phase 10)
- Portal login Client
- Project/Site hierarki di bawah Client

---

## 6. Approval Section

| Role | Decision | Date | Notes |
|---|---|---|---|
| Product Owner | ✅ APPROVED | 2026-08-14 | Chat: "approved" |

Lanjut **Phase 7 — Purchase Management & Goods Receipt**.
