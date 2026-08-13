<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

defineProps({
    demos: Object,
});

const form = useForm({
    title: '',
    amount: '',
    submit_now: true,
});

function submit() {
    form.post(route('approval-demos.store'), {
        onSuccess: () => form.reset('title', 'amount'),
    });
}

function submitExisting(id) {
    router.post(route('approval-demos.submit', id));
}
</script>

<template>
    <Head title="Uji Approval" />

    <AuthenticatedLayout>
        <template #header-title>Uji Approval</template>
        <template #header-subtitle
            >Buat dokumen uji untuk menguji alur persetujuan</template
        >

        <div class="space-y-6">
            <form class="surface-card space-y-4 p-6" @submit.prevent="submit">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="title" value="Judul dokumen" />
                        <TextInput
                            id="title"
                            v-model="form.title"
                            class="mt-1 block w-full"
                            required
                            placeholder="Contoh: Permohonan Scaffold Client A"
                        />
                        <InputError class="mt-2" :message="form.errors.title" />
                    </div>
                    <div>
                        <InputLabel for="amount" value="Nilai (opsional)" />
                        <TextInput
                            id="amount"
                            v-model="form.amount"
                            type="number"
                            min="0"
                            step="0.01"
                            class="mt-1 block w-full"
                        />
                    </div>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        v-model="form.submit_now"
                        type="checkbox"
                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                    />
                    Ajukan approval sekarang
                </label>
                <PrimaryButton :disabled="form.processing"
                    >Buat Dokumen Uji</PrimaryButton
                >
            </form>

            <div class="surface-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead
                            class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            <tr>
                                <th class="px-4 py-3">Judul</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Pembuat</th>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="demo in demos.data" :key="demo.id">
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    {{ demo.title }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ demo.status }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ demo.creator }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ demo.created_at }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        v-if="
                                            ['draft', 'rejected'].includes(
                                                demo.status,
                                            )
                                        "
                                        type="button"
                                        class="btn-ghost px-2 py-1"
                                        @click="submitExisting(demo.id)"
                                    >
                                        Ajukan
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!demos.data.length">
                                <td
                                    colspan="5"
                                    class="px-4 py-8 text-center text-slate-500"
                                >
                                    Belum ada data
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    <Pagination :links="demos.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
