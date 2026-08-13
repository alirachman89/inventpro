<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    movements: Object,
    filters: Object,
    types: Array,
    typeLabels: Object,
    reasonLabels: Object,
});

const { can } = useCan();
const search = ref(props.filters.search || '');
const type = ref(props.filters.type || '');

watch([search, type], () => {
    router.get(
        route('admin.stock-movements.index'),
        {
            search: search.value || undefined,
            type: type.value || undefined,
        },
        { preserveState: true, replace: true },
    );
});
</script>

<template>
    <Head title="Mutasi Stok" />

    <AuthenticatedLayout>
        <template #header-title>Mutasi Stok</template>
        <template #header-subtitle>Stock In / Out / Transfer (lokasi + rak)</template>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="grid flex-1 gap-3 sm:grid-cols-2">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari nomor MOV..."
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                    <select
                        v-model="type"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">Semua tipe</option>
                        <option v-for="item in types" :key="item" :value="item">
                            {{ typeLabels[item] || item }}
                        </option>
                    </select>
                </div>
                <div v-if="can('stock_movements.create')" class="flex flex-wrap gap-2">
                    <Link
                        :href="route('admin.stock-movements.create', { type: 'in' })"
                        class="btn-primary"
                    >
                        Stock In
                    </Link>
                    <Link
                        :href="route('admin.stock-movements.create', { type: 'out' })"
                        class="btn-secondary"
                    >
                        Stock Out
                    </Link>
                    <Link
                        :href="route('admin.stock-movements.create', { type: 'transfer' })"
                        class="btn-secondary"
                    >
                        Transfer
                    </Link>
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Nomor</th>
                                <th class="px-4 py-3">Tipe</th>
                                <th class="px-4 py-3">Alasan</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Baris</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="movement in movements.data" :key="movement.id">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ movement.number }}
                                </td>
                                <td class="px-4 py-3">
                                    {{ typeLabels[movement.type] || movement.type }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ reasonLabels[movement.reason] || movement.reason }}
                                    <div
                                        v-if="movement.client"
                                        class="text-xs text-slate-400"
                                    >
                                        Client: {{ movement.client.code }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ movement.movement_date }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ movement.lines_count }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="
                                            route('admin.stock-movements.show', movement.id)
                                        "
                                        class="btn-ghost px-2 py-1"
                                    >
                                        Detail
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!movements.data.length">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada mutasi
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    <Pagination :links="movements.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
