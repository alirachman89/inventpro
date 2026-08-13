<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useConfirm } from '@/composables/useConfirm';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    approval: Object,
    canAct: Boolean,
});

const { confirm } = useConfirm();

const form = useForm({
    comment: '',
});

async function approve() {
    const ok = await confirm({
        title: 'Setujui dokumen?',
        message: `Setujui ${props.approval.document_type_label || props.approval.document_type} ${props.approval.document_label}? Keputusan ini akan memajukan alur approval.`,
        confirmLabel: 'Ya, setujui',
        variant: 'warning',
    });

    if (ok) {
        form.post(route('approvals.approve', props.approval.id));
    }
}

async function reject() {
    if (!form.comment?.trim()) {
        form.setError('comment', 'Komentar wajib diisi jika menolak.');
        return;
    }

    const ok = await confirm({
        title: 'Tolak dokumen?',
        message: `Tolak ${props.approval.document_type_label || props.approval.document_type} ${props.approval.document_label}? Dokumen akan dikembalikan ke pengaju.`,
        confirmLabel: 'Ya, tolak',
        variant: 'danger',
    });

    if (ok) {
        form.clearErrors('comment');
        form.post(route('approvals.reject', props.approval.id));
    }
}
</script>

<template>
    <Head title="Detail Persetujuan" />

    <AuthenticatedLayout>
        <template #header-title>Detail Persetujuan</template>
        <template #header-subtitle>
            {{ approval.document_type_label || approval.document_type }} ·
            {{ approval.document_label }}
        </template>

        <div class="mx-auto max-w-4xl space-y-4">
            <div class="flex flex-wrap justify-end gap-2">
                <a
                    v-if="approval.document?.url"
                    :href="approval.document.url"
                    class="inline-flex"
                >
                    <SecondaryButton type="button">
                        {{ approval.document.url_label || 'Buka dokumen' }}
                    </SecondaryButton>
                </a>
                <Link :href="route('approvals.index')">
                    <SecondaryButton type="button">Kembali</SecondaryButton>
                </Link>
            </div>

            <div class="surface-card space-y-3 p-6">
                <p class="text-sm text-slate-500">
                    Jenis:
                    <span class="font-semibold text-slate-800">{{
                        approval.document_type_label || approval.document_type
                    }}</span>
                </p>
                <p class="text-sm text-slate-500">
                    Status:
                    <span class="font-semibold text-slate-800">{{
                        approval.status
                    }}</span>
                </p>
                <p class="text-sm text-slate-500">
                    Diajukan oleh {{ approval.submitter?.name }} pada
                    {{ approval.submitted_at }}
                </p>
                <p v-if="approval.current_step_name" class="text-sm text-slate-500">
                    Langkah aktif: {{ approval.current_step_name }}
                </p>
                <p
                    v-if="approval.document?.purpose"
                    class="rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-700"
                >
                    {{ approval.document.purpose }}
                </p>
            </div>

            <div
                v-if="approval.document?.summary?.length"
                class="surface-card space-y-4 p-6"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-slate-900">
                        Ringkasan dokumen
                    </h3>
                    <span class="text-sm font-medium text-slate-800">
                        {{ approval.document.title }}
                    </span>
                </div>
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="(row, index) in approval.document.summary"
                        :key="index"
                    >
                        <dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                            {{ row.label }}
                        </dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ row.value }}</dd>
                    </div>
                </dl>
            </div>

            <div
                v-if="approval.document?.lines?.length"
                class="surface-card overflow-hidden"
            >
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">
                        {{ approval.document.lines_title || 'Detail baris' }}
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead
                            class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            <tr>
                                <th
                                    v-for="(col, index) in approval.document.line_columns"
                                    :key="index"
                                    class="px-4 py-3"
                                >
                                    {{ col }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="(line, rowIndex) in approval.document.lines"
                                :key="rowIndex"
                            >
                                <td
                                    v-for="(cell, cellIndex) in line"
                                    :key="cellIndex"
                                    class="px-4 py-3 text-slate-700"
                                    :class="{
                                        'font-medium text-amber-700':
                                            approval.document.kind === 'stock_opname' &&
                                            cellIndex === line.length - 1,
                                    }"
                                >
                                    {{ cell }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div
                v-else-if="approval.document?.kind === 'stock_opname'"
                class="surface-card p-6 text-sm text-slate-500"
            >
                Tidak ada baris berselisih pada dokumen ini.
            </div>

            <div class="surface-card p-6">
                <h3 class="text-sm font-semibold text-slate-900">
                    Alur langkah
                </h3>
                <ol class="mt-3 space-y-2 text-sm text-slate-600">
                    <li v-for="step in approval.steps" :key="step.step_order">
                        {{ step.step_order }}. {{ step.name }} —
                        {{ step.approver_role }} ({{ step.mode }})
                    </li>
                </ol>
            </div>

            <div class="surface-card p-6">
                <h3 class="text-sm font-semibold text-slate-900">
                    Riwayat keputusan
                </h3>
                <div v-if="approval.actions?.length" class="mt-3 space-y-3">
                    <div
                        v-for="(action, index) in approval.actions"
                        :key="index"
                        class="rounded-xl bg-slate-50 px-3 py-2 text-sm"
                    >
                        <p class="font-medium text-slate-800">
                            {{ action.actor_name }} · {{ action.action }} ·
                            langkah {{ action.step_order }}
                        </p>
                        <p class="text-slate-500">{{ action.created_at }}</p>
                        <p v-if="action.comment" class="mt-1 text-slate-600">
                            {{ action.comment }}
                        </p>
                    </div>
                </div>
                <p v-else class="mt-3 text-sm text-slate-500">
                    Belum ada keputusan.
                </p>
            </div>

            <div v-if="canAct" class="surface-card space-y-4 p-6">
                <h3 class="text-sm font-semibold text-slate-900">
                    Berikan keputusan
                </h3>
                <div>
                    <InputLabel for="comment" value="Komentar" />
                    <textarea
                        id="comment"
                        v-model="form.comment"
                        rows="3"
                        class="mt-1 block w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Wajib diisi jika menolak"
                    />
                    <InputError class="mt-2" :message="form.errors.comment" />
                    <InputError class="mt-2" :message="form.errors.approval" />
                </div>
                <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                    <button
                        type="button"
                        class="btn-danger"
                        :disabled="form.processing"
                        @click="reject"
                    >
                        Tolak
                    </button>
                    <PrimaryButton
                        type="button"
                        :disabled="form.processing"
                        @click="approve"
                    >
                        Setujui
                    </PrimaryButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
