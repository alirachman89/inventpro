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
    category: Object,
});

const isEdit = computed(() => !!props.category);

const form = useForm({
    code: props.category?.code || '',
    name: props.category?.name || '',
    description: props.category?.description || '',
    is_active: props.category?.is_active ?? true,
    sort_order: props.category?.sort_order ?? 0,
});

function submit() {
    if (isEdit.value) {
        form.put(route('admin.categories.update', props.category.id));
    } else {
        form.post(route('admin.categories.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Kategori' : 'Tambah Kategori'" />

    <AuthenticatedLayout>
        <template #header-title>{{
            isEdit ? 'Edit Kategori' : 'Tambah Kategori'
        }}</template>
        <template #header-subtitle>Form master kategori barang</template>

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
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input
                                v-model="form.is_active"
                                type="checkbox"
                                class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                            />
                            Kategori aktif
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link :href="route('admin.categories.index')">
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">
                    {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
