<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

const props = defineProps({
    report: Object,
    filters: Object,
    options: Object,
    canExport: Boolean,
});

const form = reactive({
    location_id: props.filters.location_id || '',
    rack_id: props.filters.rack_id || '',
    category_id: props.filters.category_id || '',
    vendor_id: props.filters.vendor_id || '',
    client_id: props.filters.client_id || '',
    borrower_user_id: props.filters.borrower_user_id || '',
    status: props.filters.status || '',
    movement_type: props.filters.movement_type || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    overdue: !!props.filters.overdue,
});

const racksForLocation = computed(() => {
    if (!form.location_id) return props.options.racks || [];
    return (props.options.racks || []).filter((rack) => rack.location_id === form.location_id);
});

const show = (keys) => keys.includes(props.report.key);

const applyFilters = () => {
    const payload = {};
    Object.entries(form).forEach(([key, value]) => {
        if (value === '' || value === false || value === null || value === undefined) return;
        payload[key] = value === true ? 1 : value;
    });
    router.get(route('admin.reports.show', props.report.key), payload, {
        preserveState: true,
        replace: true,
    });
};

watch(
    () => form.location_id,
    () => {
        if (
            form.rack_id &&
            !racksForLocation.value.find((rack) => rack.id === form.rack_id)
        ) {
            form.rack_id = '';
        }
    },
);

const queryString = () => {
    const params = new URLSearchParams();
    Object.entries(form).forEach(([key, value]) => {
        if (value === '' || value === false || value === null || value === undefined) return;
        params.set(key, value === true ? '1' : String(value));
    });
    const qs = params.toString();
    return qs ? `?${qs}` : '';
};

const excelUrl = computed(
    () => route('admin.reports.export-excel', props.report.key) + queryString(),
);
const pdfUrl = computed(
    () => route('admin.reports.export-pdf', props.report.key) + queryString(),
);
</script>

<template>
    <Head :title="report.title" />

    <AuthenticatedLayout>
        <template #header-title>{{ report.title }}</template>
        <template #header-subtitle>{{ report.rows.length }} baris</template>

        <div class="space-y-4">
            <div class="flex flex-wrap gap-2">
                <Link :href="route('admin.reports.index')" class="btn-secondary">
                    ← Semua laporan
                </Link>
                <a v-if="canExport" :href="excelUrl" class="btn-secondary">Unduh Excel</a>
                <a v-if="canExport" :href="pdfUrl" class="btn-secondary">Unduh PDF</a>
            </div>

            <div class="surface-card space-y-3 p-4">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-if="show(['stock', 'mutations', 'opnames', 'assets'])">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Lokasi</label>
                        <select
                            v-model="form.location_id"
                            class="w-full rounded-xl border-slate-300 text-sm"
                        >
                            <option value="">Semua</option>
                            <option
                                v-for="location in options.locations"
                                :key="location.id"
                                :value="location.id"
                            >
                                {{ location.code }}
                            </option>
                        </select>
                    </div>
                    <div v-if="show(['stock', 'mutations'])">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Rak</label>
                        <select
                            v-model="form.rack_id"
                            class="w-full rounded-xl border-slate-300 text-sm"
                        >
                            <option value="">Semua</option>
                            <option
                                v-for="rack in racksForLocation"
                                :key="rack.id"
                                :value="rack.id"
                            >
                                {{ rack.code }}
                            </option>
                        </select>
                    </div>
                    <div v-if="show(['stock'])">
                        <label class="mb-1 block text-xs font-medium text-slate-600">
                            Kategori
                        </label>
                        <select
                            v-model="form.category_id"
                            class="w-full rounded-xl border-slate-300 text-sm"
                        >
                            <option value="">Semua</option>
                            <option
                                v-for="category in options.categories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.code }}
                            </option>
                        </select>
                    </div>
                    <div v-if="show(['purchases'])">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Vendor</label>
                        <select
                            v-model="form.vendor_id"
                            class="w-full rounded-xl border-slate-300 text-sm"
                        >
                            <option value="">Semua</option>
                            <option
                                v-for="vendor in options.vendors"
                                :key="vendor.id"
                                :value="vendor.id"
                            >
                                {{ vendor.code }}
                            </option>
                        </select>
                    </div>
                    <div v-if="show(['borrows', 'assets'])">
                        <label class="mb-1 block text-xs font-medium text-slate-600">
                            Peminjam / Holder
                        </label>
                        <select
                            v-model="form.borrower_user_id"
                            class="w-full rounded-xl border-slate-300 text-sm"
                        >
                            <option value="">Semua</option>
                            <option
                                v-for="user in options.borrowers"
                                :key="user.id"
                                :value="user.id"
                            >
                                {{ user.name }}
                            </option>
                        </select>
                    </div>
                    <div v-if="show(['borrows', 'assets'])">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Client</label>
                        <select
                            v-model="form.client_id"
                            class="w-full rounded-xl border-slate-300 text-sm"
                        >
                            <option value="">Semua</option>
                            <option
                                v-for="client in options.clients"
                                :key="client.id"
                                :value="client.id"
                            >
                                {{ client.code }}
                            </option>
                        </select>
                    </div>
                    <div v-if="show(['purchases', 'opnames', 'borrows', 'assets'])">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Status</label>
                        <select
                            v-model="form.status"
                            class="w-full rounded-xl border-slate-300 text-sm"
                        >
                            <option value="">Semua</option>
                            <option
                                v-for="status in show(['purchases'])
                                    ? options.poStatuses
                                    : show(['opnames'])
                                      ? options.opnameStatuses
                                      : show(['borrows'])
                                        ? options.borrowStatuses
                                        : options.assetStatuses"
                                :key="status"
                                :value="status"
                            >
                                {{ status }}
                            </option>
                        </select>
                    </div>
                    <div v-if="show(['mutations', 'purchases', 'opnames'])">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Dari</label>
                        <input
                            v-model="form.date_from"
                            type="date"
                            class="w-full rounded-xl border-slate-300 text-sm"
                        />
                    </div>
                    <div v-if="show(['mutations', 'purchases', 'opnames'])">
                        <label class="mb-1 block text-xs font-medium text-slate-600">Sampai</label>
                        <input
                            v-model="form.date_to"
                            type="date"
                            class="w-full rounded-xl border-slate-300 text-sm"
                        />
                    </div>
                    <div
                        v-if="show(['borrows'])"
                        class="flex items-end pb-2 text-sm text-slate-700"
                    >
                        <label class="flex items-center gap-2">
                            <input
                                v-model="form.overdue"
                                type="checkbox"
                                class="rounded border-slate-300 text-brand-600"
                            />
                            Hanya overdue
                        </label>
                    </div>
                </div>
                <button type="button" class="btn-primary" @click="applyFilters">
                    Terapkan filter
                </button>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead
                            class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            <tr>
                                <th
                                    v-for="column in report.columns"
                                    :key="column"
                                    class="px-3 py-3"
                                >
                                    {{ column }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="(row, index) in report.rows" :key="index">
                                <td
                                    v-for="(cell, cellIndex) in row"
                                    :key="cellIndex"
                                    class="whitespace-nowrap px-3 py-2 text-slate-700"
                                >
                                    {{ cell }}
                                </td>
                            </tr>
                            <tr v-if="!report.rows.length">
                                <td
                                    :colspan="report.columns.length"
                                    class="px-4 py-8 text-center text-slate-500"
                                >
                                    Tidak ada data untuk filter ini
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
