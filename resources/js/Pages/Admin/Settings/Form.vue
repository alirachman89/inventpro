<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object,
    timezones: Array,
});

const form = useForm({
    company_name: props.settings.company_name,
    company_address: props.settings.company_address,
    company_phone: props.settings.company_phone,
    timezone: props.settings.timezone,
    prefix_po: props.settings.prefix_po,
    prefix_gr: props.settings.prefix_gr,
    prefix_opname: props.settings.prefix_opname,
    prefix_borrow: props.settings.prefix_borrow,
    prefix_movement: props.settings.prefix_movement,
});

function submit() {
    form.put(route('admin.settings.update'));
}
</script>

<template>
    <Head title="Pengaturan" />

    <AuthenticatedLayout>
        <template #header-title>Pengaturan</template>
        <template #header-subtitle>Data perusahaan dan prefix nomor dokumen</template>

        <form class="mx-auto max-w-3xl space-y-6" @submit.prevent="submit">
            <div class="surface-card space-y-5 p-6">
                <h2 class="text-sm font-semibold text-slate-800">Perusahaan</h2>

                <div>
                    <InputLabel for="company_name" value="Nama perusahaan" />
                    <TextInput
                        id="company_name"
                        v-model="form.company_name"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.company_name" />
                </div>

                <div>
                    <InputLabel for="company_address" value="Alamat" />
                    <textarea
                        id="company_address"
                        v-model="form.company_address"
                        rows="3"
                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                    <InputError class="mt-2" :message="form.errors.company_address" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="company_phone" value="Telepon" />
                        <TextInput
                            id="company_phone"
                            v-model="form.company_phone"
                            class="mt-1 block w-full"
                        />
                        <InputError class="mt-2" :message="form.errors.company_phone" />
                    </div>
                    <div>
                        <InputLabel for="timezone" value="Timezone" />
                        <select
                            id="timezone"
                            v-model="form.timezone"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            required
                        >
                            <option
                                v-for="tz in timezones"
                                :key="tz"
                                :value="tz"
                            >
                                {{ tz }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.timezone" />
                    </div>
                </div>
            </div>

            <div class="surface-card space-y-5 p-6">
                <h2 class="text-sm font-semibold text-slate-800">Prefix nomor dokumen</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="prefix_po" value="Purchase Order" />
                        <TextInput id="prefix_po" v-model="form.prefix_po" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.prefix_po" />
                    </div>
                    <div>
                        <InputLabel for="prefix_gr" value="Goods Receipt" />
                        <TextInput id="prefix_gr" v-model="form.prefix_gr" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.prefix_gr" />
                    </div>
                    <div>
                        <InputLabel for="prefix_opname" value="Opname Barang" />
                        <TextInput id="prefix_opname" v-model="form.prefix_opname" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.prefix_opname" />
                    </div>
                    <div>
                        <InputLabel for="prefix_borrow" value="Peminjaman" />
                        <TextInput id="prefix_borrow" v-model="form.prefix_borrow" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.prefix_borrow" />
                    </div>
                    <div>
                        <InputLabel for="prefix_movement" value="Mutasi barang" />
                        <TextInput id="prefix_movement" v-model="form.prefix_movement" class="mt-1 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.prefix_movement" />
                    </div>
                </div>
            </div>

            <div class="flex justify-end">
                <PrimaryButton :disabled="form.processing">Simpan Pengaturan</PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
