<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    asset: Object,
    histories: Array,
    manualStatuses: Array,
    statusLabels: Object,
});

const { can } = useCan();

const form = useForm({
    status: props.asset.status,
    notes: '',
});

function submit() {
    form.post(route('admin.asset-units.change-status', props.asset.id), {
        preserveScroll: true,
        onSuccess: () => form.reset('notes'),
    });
}
</script>

<template>
    <Head :title="asset.asset_tag" />

    <AuthenticatedLayout>
        <template #header-title>{{ asset.asset_tag }}</template>
        <template #header-subtitle>
            {{ asset.item?.sku }} — {{ asset.item?.name }}
        </template>

        <div class="space-y-4">
            <Link
                :href="route('admin.items.show', asset.item.id)"
                class="btn-ghost inline-flex px-2 py-1"
            >
                ← Kembali ke barang
            </Link>

            <div class="surface-card grid gap-3 p-5 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Status</p>
                    <p class="font-medium text-slate-800">
                        {{ statusLabels[asset.status] || asset.status }}
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Kondisi</p>
                    <p class="font-medium text-slate-800">{{ asset.condition }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Serial</p>
                    <p class="font-medium text-slate-800">{{ asset.serial_number || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Lokasi</p>
                    <p class="font-medium text-slate-800">
                        {{ asset.location?.name || '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Rak</p>
                    <p class="font-medium text-slate-800">
                        <span v-if="asset.rack">
                            {{ asset.rack.code }} ({{ asset.rack.label }})
                        </span>
                        <span v-else>—</span>
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Pemegang (karyawan)
                    </p>
                    <p class="font-medium text-slate-800">{{ asset.holder || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Dipakai di Client
                    </p>
                    <p class="font-medium text-slate-800">
                        <span v-if="asset.client">
                            {{ asset.client.code }} — {{ asset.client.name }}
                        </span>
                        <span v-else>—</span>
                    </p>
                </div>
            </div>

            <div
                v-if="can('asset_units.set_status')"
                class="surface-card space-y-4 p-5"
            >
                <h2 class="text-sm font-semibold text-slate-800">Ubah status</h2>
                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                    <div>
                        <InputLabel value="Status baru" />
                        <select
                            v-model="form.status"
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option
                                v-for="status in manualStatuses"
                                :key="status"
                                :value="status"
                            >
                                {{ statusLabels[status] || status }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.status" />
                    </div>
                    <div>
                        <InputLabel value="Catatan" />
                        <TextInput v-model="form.notes" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.notes" />
                    </div>
                    <div>
                        <PrimaryButton :disabled="form.processing">Simpan Status</PrimaryButton>
                    </div>
                </form>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-slate-800">Riwayat status</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">Dari</th>
                                <th class="px-4 py-3">Ke</th>
                                <th class="px-4 py-3">Oleh</th>
                                <th class="px-4 py-3">Catatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="history in histories" :key="history.id">
                                <td class="px-4 py-3 text-slate-600">{{ history.created_at }}</td>
                                <td class="px-4 py-3">
                                    {{
                                        history.from_status
                                            ? statusLabels[history.from_status] ||
                                              history.from_status
                                            : '—'
                                    }}
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-800">
                                    {{ statusLabels[history.to_status] || history.to_status }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ history.changed_by || '—' }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ history.notes || '—' }}
                                </td>
                            </tr>
                            <tr v-if="!histories.length">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada riwayat
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
