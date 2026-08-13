# Change Request — Global Search + Work Queue

| Field | Value |
|---|---|
| **CR** | Post Phase 12 ops productivity |
| **Status** | ✅ Implemented |
| **Date** | 2026-08-14 |
| **Requested by** | Product Owner (diskusi dashboard) |

## Scope

1. **Global Search** (`Ctrl/Cmd+K` + tombol Cari di topbar)
   - Cari: item/SKU/barcode, asset tag/serial, PO/GR/MOV/OPN/BRW, vendor, client, lokasi, rak
   - Hasil dikelompokkan + permission-aware
   - Endpoint: `GET /search?q=`

2. **Work Queue** di Dashboard
   - GR siap (PO ordered / partially_received)
   - Pinjam siap checkout (approved)
   - Pinjam overdue (return)
   - Opname draft/rejected & siap posting
   - Antrean approval (jika `approvals.act`)

## Files

- `app/Services/GlobalSearchService.php`
- `app/Http/Controllers/SearchController.php`
- `resources/js/Components/GlobalSearch.vue`
- `app/Services/DashboardService.php` (`work_queue`)
- `resources/js/Pages/Dashboard.vue`
- `resources/js/Layouts/AuthenticatedLayout.vue`

## QC singkat

1. Login warehouse → tekan `Ctrl+K` → cari `KLEM` / `AST-FRAME` / `PO` / `BRW`.
2. Dashboard menampilkan **Antrian kerja hari ini** dengan link aksi.
3. Viewer: search hanya modul yang boleh dilihat; work queue lebih tipis.
