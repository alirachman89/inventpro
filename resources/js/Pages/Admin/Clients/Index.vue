<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { useConfirm } from '@/composables/useConfirm';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    clients: Object,
    filters: Object,
    types: Array,
    typeLabels: Object,
});

const { can } = useCan();
const { confirm } = useConfirm();
const search = ref(props.filters.search || '');
const type = ref(props.filters.type || '');
const status = ref(props.filters.status || '');

watch([search, type, status], () => {
    router.get(
        route('admin.clients.index'),
        {
            search: search.value || undefined,
            type: type.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true },
    );
});

async function destroyClient(client) {
    const ok = await confirm({
        title: 'Hapus client',
        message: `Hapus client ${client.code} — ${client.name}?`,
        confirmLabel: 'Hapus',
        variant: 'danger',
    });

    if (ok) {
        router.delete(route('admin.clients.destroy', client.id));
    }
}
</script>

<template>
    <Head title="Client" />

    <AuthenticatedLayout>
        <template #header-title>Client</template>
        <template #header-subtitle>
            Master konteks pemakaian (bukan peminjam / bukan vendor)
        </template>

        <div class="space-y-4">
            <div
                class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
            >
                <strong class="text-slate-800">Client ≠ Peminjam ≠ Vendor.</strong>
                Client = proyek/site tempat barang digunakan (contoh: Scaffold dipinjam Karyawan A
                untuk Client A).
            </div>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                <div class="grid flex-1 gap-3 sm:grid-cols-3">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari kode / nama..."
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
                    v-if="can('clients.create')"
                    :href="route('admin.clients.create')"
                    class="btn-primary shrink-0"
                >
                    Tambah Client
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
                            <tr v-for="client in clients.data" :key="client.id">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ client.code }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ client.name }}
                                    <div class="text-xs text-slate-400">
                                        {{ client.contact_person || '—' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ typeLabels[client.type] || client.type }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="
                                            client.is_active
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-slate-100 text-slate-500'
                                        "
                                    >
                                        {{ client.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            :href="route('admin.clients.show', client.id)"
                                            class="btn-ghost px-2 py-1"
                                        >
                                            Detail
                                        </Link>
                                        <Link
                                            v-if="can('clients.update')"
                                            :href="route('admin.clients.edit', client.id)"
                                            class="btn-ghost px-2 py-1"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            v-if="can('clients.delete')"
                                            type="button"
                                            class="btn-ghost px-2 py-1 text-danger"
                                            @click="destroyClient(client)"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!clients.data.length">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada data
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    <Pagination :links="clients.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
