# Phase 07 Delivery — Purchase Management & Goods Receipt

| Field | Value |
|---|---|
| **Phase** | 7 |
| **Name** | Purchase Management & Goods Receipt |
| **Status** | ✅ APPROVED |
| **Based on** | Delivery Plan + Phase 6 APPROVED |
| **Date** | 2026-08-14 |
| **Approved** | 2026-08-14 |

> **Approval Gate Phase 7**  
> Jalankan QC di bawah, lalu balas **APPROVED** / **REVISE** / **REJECTED**.  
> Label Phase/MVP hanya di dokumen — tidak di UI.

---

## 1. Ringkasan Deliverable

1. CRUD **Purchase Order** + baris item (vendor, qty, harga)
2. Nomor dokumen otomatis (`prefix_po` / `prefix_gr` dari Pengaturan)
3. Submit PO → **Approval Engine** (`document_type=purchase_order`)
4. Setelah approve → status **ordered** (siap GR)
5. **Goods Receipt** partial/full → update stok + ledger (+ unit asset bila serialized)
6. Status PO otomatis: `partially_received` / `received`
7. Histori PO di detail Vendor
8. Permissions: `purchases.*`, `goods_receipts.*`
9. Seeder: 1 PO draft + 1 PO `ordered` (siap GR) untuk item Klem
10. **Unduh PO PDF & Excel** (DomPDF + Maatwebsite Excel), permission `purchases.export`

---

## 2. Alur bisnis

```
Purchasing buat PO (draft)
  → Ajukan approval
  → Approver setujui → status ordered
  → Warehouse buat GR (lokasi + rak)
  → Stok naik / unit asset dibuat
  → PO partially_received atau received
```

Reject mengembalikan PO ke `rejected` (bisa diedit & diajukan ulang).

---

## 3. Setup QC

```bash
php artisan migrate
php artisan db:seed
npm run build
```

Password: `Password123!`

| Role | Email | Fokus |
|---|---|---|
| Purchasing | `purchasing@inventpro.local` | Buat & ajukan PO |
| Approver | `approver@inventpro.local` | Setujui/tolak |
| Warehouse | `warehouse@inventpro.local` | Goods Receipt |

---

## 4. Panduan QC Manual

### QC-01 — Buat & ajukan PO

1. Login purchasing → **Purchase Order** → **Buat PO**.
2. Pilih vendor, tambah baris **Klem**, simpan & ajukan (atau draft lalu Ajukan).
3. **Expected:** status Menunggu approval; notifikasi ke approver.

### QC-02 — Approve → ordered

1. Login approver → **Persetujuan** → Setujui.
2. Login purchasing, buka PO.
3. **Expected:** status **Siap diterima (ordered)**.

### QC-03 — Goods Receipt & stok naik

1. Catat qty available Klem sebelum GR.
2. Login warehouse → detail PO ordered (seeder / hasil QC-02) → **Buat Goods Receipt**.
3. Pilih lokasi (mis. GU-01), rak B-02 atau GENERAL, qty sebagian.
4. **Expected:** stok Klem naik; ledger `goods_receipt`; PO `partially_received`.
5. GR sisa qty → status `received`.

### QC-04 — Reject flow

1. Buat PO baru, ajukan, login approver → **Tolak** + komentar.
2. **Expected:** PO `rejected`; purchasing bisa edit & ajukan ulang.

### QC-05 — Permission

1. Purchasing: buat PO, tidak wajib bisa GR (warehouse yang create GR).
2. Warehouse: lihat PO + buat GR; tidak membuat PO (kecuali diberi permission).
3. Approver: lihat PO + aksi approval.

### QC-05b — Unduh PDF & Excel

1. Buka detail PO apa pun.
2. Klik **Unduh PDF** → file PDF terunduh (header perusahaan, vendor, baris, total).
3. Di bagian bawah PDF ada **3 kotak tanda tangan**: Dibuat oleh / Disetujui oleh / Vendor.
4. Jika PO sudah di-approve: kolom Disetujui terisi nama approver + tanggal; Vendor tetap kosong untuk tanda tangan basah.
5. Klik **Unduh Excel** → file XLSX (sheet Header + Baris).
6. **Expected:** audit log mencatat `exported`.

### QC-06 — Label internal

1. Tidak ada teks “Phase 7” / “MVP” di UI.

---

## 5. Out of scope

- Purchase Request terpisah
- Attachment dokumen PO/GR
- Amount-based multi-step routing
- Stock In/Out/Transfer non-PO (Phase 8)

---

## 6. Approval Section

| Role | Decision | Date | Notes |
|---|---|---|---|
| Product Owner | ✅ APPROVED | 2026-08-14 | Chat: "approved" (termasuk unduh PDF/Excel + tanda tangan) |

Lanjut **Phase 8 — Stock In / Out / Transfer**.
