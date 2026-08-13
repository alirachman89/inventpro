# InventPro — UI Conventions

| Field | Value |
|---|---|
| **Document** | UI Conventions |
| **Version** | 1.0.0 |
| **Status** | Aktif |
| **Applies to** | Semua halaman produk (Vue / Inertia) |

---

## 1. Label internal tidak boleh di UI

Label yang bersifat **internal delivery / project management** hanya boleh ada di dokumen (`docs/`), commit message, atau komunikasi tim — **tidak boleh ditampilkan di UI aplikasi**.

### Contoh label yang dilarang di UI

| Dilarang di UI | Boleh di dokumen |
|---|---|
| Phase 1, Phase 2, … Phase 12 | Ya |
| MVP / Should / Could / Must (prioritas backlog) | Ya |
| Approval Gate #N | Ya |
| “menyusul di phase berikutnya” | Ya |
| “placeholder Phase X” | Ya |
| “QC manual” sebagai label layar | Ya (hanya di panduan QC) |
| Nama file dokumen (`PHASE-01-DELIVERY.md`) | Ya |

### Contoh teks UI yang benar

| Salah | Benar |
|---|---|
| Ringkasan operasional (placeholder Phase 1) | Ringkasan operasional inventori |
| Phase 1 aktif | *(hapus / ganti info produk)* |
| Notifikasi (Phase 3) | Notifikasi |
| Akan muncul setelah phase terkait selesai | *(jangan tampilkan teaser internal)* |
| Foundation & Design System siap | Pantau stok dan aktivitas gudang dari satu tempat |
| Belum ada data — Phase 5 | Belum ada data |

---

## 2. Prinsip copywriting UI

1. Bahasa **produk** untuk end-user / operasional warehouse.  
2. Jangan bocorkan rencana roadmap, nomor phase, atau status dokumen.  
3. Fitur yang belum siap: **sembunyikan** dari menu/UI, jangan beri label “coming in Phase X”.  
4. Empty state memakai bahasa netral: “Belum ada data”, “Belum ada transaksi”, “Belum ada notifikasi”.  
5. Istilah bisnis (PO, Stock Opname, Client, Vendor) diperbolehkan karena itu bahasa domain.  
6. Elemen UI yang sudah menjadi bagian shell produk (contoh: **icon bell notifikasi** di topbar) **tetap ditampilkan** meski backend-nya belum penuh — gunakan empty state netral, jangan disembunyikan hanya karena nomor phase belum selesai.

---

## 3. Di mana label Phase / MVP tetap dipakai

- `docs/01-BRD-PRD.md`
- `docs/02-DELIVERY-PLAN.md`
- `docs/phases/PHASE-XX-DELIVERY.md`
- `docs/approvals/APPROVAL-LOG.md`
- `docs/LOGIN-CREDENTIALS.md` (boleh menyebut phase untuk konteks tim)
- Komentar kode *hanya jika perlu* untuk developer — hindari string yang bisa bocor ke UI

---

## 4. Checklist sebelum merge / demo

- [ ] Tidak ada teks “Phase …”, “MVP”, “Approval Gate”, “QC” di string UI yang terlihat user
- [ ] Menu hanya menampilkan fitur yang sudah berfungsi
- [ ] Tooltip / title / aria-label juga bebas label internal
- [ ] Screenshot demo tidak menampilkan badge internal

---

## 5. Referensi implementasi

Perubahan awal diterapkan pada:

- `resources/js/Layouts/AuthenticatedLayout.vue`
- `resources/js/Pages/Dashboard.vue`
