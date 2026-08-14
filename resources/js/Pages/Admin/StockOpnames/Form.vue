<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    locations: Array,
    users: Array,
});

const form = useForm({
    location_id: props.locations[0]?.id || '',
    opname_date: new Date().toISOString().slice(0, 10),
    pic_user_id: '',
    notes: '',
});

const submit = () => form.post(route('admin.stock-opnames.store'));
</script>

<template>
    <Head title="Buat Opname Barang" />

    <AuthenticatedLayout>
        <template #header-title>Buat Opname Barang</template>
        <template #header-subtitle>
            Pilih lokasi — sistem generate daftar item per rak
        </template>

        <form class="mx-auto max-w-xl space-y-4" @submit.prevent="submit">
            <div class="surface-card space-y-4 p-5">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Lokasi</label>
                    <select
                        v-model="form.location_id"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        required
                    >
                        <option value="" disabled>Pilih lokasi</option>
                        <option v-for="location in locations" :key="location.id" :value="location.id">
                            {{ location.code }} — {{ location.name }}
                        </option>
                    </select>
                    <p v-if="form.errors.location_id" class="mt-1 text-xs text-rose-600">
                        {{ form.errors.location_id }}
                    </p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Tanggal</label>
                    <input
                        v-model="form.opname_date"
                        type="date"
                        required
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">PIC</label>
                    <select
                        v-model="form.pic_user_id"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">— Default: pembuat —</option>
                        <option v-for="user in users" :key="user.id" :value="user.id">
                            {{ user.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Catatan</label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-primary" :disabled="form.processing">
                    Buat & generate daftar
                </button>
                <Link :href="route('admin.stock-opnames.index')" class="btn-secondary">Batal</Link>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
