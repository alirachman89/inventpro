# Phase 02 Delivery — RBAC Dinamis + Superadmin + Audit Log

| Field | Value |
|---|---|
| **Phase** | 2 |
| **Name** | RBAC Dinamis + Superadmin + Audit Log |
| **Status** | ✅ APPROVED |
| **Approved** | 2026-08-13 |
| **Based on** | `02-DELIVERY-PLAN.md` (APPROVED), Phase 1 APPROVED |
| **Date** | 2026-08-13 |

> **Approval Gate Phase 2**  
> Jalankan QC manual di bawah, lalu balas **APPROVED** / **REVISE** / **REJECTED**.  
> Label internal (Phase/MVP) hanya di dokumen ini — tidak di UI.

---

## 1. Ringkasan Deliverable

1. `spatie/laravel-permission` terpasang (UUID-compatible `model_id`)
2. Role & permission dinamis + UI matrix
3. CRUD Pengguna + assign role
4. Akun **superadmin** (Gate bypass, tidak bisa dihapus)
5. Seeder 6 role + 6 user
6. Modul **Audit Log** (list, filter, detail, old/new values)
7. Audit login sukses/gagal, logout, CRUD user/role
8. Menu sidebar mengikuti permission
9. Flash toast sukses/error

---

## 2. File / modul utama

| Area | Path |
|---|---|
| Permission migration | `database/migrations/2026_08_13_094705_create_permission_tables.php` |
| Audit migration | `database/migrations/2026_08_13_100000_create_audit_logs_table.php` |
| Audit model/service | `app/Models/AuditLog.php`, `app/Services/AuditLogger.php` |
| User + HasRoles | `app/Models/User.php` |
| Gate superadmin | `app/Providers/AppServiceProvider.php` |
| Controllers | `app/Http/Controllers/Admin/*` |
| Seeders | `database/seeders/RolesAndPermissionsSeeder.php`, `UserSeeder.php` |
| Vue Users | `resources/js/Pages/Admin/Users/*` |
| Vue Roles | `resources/js/Pages/Admin/Roles/*` |
| Vue Audit | `resources/js/Pages/Admin/AuditLogs/*` |
| Credentials | `docs/LOGIN-CREDENTIALS.md` |

---

## 3. Setup QC

```bash
php artisan migrate:fresh --seed
npm run build
# atau npm run dev
```

Kredensial lengkap: `docs/LOGIN-CREDENTIALS.md`  
Password semua akun: `Password123!`

---

## 4. Permission yang di-seed

`dashboard.view`, `users.*`, `roles.*`, `audit_logs.view`

| Role | Ringkas |
|---|---|
| superadmin | Full (Gate::before) |
| admin | users + roles + audit + dashboard |
| purchasing / warehouse / approver | dashboard |
| viewer | dashboard + audit_logs.view |

---

## 5. Panduan QC Manual

### QC-01 — Login superadmin & menu

1. Logout jika masih login.
2. Login: `superadmin@inventpro.local` / `Password123!`
3. **Expected:** masuk Dashboard.
4. Cek sidebar memuat: Dashboard, Pengguna, Role & Permission, Audit Log, Profil.
5. **Expected:** tidak ada teks “Phase 2” / “MVP” di UI.

### QC-02 — CRUD User

1. Login sebagai superadmin/admin.
2. Buka menu **Pengguna** → **Tambah User**.
3. Isi nama, email unik, password, pilih role `warehouse` → Simpan.
4. **Expected:** muncul di list + toast sukses.
5. Edit user tersebut, ganti nama → Simpan.
6. Hapus user non-superadmin (konfirmasi modal).
7. **Expected:** terhapus; tombol hapus tidak ada pada user superadmin.

### QC-03 — Role & permission matrix

1. Buka **Role & Permission**.
2. Buat role baru (contoh: `qc_temp`) + centang beberapa permission → Simpan.
3. Edit role, ubah permission → Simpan.
4. Coba hapus role `superadmin`.
5. **Expected:** ditolak (error toast / tidak bisa).
6. Hapus `qc_temp` jika tidak dipakai user.

### QC-04 — Proteksi menu by role

1. Logout.
2. Login: `warehouse@inventpro.local` / `Password123!`
3. **Expected:** sidebar hanya Dashboard (+ Profil); tidak ada Pengguna / Role / Audit.
4. Coba akses manual URL `/admin/users`.
5. **Expected:** 403 / ditolak.

### QC-05 — Viewer audit

1. Login: `viewer@inventpro.local` / `Password123!`
2. **Expected:** menu Audit Log terlihat; Pengguna/Role tidak.
3. Buka Audit Log, pastikan ada entri login.
4. Klik **Lihat** pada salah satu log.
5. **Expected:** detail + old/new values (jika ada).

### QC-06 — Audit login gagal

1. Logout.
2. Login dengan password salah (email valid).
3. Login kembali sebagai superadmin.
4. Buka **Audit Log**, filter action `login_failed` / cari deskripsi login gagal.
5. **Expected:** tercatat.

### QC-07 — Superadmin immutable

1. Login superadmin.
2. Coba hapus user `superadmin@inventpro.local` dari UI.
3. **Expected:** tidak bisa dihapus.
4. Edit user superadmin, coba cabut semua role termasuk `superadmin` (jika satu-satunya).
5. **Expected:** ditolak karena minimal satu superadmin harus ada.

### QC-08 — Mobile

1. Lebar ≤ 375px.
2. **Expected:** hamburger, bell notifikasi, avatar tetap ada; menu admin tampil di drawer sesuai permission.

---

## 6. Out of scope Phase 2

- Approval engine & halaman My Approvals (Phase 3)
- Notifikasi backend penuh (Phase 3) — bell shell sudah ada
- Master data inventory (Phase 4+)

---

## 7. Approval Section

| Role | Decision | Date | Notes |
|---|---|---|---|
| Product Owner | ✅ APPROVED | 2026-08-13 | Chat: "approved" |

Lanjut **Phase 3 — Notifications + Dynamic Approval Engine**.
