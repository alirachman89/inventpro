# Change Request — Barcode Generate + Scan (Full)

| Field | Value |
|---|---|
| **CR** | Post Phase 12 — barcode ops |
| **Status** | ✅ Full Implemented (A–E) |
| **Date** | 2026-08-14 |
| **Requested by** | Product Owner |

## Decisions

- Generate: tombol manual di Form Item (`INV` + SKU, unik, CODE128)
- Scan ganda item (Mutasi / GR / Opname): **qty +1**
- Borrow asset: scan tag/serial; duplikat diabaikan
- Camera phone: belum (USB/keyboard wedge + Enter)

## Scope

| Slice | Modul | Perilaku |
|---|---|---|
| A | Master Item | Generate + preview + cetak label |
| B | API | `items.lookup`, `items.barcode.generate`, `asset-units.lookup` |
| C | Komponen | `BarcodePreview`, `BarcodeScanInput` (item / asset / custom) |
| D1 | Mutasi | Scan → isi baris / qty+1 |
| D2 | Goods Receipt | Scan item PO → qty diterima +1 (cap outstanding); qty awal 0 |
| D3 | Stock Opname | Scan → qty fisik +1 (baris pertama yang match) |
| D4 | Borrow | Scan asset tag / serial → isi unit available |
| E | Asset Show | Preview + cetak label `asset_tag` |

## QC singkat

1. Item: Generate → Show → Cetak label.
2. Mutasi: scan `KLEM-001` / barcode → qty+1.
3. GR pada PO: scan SKU baris → qty naik; item di luar PO → not found.
4. Opname draft: scan SKU → qty fisik +1.
5. Borrow: scan `AST-FRAME-007` (available) → baris terisi.
6. Asset Show: barcode `asset_tag` + Cetak label.
