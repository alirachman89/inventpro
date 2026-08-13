<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    vendor: Object,
    purchaseHistory: Array,
});

const { can } = useCan();
</script>

<template>
    <Head :title="vendor.name" />

    <AuthenticatedLayout>
        <template #header-title>{{ vendor.name }}</template>
        <template #header-subtitle>Vendor {{ vendor.code }} · Pemasok (inbound)</template>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <Link :href="route('admin.vendors.index')" class="btn-ghost px-2 py-1">
                    ← Kembali ke daftar vendor
                </Link>
                <Link
                    v-if="can('vendors.update')"
                    :href="route('admin.vendors.edit', vendor.id)"
                    class="btn-primary"
                >
                    Edit Vendor
                </Link>
            </div>

            <div class="surface-card grid gap-3 p-5 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Status</p>
                    <p class="font-medium text-slate-800">
                        {{ vendor.is_active ? 'Aktif' : 'Nonaktif' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">NPWP</p>
                    <p class="font-medium text-slate-800">{{ vendor.tax_id || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">PIC</p>
                    <p class="font-medium text-slate-800">{{ vendor.contact_person || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Kontak</p>
                    <p class="font-medium text-slate-800">
                        {{ vendor.phone || '—' }} · {{ vendor.email || '—' }}
                    </p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Alamat</p>
                    <p class="font-medium text-slate-800">{{ vendor.address || '—' }}</p>
                </div>
                <div v-if="vendor.notes" class="sm:col-span-2">
                    <p class="text-xs uppercase tracking-wide text-slate-400">Catatan</p>
                    <p class="font-medium text-slate-800">{{ vendor.notes }}</p>
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-slate-800">Histori PO</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Nomor</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="po in purchaseHistory" :key="po.id">
                                <td class="px-4 py-3">
                                    <Link
                                        :href="route('admin.purchase-orders.show', po.id)"
                                        class="font-medium text-brand-700 hover:underline"
                                    >
                                        {{ po.number }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ po.order_date }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ po.status }}</td>
                                <td class="px-4 py-3 text-right">
                                    {{ Number(po.total_amount).toLocaleString('id-ID') }}
                                </td>
                            </tr>
                            <tr v-if="!purchaseHistory.length">
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada histori PO
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
