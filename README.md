# InventPro

Platform inventory warehouse (Laravel + Inertia + Vue 3 + Tailwind + MySQL UUID).

## Stack

- Laravel 12 (monolith)
- Inertia.js + Vue 3 + Vite
- Tailwind CSS (Teal / Slate / Amber design system)
- MySQL (UUID primary keys)
- vue-sonner (toast) + custom confirm modal

## Setup (Laragon)

1. Pastikan virtual host mengarah ke `public/` (contoh: `http://inventpro.test`).
2. Buat database MySQL `inventpro`.
3. Salin env:

```bash
cp .env.example .env
php artisan key:generate
```

4. Sesuaikan `.env`:

```env
APP_NAME=InventPro
APP_URL=http://inventpro.test
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventpro
DB_USERNAME=root
DB_PASSWORD=
```

5. Install & build:

```bash
composer install
npm install
npm run build
# atau untuk development:
npm run dev
```

6. Migrate + seed:

```bash
php artisan migrate --seed
```

## Akun login (dev)

Lihat daftar lengkap di `docs/LOGIN-CREDENTIALS.md`.

| Email | Password | Role |
|---|---|---|
| `superadmin@inventpro.local` | `Password123!` | superadmin |
| `admin@inventpro.local` | `Password123!` | admin |

> Hanya untuk lokal/dev. Wajib diganti di produksi.

## Dokumentasi

- BRD/PRD: `docs/01-BRD-PRD.md` (APPROVED)
- Delivery Plan: `docs/02-DELIVERY-PLAN.md` (APPROVED)
- Phase 1 Delivery + QC: `docs/phases/PHASE-01-DELIVERY.md`

## Catatan keamanan Phase 1

- Registrasi publik dinonaktifkan
- Login rate-limit (5 percobaan)
- CSRF & session Laravel aktif
- UUID untuk `users.id`
