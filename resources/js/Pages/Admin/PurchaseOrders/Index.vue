<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    orders: Object,
    filters: Object,
    statuses: Array,
    statusLabels: Object,
});

const { can } = useCan();
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

watch([search, status], () => {
    router.get(
        route('admin.purchase-orders.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
});
</script>

<template>
    <Head title="Purchase Order" />

    <AuthenticatedLayout>
        <template #header-title>Purchase Order</template>
        <template #header-subtitle>PO ke vendor → approval → Goods Receipt</template>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="grid flex-1 gap-3 sm:grid-cols-2">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari nomor / vendor..."
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                    <select
                        v-model="status"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">Semua status</option>
                        <option v-for="item in statuses" :key="item" :value="item">
                            {{ statusLabels[item] || item }}
                        </option>
                    </select>
                </div>
                <Link
                    v-if="can('purchases.create')"
                    :href="route('admin.purchase-orders.create')"
                    class="btn-primary shrink-0"
                >
                    Buat PO
                </Link>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Nomor</th>
                                <th class="px-4 py-3">Vendor</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Total</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="order in orders.data" :key="order.id">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ order.number }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ order.vendor?.name }}
                                    <div class="text-xs text-slate-400">
                                        {{ order.vendor?.code }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ order.order_date }}</td>
                                <td class="px-4 py-3 text-slate-800">
                                    {{
                                        Number(order.total_amount).toLocaleString('id-ID', {
                                            style: 'currency',
                                            currency: 'IDR',
                                            maximumFractionDigits: 0,
                                        })
                                    }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700"
                                    >
                                        {{ statusLabels[order.status] || order.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="route('admin.purchase-orders.show', order.id)"
                                        class="btn-ghost px-2 py-1"
                                    >
                                        Detail
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!orders.data.length">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada PO
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    <Pagination :links="orders.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
