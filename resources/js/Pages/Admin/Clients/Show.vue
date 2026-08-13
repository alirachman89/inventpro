<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    client: Object,
    typeLabels: Object,
    activeBorrows: Array,
    borrowHistory: Array,
    assetsAtClient: Array,
});

const { can } = useCan();
</script>

<template>
    <Head :title="client.name" />

    <AuthenticatedLayout>
        <template #header-title>{{ client.name }}</template>
        <template #header-subtitle>
            Client {{ client.code }} · konteks pemakaian (bukan peminjam)
        </template>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <Link :href="route('admin.clients.index')" class="btn-ghost px-2 py-1">
                    ← Kembali ke daftar client
                </Link>
                <Link
                    v-if="can('clients.update')"
                    :href="route('admin.clients.edit', client.id)"
                    class="btn-primary"
                >
                    Edit Client
                </Link>
            </div>

            <div class="surface-card grid gap-3 p-5 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Tipe</p>
                    <p class="font-medium text-slate-800">
                        {{ typeLabels[client.type] || client.type }}
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Status</p>
                    <p class="font-medium text-slate-800">
                        {{ client.is_active ? 'Aktif' : 'Nonaktif' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">PIC</p>
                    <p class="font-medium text-slate-800">{{ client.contact_person || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Kontak</p>
                    <p class="font-medium text-slate-800">
                        {{ client.phone || '—' }} · {{ client.email || '—' }}
                    </p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Alamat / site</p>
                    <p class="font-medium text-slate-800">{{ client.address || '—' }}</p>
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-slate-800">
                        Unit asset di client ini
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Tag</th>
                                <th class="px-4 py-3">Barang</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Pemegang (karyawan)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="asset in assetsAtClient" :key="asset.id">
                                <td class="px-4 py-3">
                                    <Link
                                        :href="route('admin.asset-units.show', asset.id)"
                                        class="font-medium text-brand-700 hover:underline"
                                    >
                                        {{ asset.asset_tag }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ asset.item?.name || '—' }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ asset.status }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ asset.holder || '—' }}
                                </td>
                            </tr>
                            <tr v-if="!assetsAtClient.length">
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada unit asset yang menunjuk client ini
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="surface-card p-5">
                <h2 class="text-sm font-semibold text-slate-800">
                    Peminjaman aktif & histori
                </h2>
                <p class="mt-2 text-sm text-slate-500">
                    Belum ada data. Akan terisi setelah modul Peminjaman aktif (Scaffold →
                    Karyawan → Client).
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
