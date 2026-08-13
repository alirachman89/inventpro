# InventPro — Delivery Plan (Phase Roadmap)

| Field | Value |
|---|---|
| **Product Name** | InventPro |
| **Document Type** | Delivery Plan / Phase Roadmap |
| **Version** | 1.1.1 |
| **Status** | ✅ APPROVED (termasuk CR v1.1.1) |
| **Based on** | `01-BRD-PRD.md` **v1.6.1** (CR APPROVED) |
| **Created** | 2026-08-13 |
| **Approved** | 2026-08-13 (v1.0.0); CR v1.1.1 2026-08-13 |
| **Last Updated** | 2026-08-13 |
| **Change note** | v1.1.1 — Bin=Could; stok wajib rak/`GENERAL`; rak punya **label** |

> **Approval Gate #2 — SELESAI** (termasuk CR lokasi+rak).  
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
| **Multi-lokasi** | **Wajib** — minimal 2 lokasi di seeder |
| **Rak** | **Wajib** — setiap stok punya `rack_id` |
| **Rak GENERAL** | Auto-create per lokasi; tidak boleh dihapus; default mutasi |
| **Label rak** | Field `label` wajib & editable per rak (tampil di UI/filter) |
| **Bin/slot** | **Could** (backlog) |
| Penelusuran posisi | User lihat item (mis. Klem) di lokasi + rak (code/label) |

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
| **4** | Master Data Inti | UOM, kategori, **lokasi/gudang + rak**, settings perusahaan | Phase 2 |
| **5** | Stock & Asset Status | Item, **stok per lokasi+rak**, posisi barang, asset status | Phase 4 |
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

### Phase 4 — Master Data Inti (UOM, Kategori, Lokasi, Rak, Settings)

**Tujuan:** Data master yang dibutuhkan stock & transaksi, termasuk hierarki penyimpanan.

| Deliverable | Keterangan |
|---|---|
| CRUD UOM + seeder standar | §5.3.0 |
| CRUD Kategori | |
| CRUD **Lokasi/Gudang** (min. 2 lokasi seeder) | §5.3.0b LOC-* |
| CRUD **Rak** per lokasi (code, name, **label**, is_default) | Wajib |
| Auto-create rak **`GENERAL`** saat lokasi dibuat | Must |
| Rak `GENERAL` tidak bisa dihapus; **label** bisa diubah | Must |
| Settings perusahaan + prefix nomor dokumen | |
| Permissions: `locations.*`, `racks.*`, dst. | |
| Audit create/update/deactivate | |
| Seeder: tiap lokasi punya `GENERAL` + rak lain ber-label | Must |

**QC focus:**  
- Buat lokasi → pastikan rak `GENERAL` otomatis muncul  
- Edit **label** rak (bukan hanya code)  
- Coba hapus `GENERAL` → ditolak  
- Nonaktif lokasi/rak tidak bisa dipilih di form stok (Phase 5)  
- Tidak ada teks Phase/MVP di UI  

**Phase Delivery file:** `docs/phases/PHASE-04-DELIVERY.md`

---

### Phase 5 — Stock Management & Asset Status (+ Posisi Lokasi/Rak)

**Tujuan:** Item, stok per **lokasi + rak**, penelusuran posisi barang, asset serialized + status.

| Deliverable | Keterangan |
|---|---|
| CRUD Item (consumable/asset, UOM, kategori, min stock) | |
| `item_stocks` per **item + location + rack** | LOC-04 |
| Detail item: breakdown **Lokasi → Rak (code + label) → Qty** | LOC-05; contoh Klem |
| Filter/cari stok by item, lokasi, rak code/label | LOC-06 |
| Mutasi/default rak = `GENERAL` jika tidak dipilih khusus | LOC-14 |
| Asset units (tag/serial, status, holder, client, location, **rack**) | |
| Aksi status: maintenance, damaged, quarantine, dll. | |
| Histori status asset | |
| Stock ledger foundation (catat location_id + rack_id) | |
| Seeder: item **Klem** (dan lainnya) tersebar multi lokasi/rak | |
| Low stock flag di list | |

**QC focus (wajib):**  
1. Login admin/warehouse  
2. Buka item **Klem** (atau buat item serupa)  
3. **Expected:** terlihat stok per lokasi & rak **plus label** (mis. Gudang Utama / B-02 / Rak Besi Zona B)  
4. Ada qty di rak `GENERAL` bila dipakai  
5. Filter lokasi/rak (code atau label) menampilkan posisi yang benar  
6. Mobile: breakdown posisi tetap terbaca  

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

**Tujuan:** Mutasi non-PO terkendali **dengan lokasi + rak**.

| Deliverable | Keterangan |
|---|---|
| Stock In manual (pilih lokasi + rak tujuan) | |
| Stock Out (lokasi + rak sumber; issue terkait client bila perlu) | |
| Transfer antar lokasi/**rak** (`in_transit` untuk asset) | |
| Validasi stok cukup pada posisi sumber | |
| Ledger + audit + notifikasi relevan | |
| Seeder sample movements multi posisi | |

**QC focus:** out melebihi stok di rak ditolak; transfer Gudang A/Rak-1 → Gudang B/Rak-2; issue dengan client.

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
| Stok terkini, **posisi lokasi/rak**, mutasi, PO/GR, opname, peminjaman, status asset | |
| Filter client/borrower/lokasi/**rak**/periode | |
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
| 8 Movements (lokasi+rak sumber/tujuan) | M |
| 9 Opname (per lokasi / rak) | M–L |
| 10 Borrow | L |
| 11 Reports (+ posisi lokasi/rak) | M–L |
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
| Stok tanpa rak membingungkan | Wajib rack_id; sediakan rak `GENERAL` bila perlu |

---

## 10. Out of Scope Delivery Plan ini (tetap backlog BRD)

- UOM conversion matrix  
- 2FA  
- Client portal login  
- Project/Site hierarchy di bawah Client  
- WebSocket realtime (Reverb)  
- Native mobile app  
- AI forecasting  
- **Bin/slot di dalam rak** (Could; lokasi+rak sudah Must sejak CR v1.1)

---

## 11. Approval Section

| Role | Name | Decision | Date | Note |
|---|---|---|---|---|
| Product Owner | Product Owner | ✅ APPROVED | 2026-08-13 | v1.0.0 base |
| Product Owner | Product Owner | ✅ APPROVED CR v1.1.1 | 2026-08-13 | Chat: "Approved untuk CR" |

Phase 4+ memakai scope lokasi + rak `GENERAL` + label. Menunggu approval **Phase 3** sebelum lanjut coding Phase 4.

---

## 12. Document Control

| Version | Date | Notes |
|---|---|---|
| 1.0.0-DRAFT | 2026-08-13 | Initial delivery plan based on BRD v1.5.0 approved |
| 1.0.0 | 2026-08-13 | APPROVED by Product Owner |
| 1.1.0 | 2026-08-13 | CR: Phase 4/5 multi-lokasi + rak wajib; QC posisi barang |
| 1.1.1 | 2026-08-13 | CR refine: Bin=Could; GENERAL must; rack label field |
