# Phase 03 Delivery — Notifications + Dynamic Approval Engine

| Field | Value |
|---|---|
| **Phase** | 3 |
| **Name** | Notifications + Dynamic Approval Engine |
| **Status** | ✅ APPROVED |
| **Based on** | Delivery Plan + Phase 2 APPROVED |
| **Date** | 2026-08-13 |
| **Approved** | 2026-08-13 |

> **Approval Gate Phase 3**  
> Jalankan QC di bawah, lalu balas **APPROVED** / **REVISE** / **REJECTED**.  
> Label Phase/MVP hanya di dokumen — tidak di UI.

---

## 1. Ringkasan Deliverable

1. Engine approval dinamis (workflow, steps, request, action)
2. UI konfigurasi workflow (admin)
3. Halaman **Persetujuan** (pending + pengajuan saya)
4. Approve / Reject + komentar (reject wajib komentar)
5. Bell notifikasi + badge unread + dropdown + halaman Notifikasi
6. Polling unread (~45 detik)
7. Seeder workflow default (Demo, PO, Opname, Borrow, Asset Dispose)
8. Modul **Uji Approval** untuk QC end-to-end
9. Notifikasi terpicu saat submit / hasil approval
10. Audit log untuk workflow update & approve/reject

---

## 2. Penjelasan konsep — Mode & cara kerja Workflow Approval

Bagian ini menjelaskan apa yang dikonfigurasi di menu **Workflow Approval** dan bagaimana itu dipakai di platform (untuk Product Owner & QC).

### 2.1 Apa itu `mode`?

`mode` mengatur aturan **di dalam satu langkah (step)** — kapan langkah itu dianggap selesai.

| Mode | Arti | Contoh |
|---|---|---|
| **any** | Cukup **salah satu** user dengan role approver di step tersebut yang setuju | Role `approver` ada 3 orang → 1 orang approve → step selesai |
| **all** | **Semua** user yang punya role tersebut harus setuju | Role `approver` ada 3 orang → ketiga-tiganya harus approve baru step selesai |

**Catatan penting:**

- `mode` **bukan** pengatur urutan antar-langkah.
- Urutan antar-langkah sudah diatur oleh **Langkah 1 → Langkah 2 → …** (berjalan sequential).
- Setelah step aktif selesai sesuai `mode`-nya, baru lanjut ke step berikutnya (jika ada).

Contoh 2 langkah:

```
Langkah 1: role = approver, mode = any
  → salah satu Approver setuju

Langkah 2: role = admin, mode = any
  → salah satu Admin setuju
  → dokumen fully approved
```

Jika Langkah 1 memakai `mode = all` dan ada 2 user role `approver`, keduanya harus approve dulu sebelum lanjut ke Langkah 2.

### 2.2 Bagaimana Workflow Approval diterapkan di platform?

Workflow **tidak dijalankan manual tiap transaksi**. Admin hanya mengonfigurasi sekali (atau saat aturan berubah). Modul bisnis memanggil **Approval Engine** saat dokumen diajukan.

Alur generik:

```
1. Admin mengatur Workflow
   (tipe dokumen + langkah + role approver + mode + aktif/nonaktif)

2. User mengajukan dokumen
   → sistem membaca workflow untuk document_type tersebut

3. Engine membuat Approval Request di langkah 1

4. User dengan role langkah aktif:
   - mendapat notifikasi (bell)
   - melihat item di menu Persetujuan

5. Approve / Reject sesuai mode langkah aktif
   - Reject → dokumen rejected + notifikasi ke pengaju (stop)
   - Approve (step selesai) → lanjut step berikutnya atau fully approved

6. Semua langkah selesai → status dokumen approved
```

### 2.3 Mapping tipe dokumen → modul

| `document_type` di Workflow | Dipakai saat |
|---|---|
| `approval_demo` | Menu **Uji Approval** (sudah ada di Phase 3, untuk QC) |
| `purchase_order` | PO di-submit (phase Purchase) |
| `stock_opname` | Opname siap posting (phase Opname) |
| `borrow_request` | Peminjaman diajukan (phase Pinjam) |
| `asset_dispose` | Ajuan dispose / lost asset |

Intinya: konfigurasi di **Workflow Approval** = “aturan main”.  
Modul bisnis nanti hanya bilang ke engine: *submit dokumen tipe X dengan ID ini* — engine yang mengurus request, notifikasi, dan status.

### 2.4 Cara mencoba sekarang (Phase 3)

1. Login superadmin → **Workflow Approval** → atur **Demo Approval** (step + role + mode).
2. Login warehouse → **Uji Approval** → buat dokumen & ajukan.
3. Login approver → bell / **Persetujuan** → Setujui atau Tolak.

Ini simulasi yang sama dengan yang nanti dipakai PO / Opname / Pinjam.

### 2.5 Jika workflow nonaktif / tanpa step

Jika workflow untuk tipe dokumen **nonaktif** (atau tidak punya step aktif), dokumen dapat **langsung approved** (auto) sesuai desain — berguna bila tipe tertentu tidak memerlukan approval.

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
| Admin / Warehouse (ajukan) | `warehouse@inventpro.local` atau `admin@inventpro.local` |
| Approver (setujui/tolak) | `approver@inventpro.local` |
| Superadmin (atur workflow) | `superadmin@inventpro.local` |

---

## 4. Panduan QC Manual

### QC-01 — Bell & halaman notifikasi

1. Login sebagai `approver@inventpro.local`.
2. Pastikan icon **bell** di topbar terlihat (mobile & desktop).
3. Buka menu **Notifikasi** via “Lihat semua” di dropdown bell (atau `/notifications`).
4. **Expected:** halaman notifikasi dengan filter Semua / Belum dibaca.

### QC-02 — Buat dokumen uji & submit

1. Logout, login `warehouse@inventpro.local`.
2. Buka menu **Uji Approval**.
3. Isi judul (contoh: `Scaffold untuk Client A`), centang “Ajukan approval sekarang” → **Buat Dokumen Uji**.
4. **Expected:** status menjadi `pending_approval`.
5. Buka **Persetujuan** → bagian “Pengajuan saya” menampilkan dokumen.

### QC-03 — Approver menerima notifikasi & approve

1. Logout, login `approver@inventpro.local`.
2. Cek bell: badge unread / item “Menunggu persetujuan Anda”.
3. Buka **Persetujuan** → **Menunggu keputusan saya** → **Proses**.
4. Klik **Setujui**.
5. **Expected:** status `approved`; pengaju mendapat notifikasi hasil.

### QC-04 — Reject wajib komentar

1. Sebagai warehouse, buat dokumen uji baru lagi.
2. Login approver, buka dokumen, klik **Tolak** tanpa komentar.
3. **Expected:** error validasi komentar wajib.
4. Isi komentar → Tolak.
5. **Expected:** status `rejected`; notifikasi ke pengaju.

### QC-05 — Workflow 2 langkah

1. Login `superadmin@inventpro.local`.
2. Buka **Workflow Approval** → Atur **Demo Approval**.
3. Jadikan 2 langkah:
   - Langkah 1: role `approver`, mode `any`
   - Langkah 2: role `admin`, mode `any`
4. Simpan.
5. Login warehouse, ajukan dokumen uji baru.
6. Approver setujui langkah 1 → masih `pending`.
7. Login admin, setujui langkah 2 → `approved`.

### QC-06 — Nonaktifkan workflow

1. Superadmin nonaktifkan workflow Demo (`Workflow aktif` unchecked) + tetap minimal 1 step, simpan.
2. Ajukan dokumen uji baru.
3. **Expected:** dokumen langsung `approved` (auto, karena workflow nonaktif / tanpa engine aktif).

> Setelah QC, aktifkan kembali workflow Demo jika perlu.

### QC-07 — Menu by permission

1. Login `viewer@inventpro.local`.
2. **Expected:** tidak ada menu Workflow Approval / Uji Approval; bell tetap ada.

### QC-08 — Bahasa & label internal

1. Pastikan tidak ada teks “Phase 3” / “MVP” di UI.
2. Error validasi tampil Bahasa Indonesia.

---

## 5. Out of scope

- Integrasi PO/Opname/Borrow nyata (phase modul bisnis)
- WebSocket realtime (masih polling)
- Kondisi amount-based routing

---

## 6. Approval Section

| Role | Decision | Date | Notes |
|---|---|---|---|
| Product Owner | ✅ APPROVED | 2026-08-13 | Chat: "approved phase 03" |

Lanjut **Phase 4 — Master Data Inti**.
