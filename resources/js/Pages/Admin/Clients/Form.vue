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
    client: Object,
    types: Array,
    typeLabels: Object,
});

const isEdit = computed(() => !!props.client);

const form = useForm({
    code: props.client?.code || '',
    name: props.client?.name || '',
    type: props.client?.type || 'project_site',
    contact_person: props.client?.contact_person || '',
    email: props.client?.email || '',
    phone: props.client?.phone || '',
    address: props.client?.address || '',
    tax_id: props.client?.tax_id || '',
    notes: props.client?.notes || '',
    is_active: props.client?.is_active ?? true,
});

function submit() {
    if (isEdit.value) {
        form.put(route('admin.clients.update', props.client.id));
    } else {
        form.post(route('admin.clients.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Client' : 'Tambah Client'" />

    <AuthenticatedLayout>
        <template #header-title>{{ isEdit ? 'Edit Client' : 'Tambah Client' }}</template>
        <template #header-subtitle>
            Form master konteks pemakaian (bukan akun login)
        </template>

        <form class="mx-auto max-w-3xl space-y-6" @submit.prevent="submit">
            <div class="surface-card space-y-5 p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="code" value="Kode" />
                        <TextInput id="code" v-model="form.code" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>
                    <div>
                        <InputLabel for="name" value="Nama client / proyek" />
                        <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required />
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
                        <option v-for="item in types" :key="item" :value="item">
                            {{ typeLabels[item] || item }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.type" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="contact_person" value="PIC di sisi client" />
                        <TextInput
                            id="contact_person"
                            v-model="form.contact_person"
                            class="mt-1 block w-full"
                        />
                    </div>
                    <div>
                        <InputLabel for="tax_id" value="NPWP (opsional)" />
                        <TextInput id="tax_id" v-model="form.tax_id" class="mt-1 block w-full" />
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>
                    <div>
                        <InputLabel for="phone" value="Telepon" />
                        <TextInput id="phone" v-model="form.phone" class="mt-1 block w-full" />
                    </div>
                </div>

                <div>
                    <InputLabel for="address" value="Alamat / lokasi site" />
                    <textarea
                        id="address"
                        v-model="form.address"
                        rows="3"
                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                </div>

                <div>
                    <InputLabel for="notes" value="Catatan" />
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="2"
                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                    />
                    Client aktif
                </label>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link
                    :href="
                        isEdit
                            ? route('admin.clients.show', client.id)
                            : route('admin.clients.index')
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
