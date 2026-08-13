# UAT Checklist — InventPro (End-to-End)

Password semua akun: `Password123!`  
Lihat juga: `docs/LOGIN-CREDENTIALS.md`

## Setup

```bash
php artisan migrate
php artisan db:seed
npm run build
```

## Happy path lintas modul

| # | Modul | Langkah singkat | Expected |
|---|---|---|---|
| 1 | Login | `warehouse@inventpro.local` | Dashboard KPI terisi (bukan “—”) |
| 2 | Master | Lokasi GU-01 punya rak GENERAL | Tidak bisa hapus GENERAL |
| 3 | Barang | Item Klem multi posisi | Stok per lokasi/rak terlihat |
| 4 | Vendor/Client | CLI-A ada | Label client ≠ peminjam |
| 5 | PO → Approval → GR | Purchasing ajukan → Approver setujui → Warehouse GR partial | Stok naik; PO partially/received |
| 6 | Mutasi | Stock Out melebihi stok | Ditolak validasi |
| 7 | Opname | Hitung → ajukan selisih → approve → post | Stok menyesuaikan |
| 8 | Pinjam | BRW Scaffold → approve → checkout → return | Asset borrowed lalu available; holder+client jelas |
| 9 | Laporan | Buka stok/mutasi/pinjam; export Excel/PDF (warehouse) | File terunduh; viewer tanpa export |
| 10 | Dashboard | Filter lokasi / 7–30 hari | Chart & KPI berubah; alert actionable |

## Role matrix smoke

| Role | Email | Harus bisa | Tidak boleh |
|---|---|---|---|
| Viewer | `viewer@…` | Dashboard, laporan view | Mutasi create, export |
| Approver | `approver@…` | Persetujuan + popup konfirmasi | Operasional gudang penuh |
| Warehouse | `warehouse@…` | GR, mutasi, opname, pinjam, export | Manage users/roles |
| Purchasing | `purchasing@…` | PO + vendor | Checkout pinjam |
| Admin | `admin@…` | Hampir semua master + ops | — |
| Superadmin | `superadmin@…` | Full | — |

## Security smoke

| # | Cek | Expected |
|---|---|---|
| 1 | POST login berulang cepat | Throttle setelah limit |
| 2 | Response headers | Ada `X-Frame-Options`, `X-Content-Type-Options` |
| 3 | Akses `/admin/users` sebagai warehouse | 403 |
| 4 | Register publik `/register` | Tidak tersedia |

## Sign-off

| Role | Nama | Tanggal | Hasil |
|---|---|---|---|
| Product Owner | | | APPROVED / REVISE / REJECTED |
| QA / Tester | | | |
