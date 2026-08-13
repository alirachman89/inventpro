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
    vendor: Object,
});

const isEdit = computed(() => !!props.vendor);

const form = useForm({
    code: props.vendor?.code || '',
    name: props.vendor?.name || '',
    tax_id: props.vendor?.tax_id || '',
    contact_person: props.vendor?.contact_person || '',
    email: props.vendor?.email || '',
    phone: props.vendor?.phone || '',
    address: props.vendor?.address || '',
    notes: props.vendor?.notes || '',
    is_active: props.vendor?.is_active ?? true,
});

function submit() {
    if (isEdit.value) {
        form.put(route('admin.vendors.update', props.vendor.id));
    } else {
        form.post(route('admin.vendors.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Vendor' : 'Tambah Vendor'" />

    <AuthenticatedLayout>
        <template #header-title>{{ isEdit ? 'Edit Vendor' : 'Tambah Vendor' }}</template>
        <template #header-subtitle>Form master pemasok</template>

        <form class="mx-auto max-w-3xl space-y-6" @submit.prevent="submit">
            <div class="surface-card space-y-5 p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="code" value="Kode" />
                        <TextInput id="code" v-model="form.code" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>
                    <div>
                        <InputLabel for="name" value="Nama vendor" />
                        <TextInput id="name" v-model="form.name" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="tax_id" value="NPWP (opsional)" />
                        <TextInput id="tax_id" v-model="form.tax_id" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <InputLabel for="contact_person" value="PIC / kontak" />
                        <TextInput
                            id="contact_person"
                            v-model="form.contact_person"
                            class="mt-1 block w-full"
                        />
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
                    <InputLabel for="address" value="Alamat" />
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
                    Vendor aktif
                </label>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link
                    :href="
                        isEdit
                            ? route('admin.vendors.show', vendor.id)
                            : route('admin.vendors.index')
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
