<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { useConfirm } from '@/composables/useConfirm';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    order: Object,
    statusLabels: Object,
});

const { can } = useCan();
const { confirm } = useConfirm();

async function submitPo() {
    const ok = await confirm({
        title: 'Ajukan PO',
        message: `Ajukan ${props.order.number} ke approval?`,
        confirmLabel: 'Ajukan',
    });

    if (ok) {
        router.post(route('admin.purchase-orders.submit', props.order.id));
    }
}

async function destroyPo() {
    const ok = await confirm({
        title: 'Hapus PO',
        message: `Hapus ${props.order.number}?`,
        confirmLabel: 'Hapus',
        variant: 'danger',
    });

    if (ok) {
        router.delete(route('admin.purchase-orders.destroy', props.order.id));
    }
}
</script>

<template>
    <Head :title="order.number" />

    <AuthenticatedLayout>
        <template #header-title>{{ order.number }}</template>
        <template #header-subtitle>
            {{ order.vendor?.name }} ·
            {{ statusLabels[order.status] || order.status }}
        </template>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <Link :href="route('admin.purchase-orders.index')" class="btn-ghost px-2 py-1">
                    ← Daftar PO
                </Link>
                <div class="flex flex-wrap gap-2">
                    <a
                        v-if="can('purchases.export')"
                        :href="route('admin.purchase-orders.export-pdf', order.id)"
                        class="btn-secondary"
                        target="_blank"
                        rel="noopener"
                    >
                        Unduh PDF
                    </a>
                    <a
                        v-if="can('purchases.export')"
                        :href="route('admin.purchase-orders.export-excel', order.id)"
                        class="btn-secondary"
                    >
                        Unduh Excel
                    </a>
                    <Link
                        v-if="order.can_edit && can('purchases.update')"
                        :href="route('admin.purchase-orders.edit', order.id)"
                        class="btn-secondary"
                    >
                        Edit
                    </Link>
                    <button
                        v-if="order.can_submit && can('purchases.submit')"
                        type="button"
                        class="btn-primary"
                        @click="submitPo"
                    >
                        Ajukan Approval
                    </button>
                    <Link
                        v-if="order.can_receive && can('goods_receipts.create')"
                        :href="route('admin.goods-receipts.create', order.id)"
                        class="btn-primary"
                    >
                        Buat Goods Receipt
                    </Link>
                    <button
                        v-if="can('purchases.delete') && order.can_edit"
                        type="button"
                        class="btn-ghost text-danger"
                        @click="destroyPo"
                    >
                        Hapus
                    </button>
                </div>
            </div>

            <div class="surface-card grid gap-3 p-5 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Vendor</p>
                    <p class="font-medium text-slate-800">
                        {{ order.vendor?.code }} — {{ order.vendor?.name }}
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Tanggal</p>
                    <p class="font-medium text-slate-800">{{ order.order_date }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Total</p>
                    <p class="font-medium text-slate-800">
                        {{
                            Number(order.total_amount).toLocaleString('id-ID', {
                                style: 'currency',
                                currency: 'IDR',
                                maximumFractionDigits: 0,
                            })
                        }}
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Dibuat oleh</p>
                    <p class="font-medium text-slate-800">{{ order.creator || '—' }}</p>
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-slate-800">Baris PO</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Item</th>
                                <th class="px-4 py-3 text-right">Ordered</th>
                                <th class="px-4 py-3 text-right">Received</th>
                                <th class="px-4 py-3 text-right">Sisa</th>
                                <th class="px-4 py-3 text-right">Harga</th>
                                <th class="px-4 py-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="line in order.lines" :key="line.id">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">
                                        {{ line.item?.name }}
                                    </div>
                                    <div class="text-xs text-slate-400">
                                        {{ line.item?.sku }} · {{ line.item?.uom }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">{{ line.qty_ordered }}</td>
                                <td class="px-4 py-3 text-right">{{ line.qty_received }}</td>
                                <td class="px-4 py-3 text-right font-medium">
                                    {{ line.qty_outstanding }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ Number(line.unit_price).toLocaleString('id-ID') }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ Number(line.line_total).toLocaleString('id-ID') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-slate-800">Goods Receipt</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Nomor</th>
                                <th class="px-4 py-3">Lokasi</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Penerima</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="receipt in order.receipts" :key="receipt.id">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ receipt.number }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ receipt.location?.code }} —
                                    {{ receipt.location?.name }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ receipt.received_date }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ receipt.receiver || '—' }}
                                </td>
                            </tr>
                            <tr v-if="!order.receipts.length">
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada penerimaan
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
