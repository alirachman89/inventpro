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
    item: Object,
    categories: Array,
    units: Array,
    types: Array,
});

const isEdit = computed(() => !!props.item);

const form = useForm({
    sku: props.item?.sku || '',
    barcode: props.item?.barcode || '',
    name: props.item?.name || '',
    item_type: props.item?.item_type || 'consumable',
    is_serialized: props.item?.is_serialized ?? false,
    category_id: props.item?.category_id || '',
    uom_id: props.item?.uom_id || '',
    description: props.item?.description || '',
    min_stock: props.item?.min_stock ?? 0,
    is_active: props.item?.is_active ?? true,
});

watch(
    () => form.item_type,
    (value) => {
        if (value === 'asset') {
            form.is_serialized = true;
        } else {
            form.is_serialized = false;
        }
    },
);

function submit() {
    if (isEdit.value) {
        form.put(route('admin.items.update', props.item.id));
    } else {
        form.post(route('admin.items.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Barang' : 'Tambah Barang'" />

    <AuthenticatedLayout>
        <template #header-title>{{ isEdit ? 'Edit Barang' : 'Tambah Barang' }}</template>
        <template #header-subtitle>Form master barang / item</template>

        <form class="mx-auto max-w-3xl space-y-6" @submit.prevent="submit">
            <div class="surface-card space-y-5 p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="sku" value="SKU" />
                        <TextInput id="sku" v-model="form.sku" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.sku" />
                    </div>
                    <div>
                        <InputLabel for="barcode" value="Barcode (opsional)" />
                        <TextInput id="barcode" v-model="form.barcode" class="mt-1 block w-full" />
                        <InputError class="mt-2" :message="form.errors.barcode" />
                    </div>
                </div>

                <div>
                    <InputLabel for="name" value="Nama" />
                    <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="item_type" value="Tipe" />
                        <select
                            id="item_type"
                            v-model="form.item_type"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            required
                        >
                            <option v-for="type in types" :key="type" :value="type">
                                {{ type }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.item_type" />
                    </div>
                    <div>
                        <InputLabel for="uom_id" value="Satuan (UOM)" />
                        <select
                            id="uom_id"
                            v-model="form.uom_id"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            required
                        >
                            <option value="" disabled>Pilih satuan</option>
                            <option v-for="unit in units" :key="unit.id" :value="unit.id">
                                {{ unit.code }} — {{ unit.name }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.uom_id" />
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="category_id" value="Kategori" />
                        <select
                            id="category_id"
                            v-model="form.category_id"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option value="">—</option>
                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.code }} — {{ category.name }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.category_id" />
                    </div>
                    <div>
                        <InputLabel for="min_stock" value="Min stock" />
                        <TextInput
                            id="min_stock"
                            v-model="form.min_stock"
                            type="number"
                            min="0"
                            step="0.001"
                            class="mt-1 block w-full"
                        />
                        <InputError class="mt-2" :message="form.errors.min_stock" />
                    </div>
                </div>

                <div>
                    <InputLabel for="description" value="Deskripsi" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                    <InputError class="mt-2" :message="form.errors.description" />
                </div>

                <div class="flex flex-wrap gap-5">
                    <label
                        v-if="form.item_type === 'asset'"
                        class="flex items-center gap-2 text-sm text-slate-700"
                    >
                        <input
                            v-model="form.is_serialized"
                            type="checkbox"
                            class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                        />
                        Serialized (unit asset)
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                        />
                        Barang aktif
                    </label>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link
                    :href="
                        isEdit
                            ? route('admin.items.show', item.id)
                            : route('admin.items.index')
                    "
                >
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">
                    {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
