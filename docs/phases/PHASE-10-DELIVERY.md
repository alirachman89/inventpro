# Phase 10 Delivery — Peminjaman Barang (Karyawan + Client)

| Field | Value |
|---|---|
| **Phase** | 10 |
| **Name** | Peminjaman Barang (Karyawan + Client) |
| **Status** | ✅ APPROVED |
| **Based on** | Delivery Plan + Phase 9 APPROVED |
| **Date** | 2026-08-14 |
| **Approved** | 2026-08-14 |

> **Approval Gate Phase 10**  
> Jalankan QC di bawah, lalu balas **APPROVED** / **REVISE** / **REJECTED**.  
> Label Phase/MVP hanya di dokumen — tidak di UI.

---

## 1. Ringkasan Deliverable

1. Request pinjam dengan **`borrower_user_id`** + **`client_id`** wajib
2. UI label jelas: **Peminjam (Karyawan)** ≠ **Digunakan di Client**
3. Approval dinamis (`document_type=borrow_request`) + konteks di Detail Persetujuan
4. Checkout → asset `borrowed`, holder = peminjam, client = client terpilih
5. Return partial/full → asset `available`, holder & client dikosongkan
6. Flag **Overdue** + filter list (borrower / client / status / overdue)
7. Nomor `BRW-…` (`prefix_borrow`)
8. Permissions: `borrows.view|create|update|submit|checkout|return|delete`
9. Seeder: Frame Scaffold `AST-FRAME-001` → Warehouse User (Karyawan A) → Client A

---

## 2. Alur bisnis

```
Warehouse buat pinjam (peminjam + client + unit asset)
  → Ajukan approval
  → Approver setujui → approved
  → Checkout → asset borrowed (holder + client)
  → Return → asset available (holder/client clear)
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
| Warehouse | `warehouse@inventpro.local` | Buat, ajukan, checkout, return |
| Approver | `approver@inventpro.local` | Setujui pinjam |
| Admin | `admin@inventpro.local` | Full |

---

## 4. Panduan QC Manual (wajib)

### QC-01 — Buat pinjam Scaffold

1. Login warehouse → **Peminjaman** → **Buat pinjam** (atau buka seeder BRW-…).
2. Peminjam = Warehouse User, Client = **CLI-A**, unit = **AST-FRAME-001**.
3. Simpan & **Ajukan approval**.
4. **Expected:** status Menunggu approval; notifikasi ke approver.

### QC-02 — Approve

1. Login approver → **Persetujuan**.
2. **Expected:** ringkasan menampilkan Peminjam vs Client + baris unit; popup sebelum Setujui.
3. Setujui → status **Disetujui — siap checkout**.

### QC-03 — Checkout

1. Login warehouse → **Checkout**.
2. Buka detail asset / baris pinjam.
3. **Expected:** status asset `borrowed`; Holder = Warehouse User; Client = CLI-A.

### QC-04 — Return

1. **Return** unit (kondisi baik).
2. **Expected:** pinjam `returned`; asset `available`; holder & client kosong.

### QC-05 — Filter

1. Filter by borrower / client / overdue.
2. **Expected:** list sesuai filter.

---

## 5. File utama

| Area | Path |
|---|---|
| Migration | `database/migrations/2026_08_14_160000_create_borrow_tables.php` |
| Service | `app/Services/BorrowService.php` |
| Controller | `app/Http/Controllers/Admin/BorrowController.php` |
| UI | `resources/js/Pages/Admin/Borrows/*` |
| Seeder | `database/seeders/BorrowRequestSeeder.php` |

---

## 6. Keputusan PO

| Decision | Tanggal | Catatan |
|---|---|---|
| ✅ APPROVED | 2026-08-14 | Disetujui via chat ("approved"). |
