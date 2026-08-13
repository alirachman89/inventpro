# InventPro — Login Credentials (Development)

| Field | Value |
|---|---|
| **Environment** | Local / Development only |
| **Last seeded** | 2026-08-14 (Phase 9 Stock Opname) |
| **Source** | `database/seeders/UserSeeder.php` + `RolesAndPermissionsSeeder.php` |

> **Peringatan keamanan**  
> Dokumen ini hanya untuk lokal/QC.  
> Jangan commit password produksi. Ganti semua password sebelum deploy ke staging/production.

---

## Cara login

1. Buka aplikasi (contoh: `http://inventpro.test`).
2. Masuk ke halaman **Masuk** (`/login`).
3. Gunakan email + password di tabel bawah.
4. Klik **Masuk**.

---

## Akun RBAC (aktif)

Password semua akun dev:

```
Password123!
```

| No | Nama | Email (username) | Password | Role | Akses utama |
|---|---|---|---|---|---|
| 1 | Super Admin | `superadmin@inventpro.local` | `Password123!` | `superadmin` | Full akses (bypass permission) |
| 2 | Admin InventPro | `admin@inventpro.local` | `Password123!` | `admin` | Full master: Vendor, Client, Barang, Settings, Users, Approval |
| 3 | Purchasing User | `purchasing@inventpro.local` | `Password123!` | `purchasing` | CRUD Vendor + Purchase Order, view Client/Barang |
| 4 | Warehouse User | `warehouse@inventpro.local` | `Password123!` | `warehouse` | CRUD Barang/Stok, Mutasi, Opname, Goods Receipt, view PO |
| 5 | Approver User | `approver@inventpro.local` | `Password123!` | `approver` | Dashboard, Persetujuan |
| 6 | Viewer User | `viewer@inventpro.local` | `Password123!` | `viewer` | Dashboard, Audit Log, view master + barang |

### Salin cepat — Superadmin

```
Email    : superadmin@inventpro.local
Password : Password123!
```

### Salin cepat — Admin (QC master data)

```
Email    : admin@inventpro.local
Password : Password123!
```

---

## Reset database + seeder ulang

```bash
php artisan migrate:fresh --seed
```

Atau tanpa wipe (jika hanya migrasi baru):

```bash
php artisan migrate
php artisan db:seed
```

---

## Catatan

- Registrasi publik (`/register`) dinonaktifkan.
- Primary key user memakai UUID.
- Label internal seperti “Phase / MVP” hanya di dokumen — tidak di UI. Lihat `docs/UI-CONVENTIONS.md`.
- QC Phase 7: `docs/phases/PHASE-07-DELIVERY.md`.
- QC Phase 8: `docs/phases/PHASE-08-DELIVERY.md`.
- QC Phase 9: `docs/phases/PHASE-09-DELIVERY.md`.
