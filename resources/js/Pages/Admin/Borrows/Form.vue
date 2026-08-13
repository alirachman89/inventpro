<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    borrow: Object,
    borrowers: Array,
    clients: Array,
    assetUnits: Array,
});

const emptyLine = () => ({
    asset_unit_id: '',
    notes: '',
});

const form = useForm({
    borrower_user_id: props.borrow?.borrower_user_id || '',
    client_id: props.borrow?.client_id || '',
    borrow_date: props.borrow?.borrow_date || new Date().toISOString().slice(0, 10),
    due_date:
        props.borrow?.due_date ||
        new Date(Date.now() + 7 * 86400000).toISOString().slice(0, 10),
    purpose: props.borrow?.purpose || '',
    notes: props.borrow?.notes || '',
    lines: props.borrow?.lines?.length
        ? props.borrow.lines.map((line) => ({
              asset_unit_id: line.asset_unit_id || '',
              notes: line.notes || '',
          }))
        : [emptyLine()],
});

const addLine = () => form.lines.push(emptyLine());
const removeLine = (index) => {
    if (form.lines.length > 1) form.lines.splice(index, 1);
};

const unitLabel = (id) => {
    const unit = props.assetUnits.find((item) => item.id === id);
    if (!unit) return '';
    return `${unit.asset_tag} — ${unit.item?.sku} (${unit.location_code}/${unit.rack_code})`;
};

const submit = () => {
    if (props.borrow) {
        form.put(route('admin.borrows.update', props.borrow.id));
    } else {
        form.post(route('admin.borrows.store'));
    }
};
</script>

<template>
    <Head :title="borrow ? `Edit ${borrow.number}` : 'Buat Peminjaman'" />

    <AuthenticatedLayout>
        <template #header-title>
            {{ borrow ? `Edit ${borrow.number}` : 'Buat Peminjaman' }}
        </template>
        <template #header-subtitle>
            Isi Peminjam (Karyawan) dan Digunakan di Client secara terpisah
        </template>

        <form class="space-y-4" @submit.prevent="submit">
            <div class="surface-card space-y-4 p-5">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Peminjam (Karyawan) <span class="text-rose-600">*</span>
                        </label>
                        <select
                            v-model="form.borrower_user_id"
                            required
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option value="">Pilih karyawan</option>
                            <option
                                v-for="user in borrowers"
                                :key="user.id"
                                :value="user.id"
                            >
                                {{ user.name }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">
                            Siapa yang bertanggung jawab memegang barang.
                        </p>
                        <p v-if="form.errors.borrower_user_id" class="mt-1 text-xs text-rose-600">
                            {{ form.errors.borrower_user_id }}
                        </p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Digunakan di Client <span class="text-rose-600">*</span>
                        </label>
                        <select
                            v-model="form.client_id"
                            required
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option value="">Pilih client / site</option>
                            <option
                                v-for="client in clients"
                                :key="client.id"
                                :value="client.id"
                            >
                                {{ client.code }} — {{ client.name }}
                            </option>
                        </select>
                        <p class="mt-1 text-xs text-slate-500">
                            Di mana barang dipakai — bukan sama dengan peminjam.
                        </p>
                        <p v-if="form.errors.client_id" class="mt-1 text-xs text-rose-600">
                            {{ form.errors.client_id }}
                        </p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Tanggal pinjam
                        </label>
                        <input
                            v-model="form.borrow_date"
                            type="date"
                            required
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Jatuh tempo
                        </label>
                        <input
                            v-model="form.due_date"
                            type="date"
                            required
                            class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Tujuan</label>
                    <input
                        v-model="form.purpose"
                        type="text"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Contoh: Scaffold Holding untuk Client A"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">Catatan</label>
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                </div>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-slate-900">
                        Unit asset yang dipinjam
                    </h3>
                    <button type="button" class="btn-secondary" @click="addLine">+ Baris</button>
                </div>
                <div class="space-y-3 p-4">
                    <div
                        v-for="(line, index) in form.lines"
                        :key="index"
                        class="grid gap-3 rounded-xl border border-slate-200 p-3 md:grid-cols-[1fr_auto]"
                    >
                        <div>
                            <label class="mb-1 block text-xs font-medium text-slate-600">
                                Unit available
                            </label>
                            <select
                                v-model="line.asset_unit_id"
                                required
                                class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option value="">Pilih unit</option>
                                <option
                                    v-for="unit in assetUnits"
                                    :key="unit.id"
                                    :value="unit.id"
                                >
                                    {{ unit.asset_tag }} — {{ unit.item?.sku }} /
                                    {{ unit.item?.name }} ({{ unit.location_code }}/{{
                                        unit.rack_code
                                    }})
                                </option>
                            </select>
                            <p
                                v-if="form.errors[`lines.${index}.asset_unit_id`]"
                                class="mt-1 text-xs text-rose-600"
                            >
                                {{ form.errors[`lines.${index}.asset_unit_id`] }}
                            </p>
                        </div>
                        <button
                            v-if="form.lines.length > 1"
                            type="button"
                            class="self-end text-xs text-rose-600 hover:underline"
                            @click="removeLine(index)"
                        >
                            Hapus
                        </button>
                    </div>
                    <p v-if="form.errors.lines" class="text-sm text-rose-600">
                        {{ form.errors.lines }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <button type="submit" class="btn-primary" :disabled="form.processing">
                    Simpan
                </button>
                <Link
                    :href="
                        borrow
                            ? route('admin.borrows.show', borrow.id)
                            : route('admin.borrows.index')
                    "
                    class="btn-secondary"
                >
                    Batal
                </Link>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
