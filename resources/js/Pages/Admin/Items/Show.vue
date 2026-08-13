<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    item: Object,
    stocks: Array,
    assetUnits: Array,
    ledgers: Array,
    locations: Array,
    filters: Object,
    statusLabels: Object,
});

const { can } = useCan();
const locationId = ref(props.filters.location_id || '');
const rack = ref(props.filters.rack || '');

const adjustForm = useForm({
    location_id: '',
    rack_id: '',
    qty: 1,
    condition: 'good',
    notes: '',
});

const racksForAdjust = computed(() => {
    const location = props.locations.find((item) => item.id === adjustForm.location_id);
    return location?.racks || [];
});

watch(
    () => adjustForm.location_id,
    () => {
        const general = racksForAdjust.value.find((item) => item.is_default);
        adjustForm.rack_id = general?.id || racksForAdjust.value[0]?.id || '';
    },
);

watch([locationId, rack], () => {
    router.get(
        route('admin.items.show', props.item.id),
        {
            location_id: locationId.value || undefined,
            rack: rack.value || undefined,
        },
        { preserveState: true, replace: true },
    );
});

function submitAdjust() {
    adjustForm.post(route('admin.items.adjust-stock', props.item.id), {
        preserveScroll: true,
        onSuccess: () => {
            adjustForm.reset('qty', 'notes');
            adjustForm.condition = 'good';
        },
    });
}
</script>

<template>
    <Head :title="item.name" />

    <AuthenticatedLayout>
        <template #header-title>{{ item.name }}</template>
        <template #header-subtitle>
            {{ item.sku }} · {{ item.item_type }} · {{ item.uom }}
        </template>

        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <Link :href="route('admin.items.index')" class="btn-ghost px-2 py-1">
                    ← Kembali ke daftar barang
                </Link>
                <div class="flex flex-wrap gap-2">
                    <Link
                        v-if="can('items.update')"
                        :href="route('admin.items.edit', item.id)"
                        class="btn-secondary"
                    >
                        Edit Barang
                    </Link>
                    <Link
                        v-if="item.item_type === 'asset' && can('asset_units.create')"
                        :href="route('admin.asset-units.create', item.id)"
                        class="btn-primary"
                    >
                        Tambah Unit Asset
                    </Link>
                </div>
            </div>

            <div class="surface-card grid gap-3 p-5 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Kategori</p>
                    <p class="font-medium text-slate-800">{{ item.category || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Available</p>
                    <p
                        class="font-medium"
                        :class="item.is_low_stock ? 'text-amber-700' : 'text-slate-800'"
                    >
                        {{ item.qty_available }}
                        <span
                            v-if="item.is_low_stock"
                            class="ml-1 text-xs font-normal"
                        >(stok rendah)</span>
                    </p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Min stock</p>
                    <p class="font-medium text-slate-800">{{ item.min_stock }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">Status master</p>
                    <p class="font-medium text-slate-800">
                        {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                    </p>
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <div
                    class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <h2 class="text-sm font-semibold text-slate-800">
                        Posisi stok (Lokasi → Rak → Qty)
                    </h2>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <select
                            v-model="locationId"
                            class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
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
                            placeholder="Cari rak code/label..."
                            class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        />
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Lokasi</th>
                                <th class="px-4 py-3">Rak</th>
                                <th class="px-4 py-3">Label</th>
                                <th class="px-4 py-3">Kondisi</th>
                                <th class="px-4 py-3 text-right">Available</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="stock in stocks" :key="stock.id">
                                <td class="px-4 py-3 text-slate-800">
                                    {{ stock.location?.name }}
                                    <div class="text-xs text-slate-400">
                                        {{ stock.location?.code }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ stock.rack?.code }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ stock.rack?.label }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ stock.condition }}</td>
                                <td class="px-4 py-3 text-right font-medium text-slate-900">
                                    {{ stock.qty_available }}
                                </td>
                            </tr>
                            <tr v-if="!stocks.length">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada posisi stok
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div
                v-if="can('item_stocks.adjust') && item.item_type === 'consumable'"
                class="surface-card space-y-4 p-5"
            >
                <h2 class="text-sm font-semibold text-slate-800">Penyesuaian stok</h2>
                <p class="text-xs text-slate-500">
                    Jika rak tidak dipilih, sistem memakai rak GENERAL lokasi terkait.
                </p>
                <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submitAdjust">
                    <div>
                        <InputLabel value="Lokasi" />
                        <select
                            v-model="adjustForm.location_id"
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            required
                        >
                            <option value="" disabled>Pilih lokasi</option>
                            <option
                                v-for="location in locations"
                                :key="location.id"
                                :value="location.id"
                            >
                                {{ location.code }} — {{ location.name }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="adjustForm.errors.location_id" />
                    </div>
                    <div>
                        <InputLabel value="Rak" />
                        <select
                            v-model="adjustForm.rack_id"
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option value="">GENERAL (default)</option>
                            <option
                                v-for="rackOption in racksForAdjust"
                                :key="rackOption.id"
                                :value="rackOption.id"
                            >
                                {{ rackOption.code }} — {{ rackOption.label }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="adjustForm.errors.rack_id" />
                    </div>
                    <div>
                        <InputLabel value="Qty (+ masuk / − keluar)" />
                        <TextInput
                            v-model="adjustForm.qty"
                            type="number"
                            step="0.001"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="adjustForm.errors.qty" />
                    </div>
                    <div>
                        <InputLabel value="Kondisi" />
                        <select
                            v-model="adjustForm.condition"
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option value="good">good</option>
                            <option value="damaged">damaged</option>
                            <option value="quarantine">quarantine</option>
                            <option value="expired">expired</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Catatan" />
                        <TextInput v-model="adjustForm.notes" class="mt-1 block w-full" />
                    </div>
                    <div class="sm:col-span-2">
                        <PrimaryButton :disabled="adjustForm.processing">
                            Simpan Penyesuaian
                        </PrimaryButton>
                    </div>
                </form>
            </div>

            <div v-if="item.item_type === 'asset'" class="surface-card overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-slate-800">Unit Asset</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Tag</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Lokasi / Rak</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="asset in assetUnits" :key="asset.id">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ asset.asset_tag }}
                                    <div class="text-xs text-slate-400">
                                        {{ asset.serial_number || '—' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ statusLabels[asset.status] || asset.status }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ asset.location?.name || '—' }}
                                    <span v-if="asset.rack">
                                        / {{ asset.rack.code }} ({{ asset.rack.label }})
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="route('admin.asset-units.show', asset.id)"
                                        class="btn-ghost px-2 py-1"
                                    >
                                        Detail
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!assetUnits.length">
                                <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada unit asset
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-slate-800">
                        Riwayat mutasi (20 terbaru)
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">Tipe</th>
                                <th class="px-4 py-3">Lokasi / Rak</th>
                                <th class="px-4 py-3 text-right">Delta</th>
                                <th class="px-4 py-3 text-right">After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="ledger in ledgers" :key="ledger.id">
                                <td class="px-4 py-3 text-slate-600">{{ ledger.created_at }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ ledger.movement_type }}</td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ ledger.location }} /
                                    {{ ledger.rack?.code }} ({{ ledger.rack?.label }})
                                </td>
                                <td class="px-4 py-3 text-right font-medium">
                                    {{ ledger.qty_delta > 0 ? '+' : '' }}{{ ledger.qty_delta }}
                                </td>
                                <td class="px-4 py-3 text-right">{{ ledger.qty_after }}</td>
                            </tr>
                            <tr v-if="!ledgers.length">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                    Belum ada mutasi
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
