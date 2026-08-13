<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    opnames: Object,
    filters: Object,
    statuses: Array,
    statusLabels: Object,
});

const { can } = useCan();
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

watch([search, status], () => {
    router.get(
        route('admin.stock-opnames.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
});
</script>

<template>
    <Head title="Stock Opname" />

    <AuthenticatedLayout>
        <template #header-title>Stock Opname</template>
        <template #header-subtitle>Hitung fisik → selisih → approval → posting</template>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="grid flex-1 gap-3 sm:grid-cols-2">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari nomor OPN..."
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
                    v-if="can('stock_opnames.create')"
                    :href="route('admin.stock-opnames.create')"
                    class="btn-primary"
                >
                    Buat sesi
                </Link>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead
                            class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            <tr>
                                <th class="px-4 py-3">Nomor</th>
                                <th class="px-4 py-3">Lokasi</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Baris / Selisih</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="opname in opnames.data" :key="opname.id">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ opname.number }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ opname.location?.code }} — {{ opname.location?.name }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ opname.opname_date }}</td>
                                <td class="px-4 py-3">
                                    {{ statusLabels[opname.status] || opname.status }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ opname.lines_count }} /
                                    <span
                                        :class="
                                            opname.variance_lines_count
                                                ? 'font-medium text-amber-700'
                                                : ''
                                        "
                                    >
                                        {{ opname.variance_lines_count }} selisih
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="route('admin.stock-opnames.show', opname.id)"
                                        class="btn-ghost px-2 py-1"
                                    >
                                        Detail
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!opnames.data.length">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada sesi opname
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    <Pagination :links="opnames.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
