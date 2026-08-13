<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { useConfirm } from '@/composables/useConfirm';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    location: Object,
    racks: Array,
});

const { can } = useCan();
const { confirm } = useConfirm();

async function destroyRack(rack) {
    if (rack.is_general) {
        return;
    }

    const ok = await confirm({
        title: 'Hapus rak',
        message: `Hapus rak ${rack.code} (${rack.label})?`,
        confirmLabel: 'Hapus',
        variant: 'danger',
    });

    if (ok) {
        router.delete(route('admin.racks.destroy', rack.id));
    }
}
</script>

<template>
    <Head :title="`Lokasi ${location.code}`" />

    <AuthenticatedLayout>
        <template #header-title>{{ location.name }}</template>
        <template #header-subtitle>
            {{ location.code }} · {{ location.type }}
        </template>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <Link :href="route('admin.locations.index')" class="btn-ghost px-2 py-1">
                    ← Kembali ke daftar lokasi
                </Link>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-if="can('locations.update')"
                        :href="route('admin.locations.edit', location.id)"
                        class="btn-secondary"
                    >
                        Edit Lokasi
                    </Link>
                    <Link
                        v-if="can('racks.create')"
                        :href="route('admin.racks.create', location.id)"
                        class="btn-primary"
                    >
                        Tambah Rak
                    </Link>
                </div>
            </div>

            <div class="surface-card space-y-2 p-5 text-sm">
                <p class="text-slate-600">
                    <span class="font-medium text-slate-800">Status:</span>
                    {{ location.is_active ? 'Aktif' : 'Nonaktif' }}
                </p>
                <p v-if="location.address" class="text-slate-600">
                    <span class="font-medium text-slate-800">Alamat:</span>
                    {{ location.address }}
                </p>
                <p class="text-slate-500">
                    Setiap lokasi wajib punya rak GENERAL (default). Label rak dapat diubah.
                </p>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-slate-800">Daftar Rak</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Kode</th>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Label</th>
                                <th class="px-4 py-3">Default</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="rack in racks" :key="rack.id">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ rack.code }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ rack.name }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ rack.label }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        v-if="rack.is_default"
                                        class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700"
                                    >
                                        GENERAL
                                    </span>
                                    <span v-else class="text-slate-400">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="
                                            rack.is_active
                                                ? 'bg-emerald-50 text-emerald-700'
                                                : 'bg-slate-100 text-slate-500'
                                        "
                                    >
                                        {{ rack.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            v-if="can('racks.update')"
                                            :href="route('admin.racks.edit', rack.id)"
                                            class="btn-ghost px-2 py-1"
                                        >
                                            Edit
                                        </Link>
                                        <button
                                            v-if="can('racks.delete') && !rack.is_general"
                                            type="button"
                                            class="btn-ghost px-2 py-1 text-danger"
                                            @click="destroyRack(rack)"
                                        >
                                            Hapus
                                        </button>
                                        <span
                                            v-else-if="rack.is_general"
                                            class="px-2 py-1 text-xs text-slate-400"
                                        >
                                            Tidak dapat dihapus
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!racks.length">
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada rak
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
