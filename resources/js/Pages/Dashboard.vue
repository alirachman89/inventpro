<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    filters: Object,
    locations: Array,
    kpis: Object,
    asset_status: Array,
    mutation_chart: Array,
    top_items: Array,
    alerts: Array,
    overdue_borrows: Array,
    work_queue: Array,
    period_label: String,
});

const { can } = useCan();

const locationId = ref(props.filters.location_id || '');
const days = ref(props.filters.days || 30);

watch([locationId, days], () => {
    router.get(
        route('dashboard'),
        {
            location_id: locationId.value || undefined,
            days: days.value,
        },
        { preserveState: true, replace: true },
    );
});

const chartMax = computed(() => {
    const values = props.mutation_chart.flatMap((row) => [row.inbound, row.outbound]);
    return Math.max(1, ...values);
});

const assetMax = computed(() =>
    Math.max(1, ...props.asset_status.map((row) => row.count)),
);

const topMax = computed(() =>
    Math.max(1, ...props.top_items.map((row) => row.movement)),
);

const formatNumber = (value) =>
    Number(value).toLocaleString('id-ID', { maximumFractionDigits: 2 });

const alertClass = (type) => {
    if (type === 'danger') return 'border-rose-200 bg-rose-50 text-rose-800';
    if (type === 'warning') return 'border-amber-200 bg-amber-50 text-amber-900';
    return 'border-sky-200 bg-sky-50 text-sky-900';
};

const queuePriorityClass = (priority) => {
    if (priority === 'critical') return 'bg-rose-100 text-rose-700';
    if (priority === 'high') return 'bg-amber-100 text-amber-800';
    return 'bg-slate-100 text-slate-600';
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header-title>Dashboard</template>
        <template #header-subtitle>KPI operasional · {{ period_label }}</template>

        <div class="space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="page-title">Ringkasan Inventori</h1>
                    <p class="page-subtitle">
                        Pantau stok, PO, pinjam, dan status asset dari satu tempat.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <select
                        v-model="locationId"
                        class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">Semua lokasi</option>
                        <option
                            v-for="location in locations"
                            :key="location.id"
                            :value="location.id"
                        >
                            {{ location.code }} — {{ location.name }}
                        </option>
                    </select>
                    <select
                        v-model="days"
                        class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option :value="7">7 hari</option>
                        <option :value="14">14 hari</option>
                        <option :value="30">30 hari</option>
                        <option :value="60">60 hari</option>
                    </select>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                <div class="surface-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Total Item
                    </p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">
                        {{ formatNumber(kpis.total_items) }}
                    </p>
                </div>
                <div class="surface-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Total Qty
                    </p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">
                        {{ formatNumber(kpis.total_qty) }}
                    </p>
                </div>
                <div class="surface-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Barang rendah
                    </p>
                    <p class="mt-3 text-3xl font-bold text-amber-600">
                        {{ formatNumber(kpis.low_stock) }}
                    </p>
                </div>
                <div class="surface-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Open PO
                    </p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">
                        {{ formatNumber(kpis.open_po) }}
                    </p>
                </div>
                <div class="surface-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Pinjam Aktif
                    </p>
                    <p class="mt-3 text-3xl font-bold text-slate-900">
                        {{ formatNumber(kpis.open_borrows) }}
                    </p>
                </div>
                <div class="surface-card p-5">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Overdue
                    </p>
                    <p class="mt-3 text-3xl font-bold text-rose-600">
                        {{ formatNumber(kpis.overdue_borrows) }}
                    </p>
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-900">
                        Antrian kerja hari ini
                    </h2>
                    <p class="text-xs text-slate-500">
                        Tugas operasional yang bisa langsung dikerjakan
                    </p>
                </div>
                <div v-if="work_queue?.length" class="divide-y divide-slate-100">
                    <component
                        :is="item.href ? Link : 'div'"
                        v-for="(item, index) in work_queue"
                        :key="index"
                        :href="item.href || undefined"
                        class="flex flex-col gap-2 px-5 py-3 hover:bg-slate-50 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                                    :class="queuePriorityClass(item.priority)"
                                >
                                    {{ item.priority }}
                                </span>
                                <p class="truncate text-sm font-medium text-slate-900">
                                    {{ item.title }}
                                </p>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ item.meta }}</p>
                        </div>
                        <span class="shrink-0 text-xs font-semibold text-brand-700">
                            {{ item.action }} →
                        </span>
                    </component>
                </div>
                <p v-else class="px-5 py-8 text-center text-sm text-slate-500">
                    Tidak ada antrian kerja untuk peran Anda saat ini
                </p>
            </div>

            <div class="grid gap-4 xl:grid-cols-3">
                <div class="surface-card space-y-4 p-5 xl:col-span-2">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-sm font-semibold text-slate-900">
                            Mutasi In vs Out
                        </h2>
                        <div class="flex gap-3 text-xs text-slate-500">
                            <span class="inline-flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-emerald-600" /> In
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span class="h-2 w-2 rounded-full bg-amber-500" /> Out
                            </span>
                        </div>
                    </div>
                    <div
                        v-if="mutation_chart.length"
                        class="flex h-48 items-end gap-1 overflow-x-auto"
                    >
                        <div
                            v-for="row in mutation_chart"
                            :key="row.date"
                            class="flex min-w-[18px] flex-1 flex-col items-center justify-end gap-1"
                            :title="`${row.date}: in ${row.inbound}, out ${row.outbound}`"
                        >
                            <div class="flex h-40 w-full items-end justify-center gap-0.5">
                                <div
                                    class="w-2 rounded-t bg-emerald-600"
                                    :style="{
                                        height: `${(row.inbound / chartMax) * 100}%`,
                                        minHeight: row.inbound > 0 ? '2px' : '0',
                                    }"
                                />
                                <div
                                    class="w-2 rounded-t bg-amber-500"
                                    :style="{
                                        height: `${(row.outbound / chartMax) * 100}%`,
                                        minHeight: row.outbound > 0 ? '2px' : '0',
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                    <p v-else class="py-10 text-center text-sm text-slate-500">
                        Belum ada mutasi pada periode ini
                    </p>
                </div>

                <div class="surface-card space-y-4 p-5">
                    <h2 class="text-sm font-semibold text-slate-900">Status Asset</h2>
                    <div class="space-y-3">
                        <div v-for="row in asset_status" :key="row.status">
                            <div class="mb-1 flex justify-between text-xs text-slate-600">
                                <span class="capitalize">{{ row.status }}</span>
                                <span class="font-medium text-slate-900">{{ row.count }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100">
                                <div
                                    class="h-2 rounded-full bg-brand-500"
                                    :style="{
                                        width: `${(row.count / assetMax) * 100}%`,
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
                <div class="surface-card p-5">
                    <h2 class="text-sm font-semibold text-slate-900">
                        Top item bergerak
                    </h2>
                    <div v-if="top_items.length" class="mt-4 space-y-3">
                        <div v-for="item in top_items" :key="item.sku">
                            <div class="mb-1 flex justify-between gap-2 text-sm">
                                <span class="truncate text-slate-700">
                                    {{ item.sku }} — {{ item.name }}
                                </span>
                                <span class="shrink-0 font-medium text-slate-900">
                                    {{ formatNumber(item.movement) }}
                                </span>
                            </div>
                            <div class="h-2 rounded-full bg-slate-100">
                                <div
                                    class="h-2 rounded-full bg-slate-700"
                                    :style="{
                                        width: `${(item.movement / topMax) * 100}%`,
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                    <p v-else class="mt-6 text-sm text-slate-500">
                        Belum ada pergerakan signifikan
                    </p>
                </div>

                <div class="surface-card p-5">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-semibold text-slate-900">Alert</h2>
                        <Link
                            v-if="can('reports.view')"
                            :href="route('admin.reports.index')"
                            class="text-xs text-brand-700 hover:underline"
                        >
                            Laporan
                        </Link>
                    </div>
                    <div v-if="alerts.length" class="mt-4 space-y-2">
                        <component
                            :is="alert.href ? Link : 'div'"
                            v-for="(alert, index) in alerts"
                            :key="index"
                            :href="alert.href || undefined"
                            class="block rounded-xl border px-3 py-2 text-sm"
                            :class="alertClass(alert.type)"
                        >
                            <p class="font-medium">{{ alert.title }}</p>
                            <p class="mt-0.5 opacity-90">{{ alert.message }}</p>
                        </component>
                    </div>
                    <p v-else class="mt-6 text-sm text-slate-500">
                        Tidak ada alert saat ini
                    </p>
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-900">
                        Peminjaman overdue
                    </h2>
                    <Link
                        v-if="can('borrows.view')"
                        :href="route('admin.borrows.index', { overdue: 1 })"
                        class="text-xs text-brand-700 hover:underline"
                    >
                        Lihat semua
                    </Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead
                            class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            <tr>
                                <th class="px-4 py-3">Nomor</th>
                                <th class="px-4 py-3">Peminjam</th>
                                <th class="px-4 py-3">Client</th>
                                <th class="px-4 py-3">Jatuh tempo</th>
                                <th class="px-4 py-3">Hari</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="borrow in overdue_borrows" :key="borrow.id">
                                <td class="px-4 py-3">
                                    <Link
                                        v-if="can('borrows.view')"
                                        :href="route('admin.borrows.show', borrow.id)"
                                        class="font-medium text-brand-700 hover:underline"
                                    >
                                        {{ borrow.number }}
                                    </Link>
                                    <span v-else class="font-medium text-slate-900">
                                        {{ borrow.number }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ borrow.borrower }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ borrow.client }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ borrow.due_date }}</td>
                                <td class="px-4 py-3 font-medium text-rose-600">
                                    +{{ borrow.days_overdue }}
                                </td>
                            </tr>
                            <tr v-if="!overdue_borrows.length">
                                <td
                                    colspan="5"
                                    class="px-4 py-8 text-center text-slate-500"
                                >
                                    Tidak ada peminjaman overdue
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
