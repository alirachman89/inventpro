<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    defaultType: String,
    types: Array,
    typeLabels: Object,
    reasonsByType: Object,
    reasonLabels: Object,
    items: Array,
    locations: Array,
    clients: Array,
});

const emptyLine = () => ({
    item_id: '',
    qty: 1,
    condition: 'good',
    from_location_id: '',
    from_rack_id: '',
    to_location_id: '',
    to_rack_id: '',
    notes: '',
});

const form = useForm({
    type: props.defaultType || 'in',
    reason: props.reasonsByType[props.defaultType || 'in']?.[0] || 'other',
    movement_date: new Date().toISOString().slice(0, 10),
    client_id: '',
    notes: '',
    lines: [emptyLine()],
});

const reasonOptions = computed(() => props.reasonsByType[form.type] || []);

watch(
    () => form.type,
    (type) => {
        const reasons = props.reasonsByType[type] || [];
        if (!reasons.includes(form.reason)) {
            form.reason = reasons[0] || 'other';
        }
        if (type !== 'out') {
            form.client_id = '';
        }
    },
);

const racksFor = (locationId) => {
    const location = props.locations.find((item) => item.id === locationId);
    return location?.racks || [];
};

const syncDefaultRack = (line, side) => {
    const locationKey = side === 'from' ? 'from_location_id' : 'to_location_id';
    const rackKey = side === 'from' ? 'from_rack_id' : 'to_rack_id';
    const racks = racksFor(line[locationKey]);
    const current = racks.find((rack) => rack.id === line[rackKey]);
    if (!current) {
        line[rackKey] = racks.find((rack) => rack.is_default)?.id || racks[0]?.id || '';
    }
};

const addLine = () => form.lines.push(emptyLine());
const removeLine = (index) => {
    if (form.lines.length > 1) {
        form.lines.splice(index, 1);
    }
};

const submit = () => {
    form.post(route('admin.stock-movements.store'));
};
</script>

<template>
    <Head :title="`Mutasi — ${typeLabels[form.type] || form.type}`" />

    <AuthenticatedLayout>
        <template #header-title>Buat Mutasi Stok</template>
        <template #header-subtitle>
            {{ typeLabels[form.type] || form.type }} — setiap baris wajib lokasi + rak
        </template>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="surface-card space-y-4 p-5">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Tipe</label>
                        <select
                            v-model="form.type"
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option v-for="item in types" :key="item" :value="item">
                                {{ typeLabels[item] || item }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Alasan</label>
                        <select
                            v-model="form.reason"
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option v-for="item in reasonOptions" :key="item" :value="item">
                                {{ reasonLabels[item] || item }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal</label>
                        <input
                            v-model="form.movement_date"
                            type="date"
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        />
                    </div>
                    <div v-if="form.type === 'out'">
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Client
                            <span v-if="form.reason === 'issue_to_client'" class="text-rose-600">*</span>
                        </label>
                        <select
                            v-model="form.client_id"
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option value="">— Opsional —</option>
                            <option v-for="client in clients" :key="client.id" :value="client.id">
                                {{ client.code }} — {{ client.name }}
                            </option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Catatan</label>
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Baris mutasi</h3>
                    <button type="button" class="btn-secondary" @click="addLine">+ Baris</button>
                </div>
                <div class="space-y-4 p-4">
                    <div
                        v-for="(line, index) in form.lines"
                        :key="index"
                        class="rounded-xl border border-slate-200 p-4"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Baris {{ index + 1 }}
                            </span>
                            <button
                                v-if="form.lines.length > 1"
                                type="button"
                                class="text-xs text-rose-600 hover:underline"
                                @click="removeLine(index)"
                            >
                                Hapus
                            </button>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                            <div class="xl:col-span-2">
                                <label class="mb-1 block text-xs font-medium text-slate-600">Item</label>
                                <select
                                    v-model="line.item_id"
                                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                >
                                    <option value="">Pilih item</option>
                                    <option v-for="item in items" :key="item.id" :value="item.id">
                                        {{ item.sku }} — {{ item.name }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Qty</label>
                                <input
                                    v-model="line.qty"
                                    type="number"
                                    min="0.0001"
                                    step="any"
                                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-slate-600">Kondisi</label>
                                <select
                                    v-model="line.condition"
                                    class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                >
                                    <option value="good">Baik</option>
                                    <option value="damaged">Rusak</option>
                                    <option value="quarantine">Karantina</option>
                                    <option value="expired">Expired</option>
                                </select>
                            </div>

                            <template v-if="form.type === 'out' || form.type === 'transfer'">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">
                                        Dari lokasi
                                    </label>
                                    <select
                                        v-model="line.from_location_id"
                                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                        @change="syncDefaultRack(line, 'from')"
                                    >
                                        <option value="">Pilih lokasi</option>
                                        <option
                                            v-for="location in locations"
                                            :key="location.id"
                                            :value="location.id"
                                        >
                                            {{ location.code }} — {{ location.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">
                                        Dari rak
                                    </label>
                                    <select
                                        v-model="line.from_rack_id"
                                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                    >
                                        <option value="">Pilih rak</option>
                                        <option
                                            v-for="rack in racksFor(line.from_location_id)"
                                            :key="rack.id"
                                            :value="rack.id"
                                        >
                                            {{ rack.code }} — {{ rack.label }}
                                        </option>
                                    </select>
                                </div>
                            </template>

                            <template v-if="form.type === 'in' || form.type === 'transfer'">
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">
                                        Ke lokasi
                                    </label>
                                    <select
                                        v-model="line.to_location_id"
                                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                        @change="syncDefaultRack(line, 'to')"
                                    >
                                        <option value="">Pilih lokasi</option>
                                        <option
                                            v-for="location in locations"
                                            :key="location.id"
                                            :value="location.id"
                                        >
                                            {{ location.code }} — {{ location.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-slate-600">
                                        Ke rak
                                    </label>
                                    <select
                                        v-model="line.to_rack_id"
                                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                    >
                                        <option value="">Pilih rak</option>
                                        <option
                                            v-for="rack in racksFor(line.to_location_id)"
                                            :key="rack.id"
                                            :value="rack.id"
                                        >
                                            {{ rack.code }} — {{ rack.label }}
                                        </option>
                                    </select>
                                </div>
                            </template>
                        </div>
                        <p
                            v-if="form.errors[`lines.${index}.item_id`] || form.errors[`lines.${index}.qty`]"
                            class="mt-2 text-xs text-rose-600"
                        >
                            {{
                                form.errors[`lines.${index}.item_id`] ||
                                form.errors[`lines.${index}.qty`]
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="form.errors.lines || Object.keys(form.errors).length"
                class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
            >
                <p v-if="form.errors.lines">{{ form.errors.lines }}</p>
                <ul v-else class="list-disc space-y-1 pl-4">
                    <li v-for="(message, key) in form.errors" :key="key">{{ message }}</li>
                </ul>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-primary" :disabled="form.processing">
                    Simpan mutasi
                </button>
                <Link :href="route('admin.stock-movements.index')" class="btn-secondary">
                    Batal
                </Link>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
