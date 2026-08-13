<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { useConfirm } from '@/composables/useConfirm';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    roles: Object,
});

const { can } = useCan();
const { confirm } = useConfirm();

async function destroyRole(role) {
    const ok = await confirm({
        title: 'Hapus role',
        message: `Hapus role ${role.name}?`,
        confirmLabel: 'Hapus',
        variant: 'danger',
    });

    if (ok) {
        router.delete(route('admin.roles.destroy', role.id));
    }
}
</script>

<template>
    <Head title="Role & Permission" />

    <AuthenticatedLayout>
        <template #header-title>Role & Permission</template>
        <template #header-subtitle
            >Kelola role dan matrix permission</template
        >

        <div class="space-y-4">
            <div class="flex justify-end">
                <Link
                    v-if="can('roles.create')"
                    :href="route('admin.roles.create')"
                    class="btn-primary"
                >
                    Tambah Role
                </Link>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead
                            class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            <tr>
                                <th class="px-4 py-3">Role</th>
                                <th class="px-4 py-3">Permission</th>
                                <th class="px-4 py-3">User</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="role in roles.data" :key="role.id">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ role.name }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ role.permissions_count }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ role.users_count }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            v-if="can('roles.update')"
                                            :href="
                                                route('admin.roles.edit', role.id)
                                            "
                                            class="btn-ghost px-2 py-1"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            v-if="
                                                can('roles.delete') &&
                                                !role.is_superadmin
                                            "
                                            type="button"
                                            class="btn-ghost px-2 py-1 text-danger"
                                            @click="destroyRole(role)"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    <Pagination :links="roles.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
