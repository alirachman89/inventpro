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
    statuses: Array,
    conditions: Array,
    locations: Array,
    clients: Array,
});

const form = useForm({
    asset_tag: '',
    serial_number: '',
    status: 'available',
    condition: 'good',
    location_id: '',
    rack_id: '',
    current_client_id: '',
    notes: '',
});

const racks = computed(() => {
    const location = props.locations.find((item) => item.id === form.location_id);
    return location?.racks || [];
});

watch(
    () => form.location_id,
    () => {
        const general = racks.value.find((item) => item.is_default);
        form.rack_id = general?.id || '';
    },
);

function submit() {
    form.post(route('admin.asset-units.store', props.item.id));
}
</script>

<template>
    <Head title="Tambah Unit Asset" />

    <AuthenticatedLayout>
        <template #header-title>Tambah Unit Asset</template>
        <template #header-subtitle>{{ item.sku }} — {{ item.name }}</template>

        <form class="mx-auto max-w-3xl space-y-6" @submit.prevent="submit">
            <div class="surface-card space-y-5 p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="asset_tag" value="Asset tag" />
                        <TextInput
                            id="asset_tag"
                            v-model="form.asset_tag"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.asset_tag" />
                    </div>
                    <div>
                        <InputLabel for="serial_number" value="Serial number" />
                        <TextInput
                            id="serial_number"
                            v-model="form.serial_number"
                            class="mt-1 block w-full"
                        />
                        <InputError class="mt-2" :message="form.errors.serial_number" />
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="status" value="Status" />
                        <select
                            id="status"
                            v-model="form.status"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option v-for="status in statuses" :key="status" :value="status">
                                {{ status }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="condition" value="Kondisi" />
                        <select
                            id="condition"
                            v-model="form.condition"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option
                                v-for="condition in conditions"
                                :key="condition"
                                :value="condition"
                            >
                                {{ condition }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel for="location_id" value="Lokasi" />
                        <select
                            id="location_id"
                            v-model="form.location_id"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            required
                        >
                            <option value="" disabled>Pilih lokasi</option>
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
                        <InputLabel for="rack_id" value="Rak" />
                        <select
                            id="rack_id"
                            v-model="form.rack_id"
                            class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option value="">GENERAL (default)</option>
                            <option v-for="rack in racks" :key="rack.id" :value="rack.id">
                                {{ rack.code }} — {{ rack.label }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.rack_id" />
                    </div>
                </div>

                <div>
                    <InputLabel for="current_client_id" value="Dipakai di Client (opsional)" />
                    <select
                        id="current_client_id"
                        v-model="form.current_client_id"
                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">—</option>
                        <option
                            v-for="client in clients"
                            :key="client.id"
                            :value="client.id"
                        >
                            {{ client.code }} — {{ client.name }}
                        </option>
                    </select>
                    <p class="mt-1 text-xs text-slate-500">
                        Client = konteks pemakaian, bukan pemegang/karyawan.
                    </p>
                    <InputError class="mt-2" :message="form.errors.current_client_id" />
                </div>

                <div>
                    <InputLabel for="notes" value="Catatan" />
                    <textarea
                        id="notes"
                        v-model="form.notes"
                        rows="3"
                        class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link :href="route('admin.items.show', item.id)">
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
