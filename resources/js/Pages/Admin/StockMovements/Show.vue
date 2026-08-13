<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    movement: Object,
    typeLabels: Object,
    reasonLabels: Object,
});

const formatQty = (value) =>
    Number(value).toLocaleString('id-ID', { maximumFractionDigits: 4 });
</script>

<template>
    <Head :title="`Mutasi ${movement.number}`" />

    <AuthenticatedLayout>
        <template #header-title>{{ movement.number }}</template>
        <template #header-subtitle>
            {{ typeLabels[movement.type] || movement.type }} ·
            {{ reasonLabels[movement.reason] || movement.reason }}
        </template>

        <div class="space-y-4">
            <div class="flex flex-wrap gap-2">
                <Link :href="route('admin.stock-movements.index')" class="btn-secondary">
                    ← Daftar mutasi
                </Link>
            </div>

            <div class="surface-card grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Tanggal
                    </p>
                    <p class="mt-1 text-sm text-slate-900">{{ movement.movement_date }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Dibuat oleh
                    </p>
                    <p class="mt-1 text-sm text-slate-900">{{ movement.creator || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Client
                    </p>
                    <p class="mt-1 text-sm text-slate-900">
                        <template v-if="movement.client">
                            {{ movement.client.code }} — {{ movement.client.name }}
                        </template>
                        <template v-else>—</template>
                    </p>
                </div>
                <div class="md:col-span-2 xl:col-span-1">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Catatan
                    </p>
                    <p class="mt-1 text-sm text-slate-900">{{ movement.notes || '—' }}</p>
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Baris mutasi</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead
                            class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            <tr>
                                <th class="px-4 py-3">Item</th>
                                <th class="px-4 py-3">Qty</th>
                                <th class="px-4 py-3">Dari</th>
                                <th class="px-4 py-3">Ke</th>
                                <th class="px-4 py-3">Kondisi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="line in movement.lines" :key="line.id">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">
                                        {{ line.item?.sku }}
                                    </div>
                                    <div class="text-xs text-slate-500">{{ line.item?.name }}</div>
                                    <div
                                        v-if="line.asset_tag"
                                        class="text-xs text-slate-400"
                                    >
                                        Asset: {{ line.asset_tag }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ formatQty(line.qty) }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    <template v-if="line.from_location">
                                        {{ line.from_location.code }}
                                        <span v-if="line.from_rack">
                                            / {{ line.from_rack.code }}
                                        </span>
                                    </template>
                                    <template v-else>—</template>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <template v-if="line.to_location">
                                        {{ line.to_location.code }}
                                        <span v-if="line.to_rack">
                                            / {{ line.to_rack.code }}
                                        </span>
                                    </template>
                                    <template v-else>—</template>
                                </td>
                                <td class="px-4 py-3 text-slate-600">{{ line.condition }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
