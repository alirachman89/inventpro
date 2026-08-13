<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { useConfirm } from '@/composables/useConfirm';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    users: Object,
    filters: Object,
});

const { can } = useCan();
const { confirm } = useConfirm();
const search = ref(props.filters.search || '');

watch(search, (value) => {
    router.get(
        route('admin.users.index'),
        { search: value || undefined },
        { preserveState: true, replace: true },
    );
});

async function destroyUser(user) {
    const ok = await confirm({
        title: 'Hapus user',
        message: `Hapus user ${user.name}? Tindakan ini tidak dapat dibatalkan.`,
        confirmLabel: 'Hapus',
        variant: 'danger',
    });

    if (ok) {
        router.delete(route('admin.users.destroy', user.id));
    }
}
</script>

<template>
    <Head title="Pengguna" />

    <AuthenticatedLayout>
        <template #header-title>Pengguna</template>
        <template #header-subtitle>Kelola akun dan role pengguna</template>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Cari nama atau email..."
                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 sm:max-w-xs"
                />
                <Link
                    v-if="can('users.create')"
                    :href="route('admin.users.create')"
                    class="btn-primary"
                >
                    Tambah User
                </Link>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Role</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="user in users.data" :key="user.id">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ user.name }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ user.email }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="role in user.roles"
                                            :key="role"
                                            class="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700"
                                        >
                                            {{ role }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            v-if="can('users.update')"
                                            :href="route('admin.users.edit', user.id)"
                                            class="btn-ghost px-2 py-1"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            v-if="can('users.delete') && !user.is_superadmin"
                                            type="button"
                                            class="btn-ghost px-2 py-1 text-danger"
                                            @click="destroyUser(user)"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!users.data.length">
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada data
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    <Pagination :links="users.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
