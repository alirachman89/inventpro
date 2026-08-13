<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    pending: Array,
    mine: Array,
});

function statusClass(status) {
    return {
        pending: 'bg-amber-50 text-amber-700',
        approved: 'bg-emerald-50 text-emerald-700',
        rejected: 'bg-red-50 text-red-700',
    }[status] || 'bg-slate-100 text-slate-700';
}
</script>

<template>
    <Head title="Persetujuan" />

    <AuthenticatedLayout>
        <template #header-title>Persetujuan</template>
        <template #header-subtitle
            >Dokumen menunggu keputusan dan pengajuan Anda</template
        >

        <div class="space-y-8">
            <section class="space-y-3">
                <h2 class="text-lg font-semibold text-slate-900">
                    Menunggu keputusan saya
                </h2>
                <div v-if="pending.length" class="space-y-3">
                    <div
                        v-for="item in pending"
                        :key="item.id"
                        class="surface-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="font-medium text-slate-900">
                                {{ item.document_label }}
                            </p>
                            <p class="mt-1 text-sm text-slate-500">
                                Dari {{ item.submitter?.name }} ·
                                {{ item.current_step_name }} ·
                                {{ item.submitted_at }}
                            </p>
                        </div>
                        <Link
                            :href="route('approvals.show', item.id)"
                            class="btn-primary"
                        >
                            Proses
                        </Link>
                    </div>
                </div>
                <div v-else class="surface-card p-6 text-sm text-slate-500">
                    Tidak ada dokumen yang menunggu keputusan Anda.
                </div>
            </section>

            <section class="space-y-3">
                <h2 class="text-lg font-semibold text-slate-900">
                    Pengajuan saya
                </h2>
                <div v-if="mine.length" class="space-y-3">
                    <div
                        v-for="item in mine"
                        :key="item.id"
                        class="surface-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="font-medium text-slate-900">
                                {{ item.document_label }}
                            </p>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ item.submitted_at }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                :class="statusClass(item.status)"
                            >
                                {{ item.status }}
                            </span>
                            <Link
                                :href="route('approvals.show', item.id)"
                                class="btn-secondary"
                            >
                                Detail
                            </Link>
                        </div>
                    </div>
                </div>
                <div v-else class="surface-card p-6 text-sm text-slate-500">
                    Belum ada pengajuan.
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
