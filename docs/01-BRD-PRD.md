# InventPro — Business Requirements Document (BRD) & Product Requirements Document (PRD)

| Field | Value |
|---|---|
| **Product Name** | InventPro |
| **Document Type** | BRD + PRD (Combined) |
| **Version** | 1.5.0 |
| **Status** | ✅ APPROVED |
| **Owner** | Inventory Warehouse Manager / Product Owner |
| **Authors** | Senior Full Stack Developer, Senior UI/UX |
| **Created** | 2026-08-13 |
| **Approved** | 2026-08-13 |
| **Change note** | v1.5 — Client = lokasi/proyek pemakaian; peminjam = karyawan (dipisah) |
| **Tech Stack** | Laravel + Vue (Monolith), MySQL (UUID), RBAC dinamis, OWASP |

> **Approval Gate #1 — SELESAI**  
> Dokumen ini **APPROVED** pada 2026-08-13. Open questions §15 mengikuti **usulan default** kecuali diubah kemudian via Change Request.  
> Langkah berikutnya: `docs/02-DELIVERY-PLAN.md`.

---

## 1. Executive Summary

InventPro adalah platform inventory warehouse terpadu untuk mengelola stok, vendor, pembelian, stock opname, peminjaman barang, pergerakan stok (in/out), pelaporan, dan dashboard analitik — dengan keamanan berbasis RBAC dinamis dan standar OWASP.

Platform dibangun sebagai **monolith repository** (Laravel backend + Vue frontend) agar deployment, autentikasi, dan konsistensi domain tetap sederhana untuk fase awal sampai mature.

### 1.1 Tujuan Bisnis

1. Menyatukan proses inventory yang biasanya terpisah (stok, pembelian, opname, pinjam, laporan) dalam satu sistem.
2. Meningkatkan akurasi stok real-time dan audit trail.
3. Mempercepat keputusan operasional lewat dashboard & laporan.
4. Mengontrol akses secara granular (RBAC dinamis) termasuk akun Super Admin.
5. Memenuhi praktik keamanan aplikasi web modern (OWASP).

### 1.2 Success Metrics (KPI)

| KPI | Target Awal |
|---|---|
| Akurasi stok vs opname | ≥ 98% setelah 2 siklus opname |
| Waktu proses stock in/out | ≤ 2 menit / transaksi (UI) |
| Waktu generate laporan standar | ≤ 5 detik (dataset wajar) |
| Coverage role permission kritis | 100% endpoint terproteksi |
| Mobile usability (core flows) | Usable di ≥ 320px width |

---

## 2. Problem Statement

Tanpa sistem terpusat, warehouse sering mengalami:

- Stok tidak sinkron antara gudang, pembelian, dan peminjaman.
- Vendor & PO sulit dilacak sampai barang masuk.
- Stock opname manual rentan selisih tanpa audit trail.
- Laporan tersebar di spreadsheet; analitik lambat.
- Hak akses tidak terkontrol (risiko data leakage / operasi ilegal).

InventPro menyelesaikan masalah tersebut dengan modul terintegrasi, ledger stok, dan RBAC.

---

## 3. Scope

### 3.1 In Scope (MVP → Full)

| # | Modul | Ringkasan |
|---|---|---|
| 1 | Stock Management | Master barang, kategori, **satuan/UOM**, lokasi/gudang, stok per lokasi, min/max, barcode/SKU, **status barang/asset** |
| 1b | Asset / Item Status | Lifecycle & kondisi barang (available, borrowed, maintenance, damaged, disposed, dll.) + histori status |
| 1c | Master Satuan (UOM) | Data master Unit of Measure: kode, nama, tipe, status; dipakai di item, PO, mutasi, laporan |
| 2 | Vendor Management | Master vendor (pemasok), kontak, status, histori transaksi |
| 2b | **Client Management** | Master client/proyek/site pemakaian — di mana barang dipakai (bukan selalu = peminjam) |
| 3 | Purchase Management | PR/PO, approval PO (opsional), receiving (GR), update stok |
| 4 | Stock Opname | Sesi opname, hitung fisik, selisih, adjustment approval |
| 5 | Peminjaman Barang | Pinjam oleh **karyawan/user**, untuk digunakan di **Client** (contoh: Scaffold → Karyawan A → Client A) |
| 6 | Stock In / Out | Transaksi masuk/keluar non-PO (adjustment, transfer, issue ke client, return) |
| 7 | Laporan Inventory | Stok, mutasi, PO, vendor, **client**, opname, pinjam, aging, valuation |
| 8 | Dashboard Analytics | KPI, chart, alert stok rendah, overdue pinjam |
| 9 | Supporting Features | Auth, RBAC dinamis, settings, seeder, UI system |
| 10 | **Dynamic Approval** | Workflow approval bisa dikonfigurasi admin per jenis dokumen (step, approver role/user, kondisi) |
| 11 | **In-App Notification** | Bell icon + badge unread + halaman notifikasi penuh |
| 12 | **Audit Log** | Trail audit immutable untuk aksi kritis & perubahan data |

### 3.2 Out of Scope (fase awal — dicatat untuk backlog)

- Multi-company / multi-tenant SaaS billing
- Integrasi marketplace / e-commerce outbound
- Mobile native app (hanya responsive web)
- Barcode hardware SDK khusus (cukup input/scan browser)
- Accounting full ledger (hanya valuation sederhana)
- AI forecasting demand (fase lanjutan)

### 3.3 Assumptions

- 1 organisasi / 1 instance aplikasi (single tenant).
- User mengakses via browser modern (Chrome/Edge/Firefox/Safari).
- MySQL tersedia (Laragon/production).
- Bahasa UI utama: **Bahasa Indonesia** (EN labels opsional nanti).

### 3.4 Constraints

- Monolith repo (bukan microservices).
- Primary key semua tabel bisnis: **UUID**.
- Harus mobile-friendly.
- Keamanan mengacu OWASP ASVS / Top 10 (praktis).

---

## 4. Stakeholders & Personas

| Persona | Kebutuhan utama |
|---|---|
| **Super Admin** | Full control sistem, RBAC, settings, seed/maintenance |
| **Admin Warehouse** | Operasional penuh stok, opname, in/out, laporan |
| **Purchasing** | Vendor, PR/PO, receiving |
| **Warehouse Staff** | Stock in/out, picking pinjam, input opname; pilih client saat issue/pinjam |
| **Admin / Master Data** | Client & vendor master, UOM, kategori, lokasi |
| **Approver / Manager** | Approve PO, opname adjustment, peminjaman |
| **Auditor / Viewer** | Lihat laporan & audit, tanpa ubah data |

---

## 5. Functional Requirements

### 5.1 Authentication & Session

| ID | Requirement | Priority |
|---|---|---|
| AUTH-01 | Login email/username + password | Must |
| AUTH-02 | Logout, session timeout, remember-me opsional (aman) | Must |
| AUTH-03 | Reset password (email token) | Should |
| AUTH-04 | Force change password (opsional policy) | Could |
| AUTH-05 | Lockout setelah N gagal login | Must |

### 5.2 RBAC Dinamis

| ID | Requirement | Priority |
|---|---|---|
| RBAC-01 | Role CRUD dinamis | Must |
| RBAC-02 | Permission granular (modul.action) | Must |
| RBAC-03 | Assign permission ke role; assign role ke user | Must |
| RBAC-04 | Akun **Super Admin** immutable / bypass penuh | Must |
| RBAC-05 | Middleware/policy guard di API & UI menu | Must |
| RBAC-06 | Seeder role & user default | Must |
| RBAC-07 | UI matrix permission (checklist) | Should |

**Default Roles (seeder):**

| Role | Keterangan |
|---|---|
| `superadmin` | Full access, tidak bisa dihapus |
| `admin` | Operasional + master hampir penuh |
| `purchasing` | Vendor + Purchase + receiving |
| `warehouse` | Stok, in/out, opname input, pinjam fulfill |
| `approver` | Approval PO / opname / pinjam |
| `viewer` | Read-only laporan & dashboard |

### 5.3 Stock Management

| ID | Requirement | Priority |
|---|---|---|
| STK-01 | CRUD Item (nama, SKU, barcode, kategori, **uom_id**, deskripsi, foto) | Must |
| STK-02 | CRUD Kategori (master terpisah) | Must |
| STK-02b | **CRUD Master Satuan / UOM** (lihat §5.3.0) | Must |
| STK-03 | Multi-lokasi / gudang / rak (opsional rak fase 2) | Must |
| STK-04 | Stok per item per lokasi (on-hand, reserved, available) | Must |
| STK-05 | Min stock / reorder point + alert | Must |
| STK-06 | Soft delete + status aktif/nonaktif (master) | Must |
| STK-07 | History mutasi per item | Must |
| STK-08 | Import/export Excel (fase lanjutan) | Could |
| STK-09 | Tipe item: `consumable` (habis pakai) vs `asset` (tracked) | Must |
| STK-10 | Lihat **status operasional** barang/asset di list, detail, dan filter | Must |
| STK-11 | Item wajib terhubung ke UOM aktif dari master; qty selalu dalam base UOM item | Must |

### 5.3.0 Master Satuan / UOM (Unit of Measure)

Satuan **bukan** free-text di form item. Wajib ada **data master** sendiri agar konsisten di stok, PO, opname, pinjam, dan laporan.

#### Field master `units` (UOM)

| Field | Keterangan | Wajib |
|---|---|---|
| `id` | UUID | Ya |
| `code` | Kode unik singkat (pcs, box, kg, ltr, m, unit) | Ya |
| `name` | Nama tampilan (Pieces, Box, Kilogram, …) | Ya |
| `symbol` | Simbol opsional (kg, L, m) | Tidak |
| `type` | `count` \| `weight` \| `volume` \| `length` \| `other` | Ya |
| `description` | Catatan | Tidak |
| `is_active` | Aktif/nonaktif | Ya |
| `sort_order` | Urutan di dropdown | Tidak |
| timestamps / soft deletes | Standar Laravel | Ya |

#### Requirements UOM

| ID | Requirement | Priority |
|---|---|---|
| UOM-01 | Menu Master **Satuan (UOM)** — list, create, edit, nonaktifkan | Must |
| UOM-02 | Validasi `code` unik (case-insensitive) | Must |
| UOM-03 | Hanya UOM `is_active` yang bisa dipilih di form Item/PO | Must |
| UOM-04 | UOM yang sudah dipakai item/transaksi **tidak bisa hard-delete**; hanya soft-delete / nonaktif | Must |
| UOM-05 | Permission terpisah: `units.view`, `units.create`, `units.update`, `units.delete` | Must |
| UOM-06 | Seeder UOM standar industri/gudang | Must |
| UOM-07 | Tampilkan kode+nama di dropdown (contoh: `pcs — Pieces`) | Should |
| UOM-08 | Unit conversion antar UOM (mis. 1 box = 12 pcs) | Could (fase lanjut; SUP-08) |

#### Seeder UOM awal (contoh)

| Code | Name | Type |
|---|---|---|
| `pcs` | Pieces | count |
| `unit` | Unit | count |
| `box` | Box | count |
| `pack` | Pack | count |
| `set` | Set | count |
| `kg` | Kilogram | weight |
| `g` | Gram | weight |
| `ltr` | Liter | volume |
| `ml` | Mililiter | volume |
| `m` | Meter | length |
| `cm` | Centimeter | length |
| `roll` | Roll | other |
| `pasang` | Pasang | count |

> Konversi antar satuan (box→pcs) **bukan MVP wajib**; dicatat di backlog. MVP: 1 item = 1 base UOM dari master.

#### Relasi

```
units (master UOM)
   └── items.uom_id  (base unit item)
         └── dipakai di: stock, PO line, GR, opname, borrow, movement, reports
```

### 5.3.1 Status Barang / Asset (Tracking)

Tujuan: user selalu tahu **kondisi & posisi lifecycle** barang — bukan hanya qty di gudang.

InventPro membedakan **3 lapisan status** agar tidak ambigu:

| Lapisan | Apa yang ditampilkan | Contoh |
|---|---|---|
| **A. Master Status** | Apakah item masih dipakai di sistem | `active`, `inactive`, `discontinued` |
| **B. Stock Condition** | Kondisi qty di lokasi (untuk stok/consumable) | `good`, `damaged`, `quarantine`, `expired` |
| **C. Asset Lifecycle** | Status unit asset individual (serialized) | `available`, `reserved`, `borrowed`, `in_transit`, `maintenance`, `lost`, `disposed` |

#### Mode tracking

| Mode | Kapan dipakai | Cara tahu status |
|---|---|---|
| **Qty-based** (default consumable) | Obat, ATK, sparepart habis pakai | Status = agregat stok per lokasi + condition bucket + reserved/borrow qty |
| **Asset unit (serialized)** | Laptop, tools, mesin, peralatan bernilai | Tiap unit punya `asset_tag` / serial → status lifecycle per unit |

**Usulan MVP:** keduanya didukung.
- Item `consumable` → qty + condition.
- Item `asset` → wajib serial/asset tag saat stock in / receiving (bisa diaktifkan per item: `is_serialized = true`).

#### Status Lifecycle Asset (C) — nilai baku

| Status | Arti | Boleh dipinjam? | Mengurangi available? |
|---|---|---|---|
| `available` | Siap dipakai di lokasi | Ya | Tidak (ini stok available) |
| `reserved` | Ditahan untuk request/approval | Tidak | Ya |
| `borrowed` | Sedang dipinjam | Tidak | Ya (sudah out) |
| `in_transit` | Transfer antar lokasi | Tidak | Ya |
| `maintenance` | Perbaikan / kalibrasi | Tidak | Ya |
| `damaged` | Rusak, belum diputuskan | Tidak | Ya |
| `quarantine` | Tahan QC / investigasi | Tidak | Ya |
| `lost` | Hilang (setelah approval) | Tidak | Ya (qty ditulis off) |
| `disposed` | Dihapusbukukan / dijual / scrap | Tidak | Ya (keluar permanen) |

#### Cara user melihat status di UI

1. **List Item / Stock** — kolom badge status + filter status.  
2. **Detail Item** — ringkasan: qty on-hand, available, reserved, borrowed, damaged, maintenance.  
3. **Tab Asset Units** (jika serialized) — serial/asset tag + status + lokasi + **peminjam (karyawan)** + **client pemakaian**.  
4. **Dashboard** — widget: asset borrowed, in maintenance, damaged, overdue return (dengan client).
5. **Laporan Status Barang/Asset** — filter by status, lokasi, kategori, peminjam, client.
6. **Histori Status** — setiap perubahan status tercatat (siapa, kapan, dari→ke, alasan, dokumen terkait).

#### Aturan perubahan status

| ID | Requirement | Priority |
|---|---|---|
| AST-01 | Field `item_type`: consumable \| asset | Must |
| AST-02 | Flag `is_serialized` pada item asset | Must |
| AST-03 | Master/unit asset: `asset_tag`, `serial_number`, `location_id`, `status`, `condition`, `current_holder_user_id`, `current_client_id` (nullable) | Must |
| AST-04 | Perubahan status hanya lewat aksi terkontrol (bukan edit bebas sembarang) | Must |
| AST-05 | Aksi: Set Maintenance, Selesai Maintenance, Mark Damaged, Quarantine, Dispose (butuh permission/approval) | Must |
| AST-06 | Integrasi otomatis: Borrow checkout → `borrowed`; return → `available` (atau `damaged` jika kondisi rusak) | Must |
| AST-07 | Integrasi Transfer → `in_transit` lalu `available` di lokasi tujuan | Must |
| AST-08 | Dispose / Lost wajib alasan + approval (role approver/admin) | Must |
| AST-09 | Histori status immutable (`asset_status_histories`) | Must |
| AST-10 | Filter & badge status di semua list terkait | Must |
| AST-11 | Laporan & export status asset | Must |
| AST-12 | Seeder contoh asset serialized + berbagai status | Must |

#### Alur cepat (contoh)

```
Stock In / GR (asset serialized)
  → unit dibuat status: available, condition: good

Borrow approved + checkout
  → status: borrowed
  → current_holder_user_id: Karyawan A
  → current_client_id: Client A

Return (kondisi baik)
  → status: available, holder: null, client: null

Return (rusak) / Mark Damaged
  → status: damaged → (opsional) maintenance → available
  → atau dispose (approval)
```

### 5.4 Vendor Management

| ID | Requirement | Priority |
|---|---|---|
| VND-01 | CRUD Vendor (nama, NPWP/opsional, alamat, kontak, email, telepon) | Must |
| VND-02 | Status aktif/nonaktif | Must |
| VND-03 | Catatan & lampiran sederhana | Should |
| VND-04 | Histori PO & performa (on-time, qty) | Should |

### 5.4.1 Client Management

Pisahkan dengan jelas tiga peran:

| Peran | Arti | Contoh |
|---|---|---|
| **Vendor** | Pemasok barang masuk | Supplier scaffolding |
| **Borrower (Peminjam)** | Karyawan/user yang bertanggung jawab meminjam | **Karyawan A** |
| **Client** | Pihak / proyek / site **tempat barang digunakan** | **Client A** (lokasi proyek) |

**Contoh bisnis (inti):**

> Scaffold Holding dipinjam oleh **Karyawan A** untuk digunakan di **Client A**.

Artinya di sistem:
- Item/Asset: Scaffold Holding  
- `borrower_user_id`: Karyawan A (penanggung jawab & yang checkout/return)  
- `client_id`: Client A (konteks pemakaian / proyek / site)  

Client **bukan** pengganti peminjam. Client adalah **context of use** (customer, proyek, site, gedung, dll.).

Digunakan sebagai master referensi untuk:
- Peminjaman: “dipakai untuk Client mana?”
- Tracking asset: sedang di site Client mana + dipegang karyawan siapa
- Stock Out / Issue ke proyek client (jika relevan)
- Laporan utilisasi barang per client / per karyawan
- (Backlog) project/site detail, delivery note — tidak wajib MVP

#### Field master `clients` (usulan)

| Field | Keterangan | Wajib |
|---|---|---|
| `id` | UUID | Ya |
| `code` | Kode unik client (auto/manual) | Ya |
| `name` | Nama client / perusahaan / proyek induk | Ya |
| `type` | `company` \| `project_site` \| `individual` \| `internal_unit` \| `other` | Ya |
| `contact_person` | PIC di sisi client | Tidak |
| `email` | Email | Tidak |
| `phone` | Telepon/WA | Tidak |
| `address` | Alamat / lokasi site | Tidak |
| `tax_id` | NPWP/opsional | Tidak |
| `notes` | Catatan | Tidak |
| `is_active` | Aktif/nonaktif | Ya |
| soft deletes / timestamps | Standar | Ya |

#### Requirements Client

| ID | Requirement | Priority |
|---|---|---|
| CLI-01 | Menu Master **Client** — list, create, edit, nonaktifkan | Must |
| CLI-02 | Validasi `code` unik; `name` wajib | Must |
| CLI-03 | Filter & search (kode, nama, tipe, status) | Must |
| CLI-04 | Hanya client `is_active` yang bisa dipilih di form pinjam / issue | Must |
| CLI-05 | Client yang sudah punya transaksi **tidak hard-delete**; soft-delete / nonaktif | Must |
| CLI-06 | Detail client: daftar peminjaman aktif & histori (item, peminjam/karyawan, periode) | Must |
| CLI-07 | Catatan & lampiran sederhana | Should |
| CLI-08 | Permission: `clients.view`, `clients.create`, `clients.update`, `clients.delete` | Must |
| CLI-09 | Seeder client contoh termasuk skenario Scaffold → Karyawan → Client | Must |
| CLI-10 | Audit log untuk create/update/deactivate client | Must |
| CLI-11 | Di UI pinjam & detail asset: tampilkan **Peminjam** dan **Dipakai di Client** secara terpisah | Must |

#### Integrasi modul lain

| Modul | Relasi |
|---|---|
| Peminjaman | Wajib `borrower_user_id` + `client_id` (lihat §5.7) |
| Asset Unit | Saat `borrowed`: simpan `current_holder_user_id` + `current_client_id` |
| Stock Out / Issue | `client_id` nullable; wajib jika alasan terkait client/proyek |
| Laporan | Filter & grouping by client **dan** by borrower |
| Dashboard | Borrow aktif per client; overdue per karyawan |

> Client **bukan** akun login. Karyawan login sebagai user; Client hanya master data konteks pemakaian.

### 5.5 Purchase Management

| ID | Requirement | Priority |
|---|---|---|
| PUR-01 | Purchase Request (opsional) → Purchase Order | Should |
| PUR-02 | CRUD PO: vendor, item, qty, harga, tanggal, catatan | Must |
| PUR-03 | Status PO: draft, submitted, approved, rejected, ordered, partially_received, received, cancelled | Must |
| PUR-04 | Approval PO melalui **engine approval dinamis** (§5.12) | Must |
| PUR-05 | Goods Receipt (GR) partial/full → stock in otomatis | Must |
| PUR-06 | Nomor dokumen otomatis (PO/GR) | Must |

### 5.6 Stock Opname

| ID | Requirement | Priority |
|---|---|---|
| SOP-01 | Buat sesi opname (lokasi, tanggal, pic) | Must |
| SOP-02 | Generate daftar item lokasi | Must |
| SOP-03 | Input qty fisik (mobile friendly) | Must |
| SOP-04 | Hitung selisih vs sistem | Must |
| SOP-05 | Approval adjustment via **engine approval dinamis** sebelum post ke ledger | Must |
| SOP-06 | Posting → stock adjustment + audit | Must |
| SOP-07 | Laporan hasil opname | Must |

### 5.7 Peminjaman Barang / Stock

Model wajib memisahkan **siapa meminjam** vs **untuk client mana dipakai**.

#### Contoh alur

```
Item: Scaffold Holding (asset/consumable sesuai master)
  → Request oleh / untuk peminjam: Karyawan A
  → Digunakan di: Client A
  → Approval dinamis
  → Checkout gudang
  → Status asset: borrowed
       holder = Karyawan A
       client  = Client A
  → Return oleh Karyawan A (sebagian/penuh)
```

| ID | Requirement | Priority |
|---|---|---|
| BOR-01 | Request pinjam: item/qty (atau asset unit), tgl pinjam, tgl kembali, tujuan/catatan | Must |
| BOR-01b | Field wajib **`borrower_user_id`** (karyawan penanggung jawab) | Must |
| BOR-01c | Field wajib **`client_id`** (client/site tempat barang digunakan) | Must |
| BOR-01d | UI label jelas: “Peminjam (Karyawan)” ≠ “Digunakan di Client” | Must |
| BOR-02 | Approval / reject via **engine approval dinamis** | Must |
| BOR-03 | Checkout (kurangi available / reserved → out); set holder + client pada asset | Must |
| BOR-04 | Return partial/full + kondisi barang; clear holder/client saat kembali | Must |
| BOR-05 | Overdue reminder di dashboard (tampilkan peminjam + client) | Must |
| BOR-06 | Riwayat peminjaman per karyawan, per client, per item | Must |
| BOR-07 | Filter list pinjam: by borrower, by client, by status, by tanggal | Must |
| BOR-08 | Seeder: contoh “Scaffold Holding dipinjam Karyawan A untuk Client A” | Must |

### 5.8 Stock In / Out

| ID | Requirement | Priority |
|---|---|---|
| MOV-01 | Stock In manual (alasan: return, adjustment+, transfer in, other) | Must |
| MOV-02 | Stock Out manual (alasan: issue, issue_to_client, damage, transfer out, other) + **client** bila relevan | Must |
| MOV-03 | Transfer antar lokasi | Must |
| MOV-04 | Validasi stok cukup sebelum out | Must |
| MOV-05 | Semua gerakan menulis stock ledger immutable | Must |
| MOV-06 | Referensi dokumen sumber (PO/GR/Opname/Borrow) | Must |

### 5.9 Laporan

| ID | Requirement | Priority |
|---|---|---|
| RPT-01 | Laporan stok terkini (filter lokasi/kategori) | Must |
| RPT-02 | Laporan mutasi stok (periode) | Must |
| RPT-03 | Laporan PO & receiving | Must |
| RPT-04 | Laporan vendor | Should |
| RPT-04b | Laporan client (aktifitas pinjam / issue outbound) | Should |
| RPT-05 | Laporan opname & selisih | Must |
| RPT-06 | Laporan peminjaman & overdue (filter client) | Must |
| RPT-07 | Stock aging / slow moving | Should |
| RPT-08 | Stock valuation (harga terakhir / rata-rata sederhana) | Should |
| RPT-09 | Export PDF / Excel | Must |
| RPT-10 | Laporan status barang/asset (by status, lokasi, holder) | Must |
| RPT-11 | Laporan histori perubahan status asset | Should |

### 5.10 Dashboard Analytics

| ID | Requirement | Priority |
|---|---|---|
| DASH-01 | Kartu KPI: total item, total qty, low stock, open PO, open borrow, overdue | Must |
| DASH-02 | Chart mutasi in vs out (periode) | Must |
| DASH-03 | Chart top item bergerak | Should |
| DASH-04 | Alert list actionable | Must |
| DASH-05 | Filter periode / lokasi | Must |
| DASH-06 | KPI status asset: available / borrowed / maintenance / damaged | Must |

### 5.11 Fitur Pendukung Tambahan (Recommended)

| ID | Fitur | Priority |
|---|---|---|
| SUP-01 | Audit Log — detail di §5.14 | Must |
| SUP-02 | Notifikasi in-app — detail di §5.13 | Must |
| SUP-03 | Activity feed di dashboard | Should |
| SUP-04 | Master Settings (perusahaan, nomor dokumen, timezone) | Must |
| SUP-05 | Upload attachment dokumen (PO, GR) | Should |
| SUP-06 | Soft delete + restore (admin) | Should |
| SUP-07 | Global search (item, PO, vendor, client) | Should |
| SUP-08 | Unit conversion sederhana (fase lanjut) | Could |
| SUP-09 | Print label / barcode sheet | Could |
| SUP-10 | Dark/Light theme toggle (opsional) — default light modern | Could |
| SUP-11 | Dynamic Approval Engine — detail di §5.12 | Must |

### 5.12 Dynamic Approval Workflow (Konfigurasi Admin)

Approval **tidak hard-code** per modul. Admin (dengan permission) dapat mengatur workflow sendiri sesuai kebutuhan organisasi.

#### Konsep

| Konsep | Keterangan |
|---|---|
| **Approval Document Type** | Jenis dokumen yang bisa di-approve, mis. `purchase_order`, `stock_opname`, `borrow_request`, `asset_dispose`, `stock_adjustment` |
| **Approval Workflow** | Definisi alur per document type (aktif/nonaktif, nama, versi) |
| **Approval Step** | Urutan langkah (1..N): siapa yang approve, mode approval |
| **Approval Request** | Instance saat dokumen di-submit: status pending/approved/rejected/cancelled |
| **Approval Action** | Approve / Reject / Return-to-revision + komentar |

#### Mode step (MVP)

| Mode | Arti |
|---|---|
| `any` | Salah satu approver di step cukup |
| `all` | Semua approver di step wajib approve |
| `sequential` | Step berjalan berurutan; step N+1 baru aktif setelah step N selesai |

**Assignee step** bisa:
- Role tertentu (contoh: `approver`, `admin`)
- User tertentu (opsional, fase refine)
- Kombinasi role (MVP: 1 role per step; multi-role Could)

#### Pengaturan oleh Admin (UI)

Menu **Settings → Approval Workflows** (atau **Administration → Approvals**):

1. Pilih document type  
2. Aktifkan/nonaktifkan wajib approval  
3. Tambah/urutkan step  
4. Tentukan role approver + mode (`any`/`all`)  
5. Opsional: kondisi sederhana (Could) — mis. PO total ≥ X butuh 2 step  
6. Simpan & aktifkan  

Jika workflow **nonaktif** untuk suatu tipe: dokumen bisa langsung ke status approved/posted sesuai aturan modul (tetap tercatat di audit).

#### Requirements Approval Dinamis

| ID | Requirement | Priority |
|---|---|---|
| APPR-01 | CRUD Approval Workflow per document type | Must |
| APPR-02 | Multi-step approval (min 1 step, support ≥ 2 step) | Must |
| APPR-03 | Assign approver by **role** per step | Must |
| APPR-04 | Mode step: `any` / `all` | Must |
| APPR-05 | Sequential steps (step berikutnya menunggu step sebelumnya) | Must |
| APPR-06 | Submit dokumen → buat `approval_request` + notifikasi ke approver step aktif | Must |
| APPR-07 | Approve / Reject dengan wajib komentar saat reject | Must |
| APPR-08 | Reject → dokumen kembali `rejected` / `revision` (sesuai tipe) | Must |
| APPR-09 | Semua step selesai → dokumen status `approved` + trigger lanjut proses | Must |
| APPR-10 | Halaman **My Approvals** (pending untuk user login) | Must |
| APPR-11 | Riwayat approval per dokumen (siapa, kapan, aksi, komentar) | Must |
| APPR-12 | Permission: `approvals.manage` (admin), `approvals.act` (approve/reject) | Must |
| APPR-13 | Seeder workflow default: PO (1 step), Opname (1 step), Borrow (1 step), Asset Dispose (1–2 step) | Must |
| APPR-14 | Superadmin bisa override / reassign step macet (Should) | Should |
| APPR-15 | Kondisi berbasis amount/qty (routing step) | Could |
| APPR-16 | Parallel multi-role dalam 1 step | Could |
| APPR-17 | Escalation SLA (auto-remind jika pending > N jam) | Could |

#### Document types wajib terintegrasi (MVP)

| Document Type | Saat submit | Setelah fully approved |
|---|---|---|
| `purchase_order` | Draft → pending approval | `approved` / siap order/receive |
| `stock_opname` | Sesi siap posting → pending | Boleh post adjustment |
| `borrow_request` | Request diajukan → pending | Siap checkout |
| `asset_dispose` / `asset_lost` | Pengajuan hapus buku | Status unit → disposed/lost |
| `stock_adjustment` (opsional) | Adjustment manual besar | Post ke ledger |

#### Alur generik

```
Draft/Ready
  → User Submit
  → Engine cek workflow aktif?
        ├─ Tidak → auto-approve (policy modul) + audit
        └─ Ya → Approval Request (step 1)
              → Notifikasi bell ke approver
              → Approve/Reject
                    ├─ Reject → stop + notif requester
                    └─ Approve → next step / Fully Approved
                              → Notif requester + lanjut proses bisnis
```

### 5.13 In-App Notification (Bell + Halaman)

| ID | Requirement | Priority |
|---|---|---|
| NTF-01 | **Bell icon** di topbar (semua halaman authenticated) | Must |
| NTF-02 | Badge jumlah unread pada bell | Must |
| NTF-03 | Dropdown bell: 5–10 notifikasi terbaru + link "Lihat semua" | Must |
| NTF-04 | **Halaman Notifikasi** penuh: list, filter (all/unread), mark read, mark all read | Must |
| NTF-05 | Klik notifikasi → tandai read + navigasi ke dokumen terkait | Must |
| NTF-06 | Tipe notifikasi: approval_request, approval_result, low_stock, borrow_overdue, system | Must |
| NTF-07 | Realtime-ish: polling interval wajar **atau** Laravel Echo/Reverb jika tersedia; MVP boleh polling 30–60s | Must |
| NTF-08 | Permission tidak perlu khusus untuk baca notifikasi sendiri; hapus notifikasi opsional | Should |
| NTF-09 | Preferensi notifikasi user (on/off per tipe) | Could |
| NTF-10 | Seeder contoh notifikasi untuk QC | Should |

**Pemicu wajib menghasilkan notifikasi:**

- Ada approval pending untuk user (sebagai approver step aktif)
- Dokumen user di-approve / reject
- Low stock
- Borrow overdue
- Asset masuk maintenance/damaged (Should)

### 5.14 Audit Log (Fungsi Audit)

Audit log adalah **jejak immutable** untuk investigasi & compliance. Bukan sekadar activity feed.

| ID | Requirement | Priority |
|---|---|---|
| AUD-01 | Tabel `audit_logs` append-only (tidak di-update/delete via aplikasi) | Must |
| AUD-02 | Field wajib: `id` UUID, `actor_user_id`, `actor_name` snapshot, `action`, `module`, `auditable_type`, `auditable_id`, `description`, `old_values` JSON, `new_values` JSON, `ip_address`, `user_agent`, `created_at` | Must |
| AUD-03 | Catat: login sukses/gagal, logout, CRUD master kritis, submit/approve/reject, posting stok, perubahan status asset, perubahan RBAC, perubahan approval workflow | Must |
| AUD-04 | Halaman **Audit Log** (filter tanggal, user, module, action, keyword) | Must |
| AUD-05 | Detail audit: diff old vs new (readable) | Must |
| AUD-06 | Export audit log (Excel/CSV) — permission terpisah | Should |
| AUD-07 | Permission: `audit_logs.view`, `audit_logs.export` | Must |
| AUD-08 | Retensi: tidak dihapus otomatis di MVP; kebijakan purge = admin/ops (dokumentasikan) | Should |
| AUD-09 | Viewer/Auditor role punya akses lihat audit | Must |
| AUD-10 | Seeder sample audit entries untuk QC | Should |

**Prinsip:** setiap aksi yang mengubah stok, status dokumen, permission, atau approval **wajib** punya jejak audit.

---

## 6. Non-Functional Requirements

### 6.1 Performance

- List halaman utama paginated (default 15–25 rows).
- Query stok & laporan diindeks (SKU, lokasi, tanggal, status).
- Target response API CRUD < 500ms (lokal/dev dataset sedang).

### 6.2 Security (OWASP-oriented)

| Area | Kontrol |
|---|---|
| A01 Broken Access Control | Policy/permission check tiap endpoint + hide menu |
| A02 Cryptographic Failures | Password hashed (bcrypt/argon2); HTTPS production; secrets di `.env` |
| A03 Injection | Eloquent/Query Builder parameterized; no raw user SQL |
| A04 Insecure Design | Approval workflow untuk transaksi kritikal |
| A05 Security Misconfiguration | Debug off di prod; secure headers |
| A06 Vulnerable Components | Composer/NPM audit rutin |
| A07 Auth Failures | Rate limit login, session regenerate, CSRF |
| A08 Software/Data Integrity | CSRF token; signed URLs bila perlu |
| A09 Logging & Monitoring | Audit log + failed login log |
| A10 SSRF | Validasi URL upload/fetch eksternal (jika ada) |

Tambahan wajib:

- CSRF protection (Laravel default untuk web)
- XSS escaping (Vue + server)
- Mass assignment protection
- UUID tidak exposed sebagai sequential ID
- File upload validation (tipe/ukuran)
- Superadmin tidak bisa dihapus / di-downgrade via UI biasa

### 6.3 Usability & UI/UX

- Responsive: mobile / tablet / desktop
- Modern admin template (sidebar + topbar)
- Toastr & in-app alert modern (sukses, error, warning, confirm)
- Aksesibilitas dasar: kontras, fokus keyboard, label form
- Empty state & loading state jelas

### 6.4 Reliability & Data Integrity

- Stock ledger sebagai sumber kebenaran mutasi
- Transaksi DB (DB::transaction) untuk posting stok
- Idempotensi dasar untuk posting dokumen (cegah double post)

### 6.5 Maintainability

- Modul terstruktur (Controllers/Services/Policies/Vue pages)
- Seeder per modul + RBAC users
- Dokumentasi delivery & QC per fase

---

## 7. Technology Architecture

### 7.1 Stack

| Layer | Choice |
|---|---|
| Repo | Monolith |
| Backend | Laravel (latest LTS/stable) |
| Frontend | Vue 3 + Vite + (Inertia **atau** API + Vue SPA — diputuskan di Delivery Plan) |
| DB | MySQL |
| PK | UUID (`char(36)` / binary uuid — rekomendasi `uuid` string indexed) |
| Auth | Laravel session / Sanctum (tergantung pola FE) |
| RBAC | Spatie Permission **atau** custom dynamic RBAC — rekomendasi Spatie + UI dinamis |
| Excel/PDF | Maatwebsite Excel / DomPDF atau setara |
| Notify UI | Modern toast (mis. vue-sonner / vue-toastification) + confirm modal |

**Keputusan teknis yang perlu approval di dokumen ini (usulan):**

1. **Frontend pattern:** Laravel + Inertia.js + Vue 3 (monolith DX terbaik, SEO admin tidak kritis, auth sederhana).  
2. **RBAC package:** `spatie/laravel-permission` + UI matrix custom.  
3. **UI kit:** Tailwind CSS + Headless UI / custom components (bukan template bootstrap jadul).

### 7.2 High-Level Architecture

```
[Browser Vue/Inertia]
        |
   Laravel Routes + Middleware (auth, permission, throttle)
        |
   Controllers → Form Requests → Services → Models/Eloquent
        |
   MySQL (UUID PKs) + Files (storage)
        |
   Audit Log / Notifications
```

### 7.3 Core Domain Model (ringkas)

- `users`, `roles`, `permissions`, `model_has_*`
- `categories`, `units` (**master UOM**), `locations`, `items` (FK `uom_id`), `item_stocks`
- `asset_units` (serialized assets: tag, serial, status, condition, holder, location)
- `asset_status_histories` (immutable status changes)
- `stock_ledgers` (immutable movements)
- `vendors`
- `clients` (**master Client Management**)
- `purchase_orders`, `purchase_order_items`, `goods_receipts`, `goods_receipt_items`
- `stock_opnames`, `stock_opname_items`
- `borrow_requests`, `borrow_items`
- `stock_movements` (header in/out/transfer) — atau langsung via ledger + document type
- `approval_workflows`, `approval_steps`, `approval_requests`, `approval_actions`
- `notifications` (in-app; bell + halaman)
- `audit_logs` (immutable)
- `settings`
- Document number sequences

> Detail ERD & migration akan ada di Phase Delivery teknis setelah approval Delivery Plan.

---

## 8. Design System & Color Palette

### 8.1 Direction

- Modern inventory SaaS: bersih, profesional, kontras jelas.
- **Bukan** purple-glow / cream-terracotta generic AI look.
- Brand tone: **Teal industrial + Slate neutral + Amber accent** (aksi penting / warning stok).

### 8.2 Color Tokens

| Token | Hex | Penggunaan |
|---|---|---|
| `--color-brand-700` | `#0F766E` | Primary brand, sidebar active |
| `--color-brand-600` | `#0D9488` | Primary button, links |
| `--color-brand-500` | `#14B8A6` | Hover/focus accents |
| `--color-brand-50` | `#F0FDFA` | Soft backgrounds |
| `--color-slate-900` | `#0F172A` | Text utama |
| `--color-slate-600` | `#475569` | Secondary text |
| `--color-slate-100` | `#F1F5F9` | Page background sections |
| `--color-slate-50` | `#F8FAFC` | App background |
| `--color-success` | `#059669` | Toast sukses, badge aktif |
| `--color-warning` | `#D97706` | Low stock, overdue soft |
| `--color-danger` | `#DC2626` | Error, delete, reject |
| `--color-info` | `#0284C7` | Info toast / tips |
| `--color-surface` | `#FFFFFF` | Panel/content surface |
| `--color-border` | `#E2E8F0` | Borders |

### 8.3 Button Mapping

| Button | Style |
|---|---|
| Primary | Teal solid (`brand-600`) — Simpan, Submit, Approve |
| Secondary | Slate outline — Batal, Kembali |
| Danger | Red solid — Hapus, Reject |
| Warning | Amber solid — Post Opname, Force actions |
| Ghost | Text only — aksi ringan di tabel |
| Disabled | 40% opacity + no pointer |

### 8.4 Typography

- Font display/UI: **Plus Jakarta Sans** (atau **DM Sans**) via Google Fonts.
- Mono untuk SKU/nomor dokumen: **JetBrains Mono** / system mono.
- Skala: 12 / 14 / 16 / 20 / 24 / 32.

### 8.5 Layout

- Desktop: collapsible sidebar + topbar + content.
- Tablet: sidebar overlay/collapsed.
- Mobile: bottom/top nav ringkas + drawer menu; tabel → card/list transform.
- Density: comfortable (bukan terlalu rapat).

### 8.6 Feedback Components

- **Toast:** pojok kanan atas, auto-dismiss 3–5s, icon + warna status.
- **Alert inline:** di atas form untuk validasi server.
- **Confirm modal:** untuk delete / post / approve (bukan `window.confirm`).

---

## 9. User Journeys (utama)

### 9.1 Stock In dari PO

1. Purchasing buat PO → Approver approve → status ordered.  
2. Warehouse terima barang (GR partial/full).  
3. Sistem update `item_stocks` + tulis `stock_ledgers`.  
4. PO status update otomatis.

### 9.2 Stock Opname

1. Admin buat sesi opname lokasi.  
2. Staff input qty fisik.  
3. Sistem tampilkan selisih.  
4. Approver approve adjustment.  
5. Posting ke ledger; laporan tersedia.

### 9.3 Peminjaman (Karyawan + Client)

1. Karyawan A (atau admin mewakili) buat request: item Scaffold Holding, peminjam = Karyawan A, digunakan di = Client A.  
2. Approver approve (workflow dinamis).  
3. Warehouse checkout → asset `borrowed`, holder Karyawan A, client Client A.  
4. Karyawan A return; stok/asset kembali available; holder & client dikosongkan; request selesai.

### 9.4 Low Stock Alert

1. Stok ≤ min → notifikasi + muncul di dashboard.  
2. Purchasing buat PO dari alert (fase lanjut: shortcut).

---

## 10. Data & Seeder Requirements

| Seeder | Isi |
|---|---|
| RBAC | Permissions semua modul + roles + mapping |
| Users | Superadmin + 1 user per role default |
| Master | Kategori, **UOM lengkap (seeder standar)**, lokasi contoh |
| Items | 20–50 item contoh + stok awal (campuran consumable & asset) |
| Asset Units | 15–30 unit serialized dengan status beragam |
| Vendors | 5–10 vendor |
| Clients | 5–15 client (company / individual / internal_unit) |
| Purchases | Beberapa PO (draft/approved/received) |
| Opname | 1 sesi contoh (completed/draft) |
| Borrow | Beberapa request berbagai status |
| Movements | Sample in/out/transfer |
| Settings | Nama perusahaan, prefix dokumen |
| Approvals | Workflow default per document type + sample pending request |
| Notifications | Sample unread/read untuk bell & halaman |
| Audit Logs | Sample jejak create/approve/post |

**Akun seeder (usulan — password diganti di produksi):**

| Email | Role |
|---|---|---|
| `superadmin@inventpro.local` | superadmin |
| `admin@inventpro.local` | admin |
| `purchasing@inventpro.local` | purchasing |
| `warehouse@inventpro.local` | warehouse |
| `approver@inventpro.local` | approver |
| `viewer@inventpro.local` | viewer |

Password default usulan: `Password123!` (wajib diganti; hanya untuk lokal/dev).

---

## 11. Reporting Requirements (detail)

Setiap laporan mendukung:

- Filter tanggal / lokasi / kategori / status / vendor
- Pagination / print view
- Export Excel & PDF
- Permission terpisah (`reports.view`, `reports.export`)

---

## 12. Acceptance Criteria (produk tingkat tinggi)

1. Semua modul Must di Section 5 tersedia dan terhubung ke stok ledger.  
2. Tidak ada endpoint mutasi stok tanpa permission.  
3. Superadmin selalu bisa akses penuh; role lain sesuai matrix.  
4. UI responsif di mobile & desktop untuk flow inti.  
5. Toast/alert modern dipakai konsisten.  
6. Seeder menghasilkan data demo siap QC.  
7. Audit trail tercatat untuk create/update/delete, approval, RBAC, & posting dokumen.  
8. Approval dinamis dapat dikonfigurasi admin minimal untuk PO, Opname, Borrow, Asset Dispose.  
9. Notifikasi bell + halaman notifikasi berfungsi untuk approval & alert operasional.  
10. Dokumen Delivery & Phase Delivery + QC manual tersedia per fase.

---

## 13. Delivery Governance (wajib)

Alur kerja dokumen & implementasi:

```
BRD/PRD (dokumen ini)
    → APPROVAL Anda
Delivery Plan (phase list)
    → APPROVAL Anda
Phase N Spec + Coding + Phase Delivery Doc + QC Guide
    → APPROVAL Anda
Phase N+1 ...
```

Aturan:

1. Tidak lanjut dokumen berikutnya tanpa approval tertulis di chat / file approvals.  
2. Tidak lanjut coding phase berikutnya tanpa approval phase sebelumnya.  
3. Setiap Phase Delivery wajib memuat **panduan QC manual** langkah demi langkah.  
4. Perubahan scope setelah approval = Change Request (dicatat & di-reapprove).

### 13.1 Template Approval

Salin ke `docs/approvals/APPROVAL-LOG.md` atau balas di chat:

```
Document : 01-BRD-PRD.md
Version  : 1.0.0-DRAFT
Decision : APPROVED | REJECTED | REVISE
Notes    : ...
Approver : <nama>
Date     : YYYY-MM-DD
```

---

## 14. Risks & Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Double posting stok | Data corrupt | DB transaction + status guard |
| Permission miss | Security hole | Policy tests + QC matrix |
| Opname lama mengunci stok | Operasional macet | Scope lokasi + status jelas |
| Scope creep laporan | Delay | Prioritaskan Must reports dulu |
| UUID perf | Index besar | Composite indexes pada filter utama |

---

## 15. Open Questions (butuh keputusan Anda)

Mohon jawab saat review/approval (boleh default usulan jika setuju):

1. **FE pattern:** setuju **Inertia + Vue 3 + Tailwind**? (Usulan: Ya)  
2. **Multi-gudang:** wajib di MVP? (Usulan: Ya, minimal 2 lokasi)  
3. **Purchase Request** terpisah dari PO, atau langsung PO? (Usulan: langsung PO + approval)  
4. **Valuation method:** last purchase price vs moving average? (Usulan: last purchase price dulu)  
5. **Bahasa UI:** ID only? (Usulan: ID)  
6. **Password policy** & 2FA: 2FA masuk MVP atau backlog? (Usulan: backlog)  
7. Nama legal perusahaan default di seeder?  
8. Ada integrasi spesifik (WhatsApp notif, scanner hardware)? (Usulan: tidak di MVP)
9. **Status asset:** setuju model 3 lapisan (master / condition / lifecycle) + serialized asset unit? (Usulan: Ya)
10. Apakah dispose/lost wajib approval? (Usulan: Ya)
11. Apakah semua asset wajib serial, atau bisa asset non-serial (qty saja)? (Usulan: opsional per item via `is_serialized`)
12. **UOM conversion** (1 box = 12 pcs) masuk MVP atau backlog? (Usulan: backlog; MVP 1 item = 1 base UOM)
13. Apakah daftar seeder UOM di §5.3.0 sudah cukup, atau perlu ditambah (mis. `ton`, `drum`, `lembar`)?
14. **Approval dinamis:** setuju model multi-step + assign by role + mode any/all? (Usulan: Ya)
15. Kondisi amount-based routing (PO ≥ X butuh 2 approver) — MVP atau Could? (Usulan: Could)
16. Notifikasi realtime WebSocket (Reverb) di MVP, atau polling dulu? (Usulan: polling MVP, Reverb Could)
17. Apakah user biasa boleh melihat audit log, atau hanya admin/auditor/viewer role? (Usulan: hanya yang punya `audit_logs.view`)
18. **Peminjaman:** setuju wajib `borrower_user_id` + `client_id` (contoh Scaffold → Karyawan A → Client A)? (Usulan: **Ya, keduanya wajib**)
19. Apakah Client butuh portal login sendiri? (Usulan: **tidak** di MVP — master data saja)
20. Perlu field kredit limit / terms untuk client? (Usulan: backlog)
21. Perlu master **Project/Site** di bawah Client (1 client banyak site), atau cukup Client + alamat dulu? (Usulan: Client saja di MVP; Project/Site Could)
22. Bolehkah satu request pinjam untuk banyak client? (Usulan: **tidak** — 1 request = 1 client; buat request terpisah jika beda client)

---

## 16. Glossary

| Istilah | Arti |
|---|---|
| SKU | Stock Keeping Unit |
| PO | Purchase Order |
| GR | Goods Receipt |
| Ledger | Catatan immutable mutasi stok |
| Available | On-hand − reserved |
| Reserved | Stok dipesan untuk pinjam/approved outbound |
| Opname | Stock take / physical count |
| RBAC | Role-Based Access Control |
| Consumable | Barang habis pakai, dilacak berbasis qty |
| Asset Unit | Unit individual (serial/asset tag) dengan lifecycle status |
| Asset Tag | Kode inventaris internal per unit |
| Condition | Kondisi fisik (good/damaged/quarantine/expired) |
| Lifecycle Status | Status operasional unit (available/borrowed/maintenance/…) |
| UOM | Unit of Measure / satuan hitung (master `units`) |
| Base UOM | Satuan dasar item; semua qty stok dicatat dalam satuan ini |
| Approval Workflow | Konfigurasi alur approve yang bisa diubah admin |
| Approval Step | Tahapan dalam workflow (urutan + role + mode) |
| Approval Request | Instance approval untuk 1 dokumen spesifik |
| Audit Log | Catatan immutable siapa mengubah apa & kapan |
| In-App Notification | Pesan sistem di bell & halaman notifikasi |
| Vendor | Pemasok barang (inbound) |
| Borrower / Peminjam | Karyawan/user penanggung jawab peminjaman |
| Client | Customer/proyek/site tempat barang **digunakan** (bukan otomatis = peminjam) |
| Context of use | Relasi “barang dipinjam X untuk dipakai di Client Y” |
| Internal Unit | Jenis client untuk pemakaian internal (jika barang dipakai di unit sendiri) |

---

## 17. Document Control

| Version | Date | Author | Notes |
|---|---|---|---|
| 1.0.0-DRAFT | 2026-08-13 | InventPro Team | Initial BRD/PRD for approval |
| 1.1.0-DRAFT | 2026-08-13 | InventPro Team | Tambah Status Barang/Asset (AST-01…12), laporan & dashboard terkait |
| 1.2.0-DRAFT | 2026-08-13 | InventPro Team | Penegasan Master Satuan/UOM (§5.3.0, UOM-01…08) + seeder standar |
| 1.3.0-DRAFT | 2026-08-13 | InventPro Team | Dynamic Approval (§5.12), Notification bell+page (§5.13), Audit Log (§5.14) |
| 1.4.0-DRAFT | 2026-08-13 | InventPro Team | Client Management (§5.4.1, CLI-01…10) + integrasi borrow/stock out/laporan |
| 1.5.0-DRAFT | 2026-08-13 | InventPro Team | Clarifikasi Client ≠ Peminjam; model Karyawan A pinjam untuk Client A |

---

## 18. Approval Section

| Role | Name | Decision | Date | Signature/Note |
|---|---|---|---|---|
| Product Owner | Product Owner | ✅ APPROVED | 2026-08-13 | Chat approval: "approved"; defaults §15 diterima |

Dokumen lanjutan: `docs/02-DELIVERY-PLAN.md`.
