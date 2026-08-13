<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { useConfirm } from '@/composables/useConfirm';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    items: Object,
    filters: Object,
    locations: Array,
});

const { can } = useCan();
const { confirm } = useConfirm();
const search = ref(props.filters.search || '');
const locationId = ref(props.filters.location_id || '');
const rack = ref(props.filters.rack || '');

function applyFilters() {
    router.get(
        route('admin.items.index'),
        {
            search: search.value || undefined,
            location_id: locationId.value || undefined,
            rack: rack.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

watch([search, locationId, rack], applyFilters);

async function destroyItem(item) {
    const ok = await confirm({
        title: 'Hapus barang',
        message: `Hapus barang ${item.sku} — ${item.name}?`,
        confirmLabel: 'Hapus',
        variant: 'danger',
    });

    if (ok) {
        router.delete(route('admin.items.destroy', item.id));
    }
}
</script>

<template>
    <Head title="Barang" />

    <AuthenticatedLayout>
        <template #header-title>Barang</template>
        <template #header-subtitle>Master item, stok per lokasi/rak, dan unit asset</template>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="grid flex-1 gap-3 sm:grid-cols-3">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari nama / SKU..."
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                    <select
                        v-model="locationId"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
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
                    <input
                        v-model="rack"
                        type="search"
                        placeholder="Filter rak (kode/label)..."
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                </div>
                <Link
                    v-if="can('items.create')"
                    :href="route('admin.items.create')"
                    class="btn-primary shrink-0"
                >
                    Tambah Barang
                </Link>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">SKU</th>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Tipe</th>
                                <th class="px-4 py-3">Available</th>
                                <th class="px-4 py-3">Min</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="item in items.data" :key="item.id">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ item.sku }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    <div>{{ item.name }}</div>
                                    <div class="text-xs text-slate-400">
                                        {{ item.category || '—' }} · {{ item.uom || '—' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ item.item_type }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="
                                            item.is_low_stock
                                                ? 'font-semibold text-amber-700'
                                                : 'text-slate-800'
                                        "
                                    >
                                        {{ item.qty_available }}
                                    </span>
                                    <span
                                        v-if="item.is_low_stock"
                                        class="ml-2 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700"
                                    >
                                        Stok rendah
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ item.min_stock }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            :href="route('admin.items.show', item.id)"
                                            class="btn-ghost px-2 py-1"
                                        >
                                            Detail
                                        </Link>
                                        <Link
                                            v-if="can('items.update')"
                                            :href="route('admin.items.edit', item.id)"
                                            class="btn-ghost px-2 py-1"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            v-if="can('items.delete')"
                                            type="button"
                                            class="btn-ghost px-2 py-1 text-danger"
                                            @click="destroyItem(item)"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!items.data.length">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada data
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    <Pagination :links="items.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
