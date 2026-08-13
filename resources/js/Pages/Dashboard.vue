<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useConfirm } from '@/composables/useConfirm';
import { useToast } from '@/composables/useToast';
import { Head } from '@inertiajs/vue3';

const toast = useToast();
const { confirm } = useConfirm();

async function demoToast(type) {
    if (type === 'success') {
        toast.success('Berhasil', 'Perubahan berhasil disimpan.');
    } else if (type === 'error') {
        toast.error('Gagal', 'Terjadi kesalahan. Silakan coba lagi.');
    } else if (type === 'warning') {
        toast.warning('Perhatian', 'Periksa kembali data sebelum melanjutkan.');
    } else {
        toast.info('Informasi', 'Data stok akan diperbarui secara berkala.');
    }
}

async function demoConfirm() {
    const ok = await confirm({
        title: 'Konfirmasi aksi',
        message:
            'Apakah Anda yakin ingin melanjutkan? Tindakan ini dapat memengaruhi data inventori.',
        confirmLabel: 'Ya, lanjutkan',
        cancelLabel: 'Batal',
        variant: 'warning',
    });

    if (ok) {
        toast.success('Dikonfirmasi', 'Aksi berhasil dilanjutkan.');
    } else {
        toast.info('Dibatalkan', 'Tidak ada perubahan.');
    }
}
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header-title>Dashboard</template>
        <template #header-subtitle>Ringkasan operasional inventori</template>

        <div class="space-y-6">
            <div>
                <h1 class="page-title">Selamat datang di InventPro</h1>
                <p class="page-subtitle">
                    Pantau stok, transaksi, dan aktivitas gudang dari satu
                    tempat.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="surface-card p-5">
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                    >
                        Total Item
                    </p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">—</p>
                    <p class="mt-2 text-xs text-slate-500">Belum ada data</p>
                </div>
                <div class="surface-card p-5">
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                    >
                        Open PO
                    </p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">—</p>
                    <p class="mt-2 text-xs text-slate-500">Belum ada data</p>
                </div>
                <div class="surface-card p-5">
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                    >
                        Peminjaman Aktif
                    </p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">—</p>
                    <p class="mt-2 text-xs text-slate-500">Belum ada data</p>
                </div>
                <div class="surface-card p-5">
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                    >
                        Low Stock
                    </p>
                    <p class="mt-3 text-3xl font-bold text-amber-600">—</p>
                    <p class="mt-2 text-xs text-slate-500">Belum ada data</p>
                </div>
            </div>

            <div class="surface-card p-6">
                <h2 class="text-lg font-semibold text-slate-900">
                    Contoh notifikasi & konfirmasi
                </h2>
                <p class="mt-1 text-sm text-slate-600">
                    Pratinjau tampilan pesan sukses, peringatan, dan dialog
                    konfirmasi di aplikasi.
                </p>

                <div class="mt-5 flex flex-wrap gap-3">
                    <button
                        type="button"
                        class="btn-primary"
                        @click="demoToast('success')"
                    >
                        Toast Success
                    </button>
                    <button
                        type="button"
                        class="btn-secondary"
                        @click="demoToast('info')"
                    >
                        Toast Info
                    </button>
                    <button
                        type="button"
                        class="btn-warning"
                        @click="demoToast('warning')"
                    >
                        Toast Warning
                    </button>
                    <button
                        type="button"
                        class="btn-danger"
                        @click="demoToast('error')"
                    >
                        Toast Error
                    </button>
                    <button
                        type="button"
                        class="btn-ghost"
                        @click="demoConfirm"
                    >
                        Buka Confirm Modal
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
