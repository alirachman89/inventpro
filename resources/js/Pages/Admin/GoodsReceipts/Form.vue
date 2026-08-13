<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

const props = defineProps({
    order: Object,
    locations: Array,
});

const form = useForm({
    location_id: props.locations[0]?.id || '',
    received_date: new Date().toISOString().slice(0, 10),
    notes: '',
    lines: props.order.lines.map((line) => ({
        purchase_order_line_id: line.id,
        rack_id: '',
        qty_received: line.qty_outstanding,
        notes: '',
        _meta: line,
    })),
});

const racks = computed(() => {
    const location = props.locations.find((item) => item.id === form.location_id);
    return location?.racks || [];
});

watch(
    () => form.location_id,
    () => {
        const general = racks.value.find((item) => item.is_default);
        form.lines.forEach((line) => {
            line.rack_id = general?.id || '';
        });
    },
    { immediate: true },
);

function submit() {
    form.transform((data) => ({
        location_id: data.location_id,
        received_date: data.received_date,
        notes: data.notes,
        lines: data.lines.map(({ purchase_order_line_id, rack_id, qty_received, notes }) => ({
            purchase_order_line_id,
            rack_id: rack_id || null,
            qty_received,
            notes,
        })),
    })).post(route('admin.goods-receipts.store', props.order.id));
}
</script>

<template>
    <Head :title="`GR ${order.number}`" />

    <AuthenticatedLayout>
        <template #header-title>Goods Receipt</template>
        <template #header-subtitle>
            {{ order.number }} · {{ order.vendor?.name }}
        </template>

        <form class="mx-auto max-w-5xl space-y-6" @submit.prevent="submit">
            <div class="surface-card space-y-5 p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Lokasi tujuan" />
                        <select
                            v-model="form.location_id"
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            required
                        >
                            <option
                                v-for="location in locations"
                                :key="location.id"
                                :value="location.id"
                            >
                                {{ location.code }} — {{ location.name }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.location_id" />
                    </div>
                    <div>
                        <InputLabel value="Tanggal terima" />
                        <TextInput
                            v-model="form.received_date"
                            type="date"
                            class="mt-1 block w-full"
                            required
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
                <p class="text-xs text-slate-500">
                    Jika rak dikosongkan, sistem memakai rak GENERAL lokasi terkait. Item asset
                    serialized akan membuat unit asset otomatis.
                </p>
            </div>

            <div class="surface-card space-y-4 p-6">
                <h2 class="text-sm font-semibold text-slate-800">Qty diterima</h2>
                <div
                    v-for="(line, index) in form.lines"
                    :key="line.purchase_order_line_id"
                    class="grid gap-3 rounded-xl border border-slate-200 p-4 sm:grid-cols-12"
                >
                    <div class="sm:col-span-4">
                        <p class="font-medium text-slate-900">
                            {{ line._meta.item?.name }}
                        </p>
                        <p class="text-xs text-slate-400">
                            {{ line._meta.item?.sku }} · sisa
                            {{ line._meta.qty_outstanding }}
                        </p>
                    </div>
                    <div class="sm:col-span-3">
                        <InputLabel value="Rak" />
                        <select
                            v-model="line.rack_id"
                            class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option value="">GENERAL (default)</option>
                            <option v-for="rack in racks" :key="rack.id" :value="rack.id">
                                {{ rack.code }} — {{ rack.label }}
                            </option>
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <InputLabel value="Qty diterima" />
                        <TextInput
                            v-model="line.qty_received"
                            type="number"
                            min="0"
                            step="0.001"
                            class="mt-1 block w-full"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors[`lines.${index}.qty_received`]"
                        />
                    </div>
                </div>
                <InputError :message="form.errors.lines" />
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link :href="route('admin.purchase-orders.show', order.id)">
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">Simpan Penerimaan</PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
