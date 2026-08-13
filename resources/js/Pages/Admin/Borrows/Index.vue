<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    borrows: Object,
    filters: Object,
    statuses: Array,
    statusLabels: Object,
    borrowers: Array,
    clients: Array,
});

const { can } = useCan();
const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const borrower_user_id = ref(props.filters.borrower_user_id || '');
const client_id = ref(props.filters.client_id || '');
const overdue = ref(!!props.filters.overdue);

watch([search, status, borrower_user_id, client_id, overdue], () => {
    router.get(
        route('admin.borrows.index'),
        {
            search: search.value || undefined,
            status: status.value || undefined,
            borrower_user_id: borrower_user_id.value || undefined,
            client_id: client_id.value || undefined,
            overdue: overdue.value || undefined,
        },
        { preserveState: true, replace: true },
    );
});
</script>

<template>
    <Head title="Peminjaman" />

    <AuthenticatedLayout>
        <template #header-title>Peminjaman Barang</template>
        <template #header-subtitle>
            Peminjam (Karyawan) ≠ Digunakan di Client
        </template>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
                <div class="grid flex-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    <input
                        v-model="search"
                        type="search"
                        placeholder="Cari nomor BRW..."
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
                    <select
                        v-model="borrower_user_id"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">Semua peminjam</option>
                        <option v-for="user in borrowers" :key="user.id" :value="user.id">
                            {{ user.name }}
                        </option>
                    </select>
                    <select
                        v-model="client_id"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">Semua client</option>
                        <option v-for="client in clients" :key="client.id" :value="client.id">
                            {{ client.code }} — {{ client.name }}
                        </option>
                    </select>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input v-model="overdue" type="checkbox" class="rounded border-slate-300 text-brand-600" />
                        Hanya overdue
                    </label>
                </div>
                <Link
                    v-if="can('borrows.create')"
                    :href="route('admin.borrows.create')"
                    class="btn-primary"
                >
                    Buat pinjam
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
                                <th class="px-4 py-3">Peminjam</th>
                                <th class="px-4 py-3">Client</th>
                                <th class="px-4 py-3">Jatuh tempo</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="borrow in borrows.data" :key="borrow.id">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ borrow.number }}
                                    <span
                                        v-if="borrow.is_overdue"
                                        class="ml-2 rounded bg-rose-100 px-1.5 py-0.5 text-xs font-semibold text-rose-700"
                                    >
                                        Overdue
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ borrow.borrower }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ borrow.client?.code }} — {{ borrow.client?.name }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ borrow.due_date }}</td>
                                <td class="px-4 py-3">
                                    {{ statusLabels[borrow.status] || borrow.status }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="route('admin.borrows.show', borrow.id)"
                                        class="btn-ghost px-2 py-1"
                                    >
                                        Detail
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!borrows.data.length">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada peminjaman
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    <Pagination :links="borrows.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
