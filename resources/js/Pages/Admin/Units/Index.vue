<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { useConfirm } from '@/composables/useConfirm';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    units: Object,
    filters: Object,
});

const { can } = useCan();
const { confirm } = useConfirm();
const search = ref(props.filters.search || '');

watch(search, (value) => {
    router.get(
        route('admin.units.index'),
        { search: value || undefined },
        { preserveState: true, replace: true },
    );
});

async function destroyUnit(unit) {
    const ok = await confirm({
        title: 'Hapus satuan',
        message: `Hapus satuan ${unit.code} — ${unit.name}?`,
        confirmLabel: 'Hapus',
        variant: 'danger',
    });

    if (ok) {
        router.delete(route('admin.units.destroy', unit.id));
    }
}
</script>

<template>
    <Head title="Satuan (UOM)" />

    <AuthenticatedLayout>
        <template #header-title>Satuan (UOM)</template>
        <template #header-subtitle>Master unit of measure untuk item dan transaksi</template>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Cari kode atau nama..."
                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:max-w-xs"
                />
                <Link
                    v-if="can('units.create')"
                    :href="route('admin.units.create')"
                    class="btn-primary"
                >
                    Tambah Satuan
                </Link>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Kode</th>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Tipe</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="unit in units.data" :key="unit.id">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ unit.code }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ unit.name }}
                                    <span v-if="unit.symbol" class="text-slate-400">
                                        ({{ unit.symbol }})
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ unit.type }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="
                                            unit.is_active
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-slate-100 text-slate-500'
                                        "
                                    >
                                        {{ unit.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            v-if="can('units.update')"
                                            :href="route('admin.units.edit', unit.id)"
                                            class="btn-ghost px-2 py-1"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            v-if="can('units.delete')"
                                            type="button"
                                            class="btn-ghost px-2 py-1 text-danger"
                                            @click="destroyUnit(unit)"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!units.data.length">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada data
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    <Pagination :links="units.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
