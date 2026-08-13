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
    unit: Object,
    types: Array,
});

const isEdit = computed(() => !!props.unit);

const form = useForm({
    code: props.unit?.code || '',
    name: props.unit?.name || '',
    symbol: props.unit?.symbol || '',
    type: props.unit?.type || 'count',
    description: props.unit?.description || '',
    is_active: props.unit?.is_active ?? true,
    sort_order: props.unit?.sort_order ?? 0,
});

function submit() {
    if (isEdit.value) {
        form.put(route('admin.units.update', props.unit.id));
    } else {
        form.post(route('admin.units.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Satuan' : 'Tambah Satuan'" />

    <AuthenticatedLayout>
        <template #header-title>{{
            isEdit ? 'Edit Satuan' : 'Tambah Satuan'
        }}</template>
        <template #header-subtitle>Form master unit of measure</template>

        <form class="mx-auto max-w-3xl space-y-6" @submit.prevent="submit">
            <div class="surface-card space-y-5 p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="code" value="Kode" />
                        <TextInput
                            id="code"
                            v-model="form.code"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>
                    <div>
                        <InputLabel for="name" value="Nama" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="symbol" value="Simbol (opsional)" />
                        <TextInput
                            id="symbol"
                            v-model="form.symbol"
                            class="mt-1 block w-full"
                        />
                        <InputError class="mt-2" :message="form.errors.symbol" />
                    </div>
                    <div>
                        <InputLabel for="type" value="Tipe" />
                        <select
                            id="type"
                            v-model="form.type"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            required
                        >
                            <option v-for="type in types" :key="type" :value="type">
                                {{ type }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.type" />
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

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="sort_order" value="Urutan" />
                        <TextInput
                            id="sort_order"
                            v-model="form.sort_order"
                            type="number"
                            min="0"
                            class="mt-1 block w-full"
                        />
                        <InputError class="mt-2" :message="form.errors.sort_order" />
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                            />
                            Satuan aktif
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link :href="route('admin.units.index')">
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">
                    {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
