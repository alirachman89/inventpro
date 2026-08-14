<script setup>
import BarcodeScanInput from '@/Components/BarcodeScanInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { useConfirm } from '@/composables/useConfirm';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

const props = defineProps({
    opname: Object,
    summary: Object,
    statusLabels: Object,
});

const { can } = useCan();
const { confirm } = useConfirm();
const highlightId = ref(null);

const form = useForm({
    lines: props.opname.lines.map((line) => ({
        id: line.id,
        qty_counted: line.qty_counted ?? '',
        notes: line.notes || '',
    })),
});

const variancePreview = (index) => {
    const counted = form.lines[index]?.qty_counted;
    if (counted === '' || counted === null || counted === undefined) {
        return null;
    }
    return Number(counted) - Number(props.opname.lines[index].qty_system);
};

const formatQty = (value) =>
    Number(value).toLocaleString('id-ID', { maximumFractionDigits: 4 });

const syncFormLines = () => {
    form.lines = props.opname.lines.map((line) => ({
        id: line.id,
        qty_counted: line.qty_counted ?? '',
        notes: line.notes || '',
    }));
};

const codeMatchesItem = (code, item) => {
    if (!item) return false;
    const needle = code.trim().toLowerCase();
    return (
        (item.sku && item.sku.toLowerCase() === needle) ||
        (item.barcode && String(item.barcode).toLowerCase() === needle)
    );
};

const resolveOpnameScan = async (code) => {
    const index = props.opname.lines.findIndex((line) => codeMatchesItem(code, line.item));
    if (index < 0) {
        return null;
    }

    const line = props.opname.lines[index];
    return {
        label: `${line.item.sku} / ${line.rack?.code || '-'}`,
        data: { index, line },
    };
};

const onBarcodeResolved = async ({ index, line }) => {
    const current = form.lines[index].qty_counted;
    const base = current === '' || current === null ? 0 : Number(current);
    form.lines[index].qty_counted = base + 1;
    highlightId.value = line.id;
    await nextTick();
    const nodes = document.querySelectorAll(`[data-opn-line="${line.id}"]`);
    const visible = [...nodes].find((el) => el.offsetParent !== null) || nodes[0];
    visible?.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

const saveCounts = () => {
    form.put(route('admin.stock-opnames.update-counts', props.opname.id), {
        preserveScroll: true,
        onSuccess: () => syncFormLines(),
    });
};

const refreshLines = () => {
    router.post(route('admin.stock-opnames.refresh', props.opname.id), {}, { preserveScroll: true });
};

const submitOpname = async () => {
    const ok = await confirm({
        title: 'Ajukan opname?',
        message:
            'Jika ada selisih, dokumen masuk antrean approval. Tanpa selisih, sesi langsung selesai.',
        confirmLabel: 'Ajukan',
        variant: 'warning',
    });
    if (ok) {
        router.post(route('admin.stock-opnames.submit', props.opname.id));
    }
};

const postOpname = async () => {
    const ok = await confirm({
        title: 'Posting penyesuaian stok?',
        message:
            'Selisih akan ditulis ke ledger (opname_adjustment) dan mengubah qty on-hand.',
        confirmLabel: 'Posting',
        variant: 'warning',
    });
    if (ok) {
        router.post(route('admin.stock-opnames.post', props.opname.id));
    }
};

const deleteOpname = async () => {
    const ok = await confirm({
        title: 'Hapus sesi opname?',
        message: 'Tindakan ini tidak dapat dibatalkan.',
        confirmLabel: 'Hapus',
    });
    if (ok) {
        router.delete(route('admin.stock-opnames.destroy', props.opname.id));
    }
};

const canEdit = computed(() => props.opname.can_edit && can('stock_opnames.update'));
</script>

<template>
    <Head :title="`Opname ${opname.number}`" />

    <AuthenticatedLayout>
        <template #header-title>{{ opname.number }}</template>
        <template #header-subtitle>
            {{ opname.location?.code }} — {{ opname.location?.name }} ·
            {{ statusLabels[opname.status] || opname.status }}
        </template>

        <div class="space-y-4">
            <div class="flex flex-wrap gap-2">
                <Link :href="route('admin.stock-opnames.index')" class="btn-secondary">
                    ← Daftar opname
                </Link>
                <button
                    v-if="canEdit"
                    type="button"
                    class="btn-secondary"
                    @click="refreshLines"
                >
                    Refresh qty sistem
                </button>
                <button
                    v-if="canEdit"
                    type="button"
                    class="btn-primary"
                    :disabled="form.processing"
                    @click="saveCounts"
                >
                    Simpan qty fisik
                </button>
                <button
                    v-if="opname.can_submit && can('stock_opnames.submit')"
                    type="button"
                    class="btn-primary"
                    @click="submitOpname"
                >
                    Ajukan approval
                </button>
                <button
                    v-if="opname.can_post && can('stock_opnames.post')"
                    type="button"
                    class="btn-primary"
                    @click="postOpname"
                >
                    Posting ke stok
                </button>
                <button
                    v-if="opname.can_edit && can('stock_opnames.delete')"
                    type="button"
                    class="btn-ghost text-rose-600"
                    @click="deleteOpname"
                >
                    Hapus
                </button>
            </div>

            <div class="surface-card grid gap-4 p-5 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Tanggal
                    </p>
                    <p class="mt-1 text-sm text-slate-900">{{ opname.opname_date }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">PIC</p>
                    <p class="mt-1 text-sm text-slate-900">{{ opname.pic || '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Ringkasan
                    </p>
                    <p class="mt-1 text-sm text-slate-900">
                        {{ summary.counted }}/{{ summary.lines }} dihitung ·
                        {{ summary.variance }} selisih
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Catatan
                    </p>
                    <p class="mt-1 text-sm text-slate-900">{{ opname.notes || '—' }}</p>
                </div>
            </div>

            <div v-if="canEdit" class="surface-card space-y-2 p-4">
                <label class="block text-xs font-medium text-slate-600">
                    Scan barcode / SKU
                </label>
                <BarcodeScanInput
                    mode="custom"
                    autofocus
                    placeholder="Scan item pada daftar opname…"
                    :resolve-fn="resolveOpnameScan"
                    @resolved="onBarcodeResolved"
                />
                <p class="text-xs text-slate-500">
                    Setiap scan menambah qty fisik (+1) pada baris item yang cocok (rak pertama
                    jika ada beberapa).
                </p>
            </div>

            <!-- Mobile-friendly card list -->
            <div class="space-y-3 md:hidden">
                <div
                    v-for="(line, index) in opname.lines"
                    :key="line.id"
                    :data-opn-line="line.id"
                    class="surface-card space-y-3 p-4"
                    :class="highlightId === line.id ? 'ring-2 ring-emerald-400' : ''"
                >
                    <div>
                        <p class="font-semibold text-slate-900">{{ line.item?.sku }}</p>
                        <p class="text-sm text-slate-600">{{ line.item?.name }}</p>
                        <p class="text-xs text-slate-400">
                            Rak {{ line.rack?.code }} · {{ line.condition }}
                        </p>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm">
                        <div>
                            <p class="text-xs text-slate-500">Sistem</p>
                            <p class="font-medium">{{ formatQty(line.qty_system) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-500">Selisih</p>
                            <p
                                class="font-medium"
                                :class="{
                                    'text-amber-700': variancePreview(index) !== null && variancePreview(index) !== 0,
                                    'text-slate-400': variancePreview(index) === null,
                                }"
                            >
                                {{
                                    variancePreview(index) === null
                                        ? '—'
                                        : formatQty(variancePreview(index))
                                }}
                            </p>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-slate-600">
                            Qty fisik
                        </label>
                        <input
                            v-model="form.lines[index].qty_counted"
                            type="number"
                            min="0"
                            step="any"
                            inputmode="decimal"
                            class="w-full rounded-xl border-slate-300 py-3 text-lg shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            :disabled="!canEdit"
                        />
                    </div>
                </div>
            </div>

            <!-- Desktop table -->
            <div class="surface-card hidden overflow-hidden md:block">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead
                            class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            <tr>
                                <th class="px-4 py-3">Item</th>
                                <th class="px-4 py-3">Rak</th>
                                <th class="px-4 py-3">Sistem</th>
                                <th class="px-4 py-3">Qty fisik</th>
                                <th class="px-4 py-3">Selisih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(line, index) in opname.lines"
                                :key="line.id"
                                :data-opn-line="line.id"
                                :class="highlightId === line.id ? 'bg-emerald-50/60' : ''"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">
                                        {{ line.item?.sku }}
                                    </div>
                                    <div class="text-xs text-slate-500">{{ line.item?.name }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ line.rack?.code }}
                                    <span class="text-xs text-slate-400">
                                        ({{ line.condition }})
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ formatQty(line.qty_system) }}
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        v-model="form.lines[index].qty_counted"
                                        type="number"
                                        min="0"
                                        step="any"
                                        class="w-28 rounded-lg border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                        :disabled="!canEdit"
                                    />
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        :class="{
                                            'font-medium text-amber-700':
                                                variancePreview(index) !== null &&
                                                variancePreview(index) !== 0,
                                            'text-slate-400': variancePreview(index) === null,
                                        }"
                                    >
                                        {{
                                            variancePreview(index) === null
                                                ? '—'
                                                : formatQty(variancePreview(index))
                                        }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!opname.lines.length">
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                    Tidak ada stok consumable di lokasi ini
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <p v-if="canEdit" class="text-xs text-slate-500">
                Tip: simpan qty fisik dulu, lalu ajukan. Jika ada selisih → approval → posting.
                Tanpa selisih, ajukan langsung menyelesaikan sesi.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
