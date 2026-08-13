# InventPro — Delivery Plan (Phase Roadmap)

| Field | Value |
|---|---|
| **Product Name** | InventPro |
| **Document Type** | Delivery Plan / Phase Roadmap |
| **Version** | 1.0.0 |
| **Status** | ✅ APPROVED |
| **Based on** | `01-BRD-PRD.md` v1.5.0 (**APPROVED** 2026-08-13) |
| **Created** | 2026-08-13 |
| **Approved** | 2026-08-13 |
| **Last Updated** | 2026-08-13 |

> **Approval Gate #2 — SELESAI**  
> Delivery Plan **APPROVED** 2026-08-13. Coding Phase 1 dapat dimulai.  
> Tiap phase: coding → `docs/phases/PHASE-XX-DELIVERY.md` + QC manual → approval Anda → phase berikutnya.

---

## 1. Tujuan Dokumen

Menjabarkan **fase pengerjaan**, urutan dependensi, deliverable per fase, kriteria selesai (DoD), dan tata kelola approval + QC — mengacu BRD/PRD yang sudah disetujui.

---

## 2. Keputusan Teknis Terkunci (dari BRD usulan + approval)

| Topik | Keputusan |
|---|---|
| Repo | Monolith |
| Stack | Laravel + **Inertia.js + Vue 3 + Tailwind CSS** |
| DB | MySQL, PK **UUID** |
| RBAC | `spatie/laravel-permission` + UI dinamis; akun **superadmin** |
| UI | Design system Teal + Slate + Amber; Plus Jakarta Sans |
| Toast/Alert | Modern toast + confirm modal (bukan `alert`/`confirm` native) |
| Approval | Engine **dinamis** multi-step, assign by role, mode any/all |
| Notifikasi | Bell + dropdown + halaman notifikasi (polling MVP) |
| Audit | `audit_logs` append-only + halaman filter |
| Client vs Peminjam | **Dipisah**: `borrower_user_id` + `client_id` wajib di peminjaman |
| UOM | Master data; conversion antar UOM = backlog |
| Bahasa UI | Bahasa Indonesia |
| 2FA | Backlog |
| Project/Site di bawah Client | Backlog (Client + alamat dulu) |

---

## 3. Tata Kelola Delivery

```
BRD/PRD ✅
  → Delivery Plan (dokumen ini) ⏳
       → APPROVAL Anda
            → Phase N Coding
                 → Phase N Delivery Doc + QC Guide
                      → APPROVAL Anda
                           → Phase N+1 ...
```

### Aturan

1. Tidak mulai coding sebelum Delivery Plan di-approve.  
2. Tidak mulai Phase N+1 sebelum Phase N (kode + dokumen + QC) di-approve.  
3. Setiap phase menghasilkan:
   - Kode yang bisa dijalankan / di-QC
   - `docs/phases/PHASE-XX-DELIVERY.md` (apa yang sudah di-coding)
   - Panduan QC manual langkah-demi-langkah
   - Update seeder/permission terkait phase
4. Change Request setelah approval = update dokumen + re-approve scope terdampak.

### Definition of Done (umum tiap phase)

- [ ] Fitur phase sesuai scope di bawah  
- [ ] Permission RBAC terpasang di route/UI  
- [ ] Audit log untuk aksi kritis phase (mulai Phase 2+)  
- [ ] Seeder terkait (jika ada)  
- [ ] Responsive (spot-check mobile + desktop)  
- [ ] Toast/alert modern dipakai  
- [ ] Dokumen Phase Delivery + QC guide siap  
- [ ] Tidak ada regresi kasar pada phase sebelumnya (smoke)  
- [ ] **UI bebas label internal** (Phase N, MVP, Approval Gate, dsb.) — lihat `docs/UI-CONVENTIONS.md`

---

## 4. Ringkasan Fase

| Phase | Nama | Fokus utama | Dependensi |
|---|---|---|---|
| **1** | Foundation & Design System | Scaffold Laravel/Inertia/Vue/Tailwind, layout, tokens, auth dasar | — |
| **2** | RBAC Dinamis + Superadmin + Audit Log | Roles/permissions UI, seed users, audit page | Phase 1 |
| **3** | Notifications + Approval Engine | Bell, halaman notifikasi, workflow konfigurasi, My Approvals | Phase 2 |
| **4** | Master Data Inti | UOM, kategori, lokasi/gudang, settings perusahaan | Phase 2 |
| **5** | Stock & Asset Status | Item, stok, serialized asset, status lifecycle | Phase 4 |
| **6** | Vendor & Client | Master vendor + client | Phase 2 |
| **7** | Purchase & Receiving | PO + approval + GR → stock in | Phase 3, 5, 6 |
| **8** | Stock In / Out / Transfer | Mutasi manual + ledger | Phase 3, 5, 6 |
| **9** | Stock Opname | Sesi opname + approval + posting | Phase 3, 5 |
| **10** | Peminjaman (Karyawan + Client) | Borrow flow lengkap | Phase 3, 5, 6 |
| **11** | Laporan | Semua laporan Must + export | Phase 7–10 |
| **12** | Dashboard Analytics + Polish | KPI/charts, hardening OWASP, UAT seeder penuh | Phase 11 |

**Catatan urutan:** Phase 4 dan 6 bisa dikerjakan sequential setelah 2–3; Phase 6 tidak bergantung stock, tapi Phase 7+ membutuhkan keduanya. Usulan eksekusi linear 1→12 agar approval sederhana.

---

## 5. Detail per Phase

### Phase 1 — Foundation & Design System

**Tujuan:** Aplikasi monolit siap dikembangkan dengan shell UI modern & auth login.

| Deliverable | Keterangan |
|---|---|
| Laravel project + Vite + Inertia + Vue 3 + Tailwind | Struktur monolith |
| UUID trait/convention untuk model | Siap dipakai migration berikutnya |
| Design tokens (warna, tipografi, button) | Sesuai BRD §8 |
| Layout app: sidebar, topbar, content, mobile drawer | Responsive |
| Halaman Login / Logout | Session auth |
| Komponen Toast + Confirm Modal + Alert inline | Modern feedback |
| Halaman kosong Dashboard placeholder | Shell saja |
| README setup lokal (Laragon) | Cara jalanin project |
| `.env.example` aman | Tanpa secret produksi |

**QC focus (tinggi):** login/logout, layout mobile/desktop, toast demo.

**Phase Delivery file:** `docs/phases/PHASE-01-DELIVERY.md`

---

### Phase 2 — RBAC Dinamis + Superadmin + Audit Log

**Tujuan:** Akses terkontrol + jejak audit dasar.

| Deliverable | Keterangan |
|---|---|
| Spatie Permission (atau setara) terpasang | Roles & permissions |
| UI kelola Role + Permission matrix | Dinamis |
| UI kelola User + assign role | CRUD user (admin) |
| Superadmin immutable (tidak bisa dihapus/di-downgrade sembarangan) | BRAC-04 |
| Middleware/policy di route contoh | Proteksi |
| Seeder roles + users default | superadmin, admin, purchasing, warehouse, approver, viewer |
| Modul Audit Log: tulis + halaman list/detail/filter | AUD-* |
| Login/logout & perubahan RBAC tercatat audit | |

**QC focus:** login tiap role; menu ter-hide; superadmin full; viewer terbatas; audit terlihat.

**Phase Delivery file:** `docs/phases/PHASE-02-DELIVERY.md`

---

### Phase 3 — Notifications + Dynamic Approval Engine

**Tujuan:** Infrastruktur approval & notifikasi untuk semua modul transaksi berikutnya.

| Deliverable | Keterangan |
|---|---|
| Tabel workflow, steps, requests, actions | Engine |
| UI Settings: konfigurasi workflow per document type | Admin |
| Halaman My Approvals | Approve/Reject + komentar |
| Bell + badge + dropdown + halaman Notifikasi | NTF-* |
| Polling unread count | MVP |
| Seeder workflow default (PO, Opname, Borrow, Asset Dispose) | |
| Integrasi “dummy document” atau internal test hook untuk QC engine | Opsional helper |
| Notifikasi terpicu saat request approval dibuat/selesai | |
| Audit untuk ubah workflow & aksi approve/reject | |

**QC focus:** set 2-step workflow; submit; approve step1→step2; reject + notif; bell & halaman notifikasi.

**Phase Delivery file:** `docs/phases/PHASE-03-DELIVERY.md`

---

### Phase 4 — Master Data Inti (UOM, Kategori, Lokasi, Settings)

**Tujuan:** Data master yang dibutuhkan stock & transaksi.

| Deliverable | Keterangan |
|---|---|
| CRUD UOM + seeder standar | §5.3.0 |
| CRUD Kategori | |
| CRUD Lokasi/Gudang (minimal 2 lokasi di seeder) | |
| Settings perusahaan + prefix nomor dokumen | |
| Permissions per master | |
| Audit create/update/deactivate | |

**QC focus:** CRUD tiap master; nonaktif UOM tidak muncul di dropdown (siapkan form item stub/preview bila item belum ada — atau validasi di Phase 5 dengan re-QC).

**Phase Delivery file:** `docs/phases/PHASE-04-DELIVERY.md`

---

### Phase 5 — Stock Management & Asset Status

**Tujuan:** Item, stok per lokasi, asset serialized + status.

| Deliverable | Keterangan |
|---|---|
| CRUD Item (consumable/asset, UOM, kategori, min stock) | |
| `item_stocks` per lokasi | |
| Asset units (tag/serial, status, holder, client) | |
| Aksi status: maintenance, damaged, quarantine, dll. | |
| Histori status asset | |
| Stock ledger foundation (siap dipakai mutasi) | |
| Seeder items + asset units berbagai status | |
| Low stock flag di list | |

**QC focus:** buat item; lihat stok; buat asset unit; ubah status; filter status; mobile list.

**Phase Delivery file:** `docs/phases/PHASE-05-DELIVERY.md`

---

### Phase 6 — Vendor & Client Management

**Tujuan:** Master pihak inbound & konteks pemakaian outbound/pinjam.

| Deliverable | Keterangan |
|---|---|
| CRUD Vendor | |
| CRUD Client (type termasuk company/project_site/…) | |
| Detail client: siap menampilkan histori (kosong dulu / setelah pinjam) | |
| Seeder vendor + client (termasuk Client A) | |
| Permissions + audit | |

**QC focus:** CRUD; nonaktif tidak bisa dipilih; bedakan jelas label Vendor vs Client di menu.

**Phase Delivery file:** `docs/phases/PHASE-06-DELIVERY.md`

---

### Phase 7 — Purchase Management & Goods Receipt

**Tujuan:** PO → approval dinamis → GR → stock in + ledger + asset unit bila serialized.

| Deliverable | Keterangan |
|---|---|
| CRUD PO + line items | |
| Submit → approval engine | |
| GR partial/full | |
| Update stok + ledger | |
| Nomor dokumen otomatis | |
| Notifikasi approval PO | |
| Seeder sample PO berbagai status | |

**QC focus:** buat PO → approve sebagai approver → GR → cek stok naik; reject flow; permission purchasing vs warehouse.

**Phase Delivery file:** `docs/phases/PHASE-07-DELIVERY.md`

---

### Phase 8 — Stock In / Out / Transfer

**Tujuan:** Mutasi non-PO terkendali.

| Deliverable | Keterangan |
|---|---|
| Stock In manual | |
| Stock Out (termasuk issue terkait client bila perlu) | |
| Transfer antar lokasi (`in_transit` untuk asset) | |
| Validasi stok cukup | |
| Ledger + audit + notifikasi relevan | |
| Seeder sample movements | |

**QC focus:** out melebihi stok ditolak; transfer lokasi A→B; issue dengan client.

**Phase Delivery file:** `docs/phases/PHASE-08-DELIVERY.md`

---

### Phase 9 — Stock Opname

**Tujuan:** Perhitungan fisik + approval selisih + posting.

| Deliverable | Keterangan |
|---|---|
| Sesi opname per lokasi | |
| Input qty fisik (mobile-friendly) | |
| Hitung selisih | |
| Approval dinamis sebelum post | |
| Posting adjustment ke ledger | |
| Laporan hasil opname (basic di phase ini / full di 11) | |
| Seeder 1 sesi contoh | |

**QC focus:** buat sesi → input → approve → post → stok berubah sesuai selisih.

**Phase Delivery file:** `docs/phases/PHASE-09-DELIVERY.md`

---

### Phase 10 — Peminjaman Barang (Karyawan + Client)

**Tujuan:** Skenario inti: Scaffold dipinjam Karyawan A untuk Client A.

| Deliverable | Keterangan |
|---|---|
| Request pinjam dengan **borrower_user_id** + **client_id** wajib | |
| Approval dinamis | |
| Checkout / return partial-full | |
| Update asset: holder + client; status borrowed | |
| Overdue flag + notifikasi | |
| List filter by borrower & client | |
| Seeder: Scaffold Holding → Karyawan A → Client A | |

**QC focus (wajib):**  
a. Login admin/warehouse  
b. Buat pinjam Scaffold, peminjam Karyawan A, client Client A  
c. Approve → checkout  
d. Cek detail asset/client menampilkan keduanya  
e. Return → status available  

**Phase Delivery file:** `docs/phases/PHASE-10-DELIVERY.md`

---

### Phase 11 — Laporan Inventory

**Tujuan:** Laporan Must + export.

| Deliverable | Keterangan |
|---|---|
| Stok terkini, mutasi, PO/GR, opname, peminjaman, status asset | |
| Filter client/borrower/lokasi/periode | |
| Laporan vendor/client (Should bila waktu cukup) | |
| Export Excel & PDF | |
| Permissions `reports.view` / `reports.export` | |

**QC focus:** tiap laporan Must produce data; export sukses; viewer bisa lihat, warehouse sesuai permission.

**Phase Delivery file:** `docs/phases/PHASE-11-DELIVERY.md`

---

### Phase 12 — Dashboard Analytics + Hardening + UAT

**Tujuan:** Analitik operasional + penguatan keamanan + kesiapan demo/UAT.

| Deliverable | Keterangan |
|---|---|
| Dashboard KPI + charts + alerts | DASH-* |
| Widget status asset + overdue borrow (peminjam + client) | |
| Review OWASP checklist praktis | Headers, throttle, mass assignment, permission gaps |
| Seeder penuh end-to-end konsisten | |
| Perapihan UI empty/loading states | |
| Smoke regression lintas modul | |
| Dokumen UAT ringkas / checklist final | `docs/phases/PHASE-12-DELIVERY.md` |

**QC focus:** dashboard akurat vs data seeder; login harden; full happy-path lintas modul.

---

## 6. Artefak Dokumen per Phase

Setelah coding tiap phase, dibuat:

```
docs/phases/PHASE-XX-DELIVERY.md
```

Isi wajib:

1. Ringkasan deliverable yang sudah di-coding  
2. Daftar file/modul utama  
3. Permission & seeder terkait  
4. Cara setup/migrate/seed (jika ada)  
5. **Panduan QC Manual** (langkah a, b, c, … per role)  
6. Known issues / out of scope phase  
7. Bagian Approval phase  

Contoh struktur QC:

```
a. Login sebagai superadmin@inventpro.local
b. Buka menu ...
c. Lakukan ...
d. Expected result: ...
```

---

## 7. Akun Seeder (untuk semua QC)

| Email | Role | Password (dev) |
|---|---|---|
| `superadmin@inventpro.local` | superadmin | `Password123!` |
| `admin@inventpro.local` | admin | `Password123!` |
| `purchasing@inventpro.local` | purchasing | `Password123!` |
| `warehouse@inventpro.local` | warehouse | `Password123!` |
| `approver@inventpro.local` | approver | `Password123!` |
| `viewer@inventpro.local` | viewer | `Password123!` |

> Hanya untuk lokal/dev. Wajib diganti di lingkungan nyata.

---

## 8. Estimasi Relatif (indikatif, bukan komitmen kalender)

| Phase | Ukuran relatif |
|---|---|
| 1 Foundation | M |
| 2 RBAC + Audit | M |
| 3 Notif + Approval | L |
| 4 Master Data | S–M |
| 5 Stock & Asset | L |
| 6 Vendor & Client | S |
| 7 Purchase & GR | L |
| 8 Movements | M |
| 9 Opname | M–L |
| 10 Borrow | L |
| 11 Reports | M–L |
| 12 Dashboard + Polish | M |

S = kecil, M = sedang, L = besar.

---

## 9. Risiko Delivery

| Risiko | Mitigasi |
|---|---|
| Approval engine terlalu kompleks di awal | MVP: sequential + role + any/all; kondisi amount = Could |
| Regresi stok antar phase | Ledger tunggal + transaksi DB + QC wajib tiap phase stok |
| Scope laporan membengkak | Phase 11 prioritas Must dulu |
| Mobile table padat | Card/list pattern sejak Phase 1 layout |

---

## 10. Out of Scope Delivery Plan ini (tetap backlog BRD)

- UOM conversion matrix  
- 2FA  
- Client portal login  
- Project/Site hierarchy di bawah Client  
- WebSocket realtime (Reverb)  
- Native mobile app  
- AI forecasting  

---

## 11. Approval Section

| Role | Name | Decision | Date | Note |
|---|---|---|---|---|
| Product Owner | Product Owner | ✅ APPROVED | 2026-08-13 | Chat: "approved" |

Langkah aktif: coding **Phase 1** → `docs/phases/PHASE-01-DELIVERY.md` → approval Phase 1.

---

## 12. Document Control

| Version | Date | Notes |
|---|---|---|
| 1.0.0-DRAFT | 2026-08-13 | Initial delivery plan based on BRD v1.5.0 approved |
| 1.0.0 | 2026-08-13 | APPROVED by Product Owner |
