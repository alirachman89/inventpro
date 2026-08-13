# Phase 01 Delivery — Foundation & Design System

| Field | Value |
|---|---|
| **Phase** | 1 |
| **Name** | Foundation & Design System |
| **Status** | ✅ APPROVED |
| **Approved** | 2026-08-13 |
| **Based on** | `02-DELIVERY-PLAN.md` (APPROVED) |
| **Date** | 2026-08-13 |
| **Stack** | Laravel 12 + Inertia + Vue 3 + Tailwind + MySQL UUID |

> **Approval Gate Phase 1**  
> Review hasil coding + jalankan QC manual di bawah.  
> Balas **APPROVED** / **REVISE** / **REJECTED** sebelum Phase 2 dimulai.

---

## 1. Ringkasan Deliverable

Sudah di-coding:

1. Scaffold monolith Laravel + Breeze (Inertia Vue)
2. Design system InventPro (Teal / Slate / Amber, Plus Jakarta Sans)
3. Layout authenticated: sidebar + topbar + mobile drawer
4. Halaman Login (ID) + Logout
5. Dashboard placeholder + demo toast/confirm
6. Komponen toast modern (`vue-sonner`) + Confirm Modal + Alert Banner
7. Trait `HasUuid` + `users.id` UUID
8. Registrasi publik **dinonaktifkan**
9. Login rate-limit (bawaan Breeze: 5 attempts)
10. Seeder user Phase 1
11. README setup Laragon

---

## 2. File / modul utama

| Area | Path |
|---|---|
| UUID trait | `app/Models/Concerns/HasUuid.php` |
| User model | `app/Models/User.php` |
| Users migration | `database/migrations/0001_01_01_000000_create_users_table.php` |
| Seeder | `database/seeders/DatabaseSeeder.php` |
| Routes | `routes/web.php`, `routes/auth.php` |
| Design tokens CSS | `resources/css/app.css` |
| Tailwind palette | `tailwind.config.js` |
| App shell JS | `resources/js/app.js` |
| Layout app | `resources/js/Layouts/AuthenticatedLayout.vue` |
| Layout guest | `resources/js/Layouts/GuestLayout.vue` |
| Login | `resources/js/Pages/Auth/Login.vue` |
| Dashboard | `resources/js/Pages/Dashboard.vue` |
| Toast helper | `resources/js/composables/useToast.js` |
| Confirm helper | `resources/js/composables/useConfirm.js` |
| Confirm UI | `resources/js/Components/ConfirmModal.vue` |
| Alert UI | `resources/js/Components/AlertBanner.vue` |
| README | `README.md` |

---

## 3. Setup untuk QC

```bash
# di folder project
composer install
npm install
npm run build   # atau: npm run dev

# pastikan DB inventpro ada, lalu:
php artisan migrate:fresh --seed
```

URL contoh (Laragon): `http://inventpro.test` atau `http://localhost/inventpro/public`

### Akun QC Phase 1

| Email | Password | Catatan |
|---|---|---|
| `admin@inventpro.local` | `Password123!` | User foundation (RBAC multi-role di Phase 2) |

---

## 4. Color palette & button (referensi QC visual)

| Token | Hex | Penggunaan |
|---|---|---|
| brand-600 | `#0D9488` | Primary button |
| brand-700 | `#0F766E` | Active sidebar / hover kuat |
| warning | `#D97706` | Warning button/toast |
| danger | `#DC2626` | Danger button/toast |
| slate-50 | `#F8FAFC` | App background |

Button class: `.btn-primary`, `.btn-secondary`, `.btn-danger`, `.btn-warning`, `.btn-ghost`

---

## 5. Panduan QC Manual

### QC-01 — Akses & Login sukses

1. Buka URL aplikasi di browser.
2. Pastikan diarahkan ke halaman **Masuk** (`/login`).
3. Pastikan branding **InventPro** terlihat jelas (logo IP + nama).
4. Login dengan:
   - Email: `admin@inventpro.local`
   - Password: `Password123!`
5. Klik **Masuk**.
6. **Expected:** masuk ke `/dashboard`, sidebar kiri tampil, nama user di pojok kanan atas.

### QC-02 — Login gagal & validasi

1. Logout (menu user → **Keluar**).
2. Login dengan password salah.
3. **Expected:** tetap di login, muncul error validasi (bukan blank page).
4. (Opsional) coba gagal > 5 kali.
5. **Expected:** muncul pesan throttle/lock sementara.

### QC-03 — Layout desktop

1. Login sukses, lebar browser ≥ 1024px.
2. **Expected:**
   - Sidebar kiri tetap terlihat
   - Topbar dengan **icon bell Notifikasi** + user menu
   - Content dashboard terbaca
   - Tidak ada teks internal seperti “Phase 1”, “MVP”, atau “phase berikutnya” di layar

### QC-03b — Bell notifikasi (shell UI)

1. Di topbar (desktop atau mobile), pastikan icon **bell** terlihat di kiri avatar user.
2. Klik icon bell.
3. **Expected:** dropdown “Notifikasi” dengan pesan netral “Belum ada notifikasi.” (tanpa label Phase/MVP).
4. *(Fungsi notifikasi penuh menyusul di dokumen Phase 3 — tidak ditampilkan di UI.)*

### QC-04 — Layout mobile / responsive

1. Buka DevTools → mode mobile (contoh 375px) atau gunakan HP.
2. **Expected:**
   - Sidebar tersembunyi
   - Tombol hamburger di kiri atas muncul
3. Klik hamburger.
4. **Expected:** drawer sidebar terbuka + overlay.
5. Klik overlay / pilih menu.
6. **Expected:** drawer tertutup, navigasi berjalan.

### QC-05 — Toast modern

1. Di Dashboard, klik tombol:
   - Toast Success
   - Toast Info
   - Toast Warning
   - Toast Error
2. **Expected:** toast muncul pojok kanan atas, berwarna sesuai status, bisa auto-dismiss / close.

### QC-06 — Confirm modal modern

1. Di Dashboard, klik **Buka Confirm Modal**.
2. **Expected:** modal konfirmasi muncul (bukan `window.confirm` browser).
3. Klik **Batal** → toast info “Dibatalkan”.
4. Buka lagi, klik **Ya, lanjutkan** → toast sukses “Dikonfirmasi”.

### QC-07 — Navigasi Profil & Logout

1. Klik avatar/nama user → **Profil**.
2. **Expected:** halaman profil terbuka dengan layout yang sama.
3. Klik user menu → **Keluar**.
4. **Expected:** kembali ke login; akses `/dashboard` tanpa login di-redirect ke login.

### QC-08 — Registrasi publik tertutup

1. Coba buka `/register`.
2. **Expected:** 404 atau tidak tersedia (route register dihapus).

### QC-09 — UUID user

1. Cek di DB (TablePlus / phpMyAdmin / tinker): tabel `users`, kolom `id`.
2. **Expected:** nilai UUID (bukan integer auto-increment), contoh `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`.

---

## 6. Aturan UI — label internal

Sesuai `docs/UI-CONVENTIONS.md`:

- Label seperti **Phase 1**, **MVP**, **Approval Gate**, dsb. **tidak boleh** muncul di UI.
- Hanya boleh di dokumen delivery/QC.
- Sudah diimplementasikan: sidebar & dashboard dibersihkan dari teks Phase/MVP/roadmap internal.

---

## 7. Out of scope Phase 1 (disengaja)

- RBAC dinamis / multi-role (Phase 2)
- Audit log page (Phase 2)
- Notification bell aktif (Phase 3)
- Approval engine (Phase 3)
- Master data & transaksi inventory (Phase 4+)

---

## 8. Known notes

- Halaman `Welcome` / `Register` Vue masih ada di folder Breeze sisa scaffolding, tetapi route register sudah dinonaktifkan.
- KPI dashboard menampilkan “Belum ada data” (tanpa label phase).
- Tombol contoh toast/confirm di dashboard bersifat pratinjau UI produk (tanpa menyebut Phase/QC di layar).
- Password seeder hanya untuk lokal/dev.

---

## 9. Approval Section

| Role | Decision | Date | Notes |
|---|---|---|---|
| Product Owner | ✅ APPROVED | 2026-08-13 | Chat: "approved" |

Lanjut **Phase 2 — RBAC Dinamis + Superadmin + Audit Log**.
