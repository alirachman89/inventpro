# InventPro — Login Credentials (Development)

| Field | Value |
|---|---|
| **Environment** | Local / Development only |
| **Last seeded** | 2026-08-13 (Phase 2 RBAC) |
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
| 2 | Admin InventPro | `admin@inventpro.local` | `Password123!` | `admin` | Users, Roles, Audit Log, Dashboard |
| 3 | Purchasing User | `purchasing@inventpro.local` | `Password123!` | `purchasing` | Dashboard |
| 4 | Warehouse User | `warehouse@inventpro.local` | `Password123!` | `warehouse` | Dashboard |
| 5 | Approver User | `approver@inventpro.local` | `Password123!` | `approver` | Dashboard |
| 6 | Viewer User | `viewer@inventpro.local` | `Password123!` | `viewer` | Dashboard, Audit Log (lihat) |

### Salin cepat — Superadmin

```
Email    : superadmin@inventpro.local
Password : Password123!
```

### Salin cepat — Viewer (read-only audit)

```
Email    : viewer@inventpro.local
Password : Password123!
```

---

## Reset database + seeder ulang

```bash
php artisan migrate:fresh --seed
```

---

## Catatan

- Registrasi publik (`/register`) dinonaktifkan.
- Primary key user memakai UUID.
- Label internal seperti “Phase 2 / MVP” hanya di dokumen — tidak di UI. Lihat `docs/UI-CONVENTIONS.md`.
- QC Phase 2: `docs/phases/PHASE-02-DELIVERY.md`.
