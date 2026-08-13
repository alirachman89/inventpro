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
    location: Object,
    rack: Object,
});

const isEdit = computed(() => !!props.rack);
const isGeneral = computed(() => !!props.rack?.is_general);

const form = useForm({
    code: props.rack?.code || '',
    name: props.rack?.name || '',
    label: props.rack?.label || '',
    description: props.rack?.description || '',
    is_active: props.rack?.is_active ?? true,
});

watch(
    () => form.code,
    (value) => {
        if (!isEdit.value && !form.label) {
            form.label = String(value || '').toUpperCase();
        }
    },
);

function submit() {
    if (isEdit.value) {
        form.put(route('admin.racks.update', props.rack.id));
    } else {
        form.post(route('admin.racks.store', props.location.id));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Rak' : 'Tambah Rak'" />

    <AuthenticatedLayout>
        <template #header-title>{{ isEdit ? 'Edit Rak' : 'Tambah Rak' }}</template>
        <template #header-subtitle>
            Lokasi {{ location.code }} — {{ location.name }}
        </template>

        <form class="mx-auto max-w-3xl space-y-6" @submit.prevent="submit">
            <div class="surface-card space-y-5 p-6">
                <div
                    v-if="isGeneral"
                    class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                >
                    Rak GENERAL wajib ada. Kode tidak dapat diubah; label boleh diganti.
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="code" value="Kode" />
                        <TextInput
                            id="code"
                            v-model="form.code"
                            class="mt-1 block w-full"
                            :disabled="isGeneral"
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
                    <InputLabel for="label" value="Label" />
                    <TextInput
                        id="label"
                        v-model="form.label"
                        class="mt-1 block w-full"
                        required
                    />
                    <p class="mt-1 text-xs text-slate-500">
                        Label fisik/cetak/UI — boleh berbeda dari kode.
                    </p>
                    <InputError class="mt-2" :message="form.errors.label" />
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

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                    />
                    Rak aktif
                </label>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link :href="route('admin.locations.show', location.id)">
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">
                    {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
