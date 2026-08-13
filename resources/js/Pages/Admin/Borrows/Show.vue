<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useCan } from '@/composables/useCan';
import { useConfirm } from '@/composables/useConfirm';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    borrow: Object,
    statusLabels: Object,
});

const { can } = useCan();
const { confirm } = useConfirm();

const returnForm = useForm({
    returns: props.borrow.lines
        .filter((line) => line.outstanding > 0)
        .map((line) => ({
            id: line.id,
            qty_return: line.outstanding,
            condition_on_return: 'good',
            include: true,
            asset_tag: line.asset_unit?.asset_tag,
            item_sku: line.item?.sku,
            outstanding: line.outstanding,
        })),
});

const submitBorrow = async () => {
    const ok = await confirm({
        title: 'Ajukan peminjaman?',
        message: `Ajukan ${props.borrow.number} ke approval?`,
        confirmLabel: 'Ajukan',
        variant: 'warning',
    });
    if (ok) router.post(route('admin.borrows.submit', props.borrow.id));
};

const checkoutBorrow = async () => {
    const ok = await confirm({
        title: 'Checkout peminjaman?',
        message:
            'Asset akan berstatus borrowed. Holder = peminjam karyawan, Client = client terpilih.',
        confirmLabel: 'Checkout',
        variant: 'warning',
    });
    if (ok) router.post(route('admin.borrows.checkout', props.borrow.id));
};

const returnBorrow = async () => {
    const selected = returnForm.returns.filter((row) => row.include);
    if (!selected.length) return;

    const ok = await confirm({
        title: 'Return barang?',
        message: 'Holder & client pada asset akan dikosongkan; status kembali available.',
        confirmLabel: 'Return',
        variant: 'warning',
    });
    if (!ok) return;

    returnForm
        .transform((data) => ({
            returns: data.returns
                .filter((row) => row.include)
                .map((row) => ({
                    id: row.id,
                    qty_return: row.qty_return,
                    condition_on_return: row.condition_on_return,
                })),
        }))
        .post(route('admin.borrows.return', props.borrow.id));
};

const destroyBorrow = async () => {
    const ok = await confirm({
        title: 'Hapus peminjaman?',
        message: `Hapus ${props.borrow.number}?`,
        confirmLabel: 'Hapus',
    });
    if (ok) router.delete(route('admin.borrows.destroy', props.borrow.id));
};
</script>

<template>
    <Head :title="borrow.number" />

    <AuthenticatedLayout>
        <template #header-title>{{ borrow.number }}</template>
        <template #header-subtitle>
            {{ statusLabels[borrow.status] || borrow.status }}
            <span
                v-if="borrow.is_overdue"
                class="ml-2 rounded bg-rose-100 px-1.5 py-0.5 text-xs font-semibold text-rose-700"
            >
                Overdue
            </span>
        </template>

        <div class="space-y-4">
            <div class="flex flex-wrap gap-2">
                <Link :href="route('admin.borrows.index')" class="btn-secondary">
                    ← Daftar pinjam
                </Link>
                <Link
                    v-if="borrow.can_edit && can('borrows.update')"
                    :href="route('admin.borrows.edit', borrow.id)"
                    class="btn-secondary"
                >
                    Edit
                </Link>
                <button
                    v-if="borrow.can_submit && can('borrows.submit')"
                    type="button"
                    class="btn-primary"
                    @click="submitBorrow"
                >
                    Ajukan approval
                </button>
                <button
                    v-if="borrow.can_checkout && can('borrows.checkout')"
                    type="button"
                    class="btn-primary"
                    @click="checkoutBorrow"
                >
                    Checkout
                </button>
                <button
                    v-if="borrow.can_edit && can('borrows.delete')"
                    type="button"
                    class="btn-ghost text-rose-600"
                    @click="destroyBorrow"
                >
                    Hapus
                </button>
            </div>

            <div class="surface-card grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Peminjam (Karyawan)
                    </p>
                    <p class="mt-1 text-sm font-medium text-slate-900">
                        {{ borrow.borrower?.name }}
                    </p>
                    <p class="text-xs text-slate-500">{{ borrow.borrower?.email }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Digunakan di Client
                    </p>
                    <p class="mt-1 text-sm font-medium text-slate-900">
                        {{ borrow.client?.code }} — {{ borrow.client?.name }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Periode
                    </p>
                    <p class="mt-1 text-sm text-slate-900">
                        {{ borrow.borrow_date }} → {{ borrow.due_date }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Tujuan
                    </p>
                    <p class="mt-1 text-sm text-slate-900">{{ borrow.purpose || '—' }}</p>
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">Barang</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead
                            class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            <tr>
                                <th class="px-4 py-3">Item / Unit</th>
                                <th class="px-4 py-3">Checkout / Return</th>
                                <th class="px-4 py-3">Holder & Client (asset)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="line in borrow.lines" :key="line.id">
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">
                                        {{ line.item?.sku }} — {{ line.item?.name }}
                                    </div>
                                    <div v-if="line.asset_unit" class="text-xs text-slate-500">
                                        {{ line.asset_unit.asset_tag }}
                                        <span v-if="line.asset_unit.serial_number">
                                            / {{ line.asset_unit.serial_number }}
                                        </span>
                                        · status {{ line.asset_unit.status }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    Out {{ line.qty_checked_out }} / Ret
                                    {{ line.qty_returned }}
                                    <span v-if="line.outstanding > 0" class="text-amber-700">
                                        (sisa {{ line.outstanding }})
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    <template v-if="line.asset_unit">
                                        Holder: {{ line.asset_unit.holder || '—' }}
                                        <br />
                                        Client:
                                        <template v-if="line.asset_unit.client">
                                            {{ line.asset_unit.client.code }} —
                                            {{ line.asset_unit.client.name }}
                                        </template>
                                        <template v-else>—</template>
                                    </template>
                                    <template v-else>—</template>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div
                v-if="borrow.can_return && can('borrows.return') && returnForm.returns.length"
                class="surface-card space-y-4 p-5"
            >
                <h3 class="text-sm font-semibold text-slate-900">Return barang</h3>
                <div
                    v-for="(row, index) in returnForm.returns"
                    :key="row.id"
                    class="grid gap-3 rounded-xl border border-slate-200 p-3 md:grid-cols-[auto_1fr_1fr]"
                >
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="row.include"
                            type="checkbox"
                            class="rounded border-slate-300 text-brand-600"
                        />
                        {{ row.asset_tag || row.item_sku }}
                    </label>
                    <div>
                        <label class="mb-1 block text-xs text-slate-500">Qty return</label>
                        <input
                            v-model="row.qty_return"
                            type="number"
                            min="0.0001"
                            step="any"
                            :max="row.outstanding"
                            class="w-full rounded-xl border-slate-300 text-sm"
                            :disabled="!row.include"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs text-slate-500">Kondisi kembali</label>
                        <select
                            v-model="row.condition_on_return"
                            class="w-full rounded-xl border-slate-300 text-sm"
                            :disabled="!row.include"
                        >
                            <option value="good">Baik</option>
                            <option value="damaged">Rusak</option>
                            <option value="quarantine">Karantina</option>
                        </select>
                    </div>
                </div>
                <button
                    type="button"
                    class="btn-primary"
                    :disabled="returnForm.processing"
                    @click="returnBorrow"
                >
                    Simpan return
                </button>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
