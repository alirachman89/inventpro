<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { useConfirm } from '@/composables/useConfirm';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    vendors: Object,
    filters: Object,
});

const { can } = useCan();
const { confirm } = useConfirm();
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

watch([search, status], () => {
    router.get(
        route('admin.vendors.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
});

async function destroyVendor(vendor) {
    const ok = await confirm({
        title: 'Hapus vendor',
        message: `Hapus vendor ${vendor.code} — ${vendor.name}?`,
        confirmLabel: 'Hapus',
        variant: 'danger',
    });

    if (ok) {
        router.delete(route('admin.vendors.destroy', vendor.id));
    }
}
</script>

<template>
    <Head title="Vendor" />

    <AuthenticatedLayout>
        <template #header-title>Vendor</template>
        <template #header-subtitle>Master pemasok barang (inbound)</template>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="grid flex-1 gap-3 sm:grid-cols-2">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari kode / nama / kontak..."
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                    <select
                        v-model="status"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">Semua status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
                <Link
                    v-if="can('vendors.create')"
                    :href="route('admin.vendors.create')"
                    class="btn-primary shrink-0"
                >
                    Tambah Vendor
                </Link>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Kode</th>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Kontak</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="vendor in vendors.data" :key="vendor.id">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ vendor.code }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ vendor.name }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    <div>{{ vendor.contact_person || '—' }}</div>
                                    <div class="text-xs text-slate-400">
                                        {{ vendor.phone || vendor.email || '' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="
                                            vendor.is_active
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-slate-100 text-slate-500'
                                        "
                                    >
                                        {{ vendor.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            :href="route('admin.vendors.show', vendor.id)"
                                            class="btn-ghost px-2 py-1"
                                        >
                                            Detail
                                        </Link>
                                        <Link
                                            v-if="can('vendors.update')"
                                            :href="route('admin.vendors.edit', vendor.id)"
                                            class="btn-ghost px-2 py-1"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            v-if="can('vendors.delete')"
                                            type="button"
                                            class="btn-ghost px-2 py-1 text-danger"
                                            @click="destroyVendor(vendor)"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!vendors.data.length">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada data
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    <Pagination :links="vendors.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
