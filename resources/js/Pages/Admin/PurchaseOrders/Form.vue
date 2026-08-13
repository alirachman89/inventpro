<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    order: Object,
    vendors: Array,
    items: Array,
});

const isEdit = computed(() => !!props.order);

const form = useForm({
    vendor_id: props.order?.vendor_id || '',
    order_date: props.order?.order_date || new Date().toISOString().slice(0, 10),
    expected_date: props.order?.expected_date || '',
    notes: props.order?.notes || '',
    submit_now: false,
    lines: props.order?.lines?.length
        ? props.order.lines.map((line) => ({ ...line }))
        : [{ item_id: '', qty_ordered: 1, unit_price: 0, notes: '' }],
});

const total = computed(() =>
    form.lines.reduce(
        (sum, line) => sum + Number(line.qty_ordered || 0) * Number(line.unit_price || 0),
        0,
    ),
);

function addLine() {
    form.lines.push({ item_id: '', qty_ordered: 1, unit_price: 0, notes: '' });
}

function removeLine(index) {
    if (form.lines.length > 1) {
        form.lines.splice(index, 1);
    }
}

function submit(submitNow = false) {
    form.submit_now = submitNow;
    if (isEdit.value) {
        form.put(route('admin.purchase-orders.update', props.order.id));
    } else {
        form.post(route('admin.purchase-orders.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit PO' : 'Buat PO'" />

    <AuthenticatedLayout>
        <template #header-title>
            {{ isEdit ? `Edit ${order.number}` : 'Buat Purchase Order' }}
        </template>
        <template #header-subtitle>Vendor, item, qty, dan harga</template>

        <form class="mx-auto max-w-5xl space-y-6" @submit.prevent="submit(false)">
            <div class="surface-card space-y-5 p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Vendor" />
                        <select
                            v-model="form.vendor_id"
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            required
                        >
                            <option value="" disabled>Pilih vendor</option>
                            <option
                                v-for="vendor in vendors"
                                :key="vendor.id"
                                :value="vendor.id"
                            >
                                {{ vendor.code }} — {{ vendor.name }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.vendor_id" />
                    </div>
                    <div>
                        <InputLabel value="Tanggal PO" />
                        <TextInput
                            v-model="form.order_date"
                            type="date"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.order_date" />
                    </div>
                    <div>
                        <InputLabel value="Estimasi datang" />
                        <TextInput
                            v-model="form.expected_date"
                            type="date"
                            class="mt-1 block w-full"
                        />
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel value="Catatan" />
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        />
                    </div>
                </div>
            </div>

            <div class="surface-card space-y-4 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-slate-800">Baris item</h2>
                    <button type="button" class="btn-secondary" @click="addLine">
                        Tambah baris
                    </button>
                </div>

                <div class="space-y-4">
                    <div
                        v-for="(line, index) in form.lines"
                        :key="index"
                        class="grid gap-3 rounded-xl border border-slate-200 p-4 sm:grid-cols-12"
                    >
                        <div class="sm:col-span-5">
                            <InputLabel value="Item" />
                            <select
                                v-model="line.item_id"
                                class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                required
                            >
                                <option value="" disabled>Pilih item</option>
                                <option
                                    v-for="item in items"
                                    :key="item.id"
                                    :value="item.id"
                                >
                                    {{ item.sku }} — {{ item.name }}
                                </option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <InputLabel value="Qty" />
                            <TextInput
                                v-model="line.qty_ordered"
                                type="number"
                                min="0.001"
                                step="0.001"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div class="sm:col-span-3">
                            <InputLabel value="Harga satuan" />
                            <TextInput
                                v-model="line.unit_price"
                                type="number"
                                min="0"
                                step="0.01"
                                class="mt-1 block w-full"
                                required
                            />
                        </div>
                        <div class="flex items-end sm:col-span-2">
                            <button
                                type="button"
                                class="btn-ghost px-2 py-2 text-danger"
                                @click="removeLine(index)"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
                <InputError :message="form.errors.lines" />
                <p class="text-right text-sm font-semibold text-slate-800">
                    Total:
                    {{
                        total.toLocaleString('id-ID', {
                            style: 'currency',
                            currency: 'IDR',
                            maximumFractionDigits: 0,
                        })
                    }}
                </p>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link
                    :href="
                        isEdit
                            ? route('admin.purchase-orders.show', order.id)
                            : route('admin.purchase-orders.index')
                    "
                >
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
                <PrimaryButton
                    type="button"
                    :disabled="form.processing"
                    @click="submit(false)"
                >
                    Simpan Draft
                </PrimaryButton>
                <PrimaryButton
                    v-if="!isEdit"
                    type="button"
                    :disabled="form.processing"
                    @click="submit(true)"
                >
                    Simpan & Ajukan
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
