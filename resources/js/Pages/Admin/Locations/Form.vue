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
    location: Object,
    types: Array,
});

const isEdit = computed(() => !!props.location);

const form = useForm({
    code: props.location?.code || '',
    name: props.location?.name || '',
    type: props.location?.type || 'warehouse',
    address: props.location?.address || '',
    is_active: props.location?.is_active ?? true,
});

function submit() {
    if (isEdit.value) {
        form.put(route('admin.locations.update', props.location.id));
    } else {
        form.post(route('admin.locations.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Lokasi' : 'Tambah Lokasi'" />

    <AuthenticatedLayout>
        <template #header-title>{{
            isEdit ? 'Edit Lokasi' : 'Tambah Lokasi'
        }}</template>
        <template #header-subtitle>
            {{
                isEdit
                    ? 'Perbarui data lokasi/gudang'
                    : 'Lokasi baru otomatis mendapat rak GENERAL'
            }}
        </template>

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

                <div>
                    <InputLabel for="address" value="Alamat" />
                    <textarea
                        id="address"
                        v-model="form.address"
                        rows="3"
                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                    <InputError class="mt-2" :message="form.errors.address" />
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                    />
                    Lokasi aktif
                </label>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link
                    :href="
                        isEdit
                            ? route('admin.locations.show', location.id)
                            : route('admin.locations.index')
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
