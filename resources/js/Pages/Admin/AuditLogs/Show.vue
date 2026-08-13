<script setup>
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    log: Object,
});
</script>

<template>
    <Head title="Detail Audit Log" />

    <AuthenticatedLayout>
        <template #header-title>Detail Audit Log</template>
        <template #header-subtitle>{{ log.description }}</template>

        <div class="mx-auto max-w-4xl space-y-4">
            <div class="flex justify-end">
                <Link :href="route('admin.audit-logs.index')">
                    <SecondaryButton type="button">Kembali</SecondaryButton>
                </Link>
            </div>

            <div class="surface-card grid gap-4 p-6 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">
                        Waktu
                    </p>
                    <p class="mt-1 text-sm text-slate-900">{{ log.created_at }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">
                        Aktor
                    </p>
                    <p class="mt-1 text-sm text-slate-900">{{ log.actor_name }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">
                        Modul
                    </p>
                    <p class="mt-1 text-sm text-slate-900">{{ log.module }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">
                        Aksi
                    </p>
                    <p class="mt-1 text-sm text-slate-900">{{ log.action }}</p>
                </div>
                <div class="sm:col-span-2">
                    <p class="text-xs font-semibold uppercase text-slate-500">
                        Deskripsi
                    </p>
                    <p class="mt-1 text-sm text-slate-900">{{ log.description }}</p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">
                        IP Address
                    </p>
                    <p class="mt-1 font-mono text-sm text-slate-900">
                        {{ log.ip_address || '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase text-slate-500">
                        User Agent
                    </p>
                    <p class="mt-1 break-all text-sm text-slate-700">
                        {{ log.user_agent || '—' }}
                    </p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="surface-card p-6">
                    <h3 class="text-sm font-semibold text-slate-900">
                        Nilai sebelumnya
                    </h3>
                    <pre
                        class="mt-3 overflow-x-auto rounded-xl bg-slate-50 p-4 text-xs text-slate-700"
                        >{{
                            log.old_values
                                ? JSON.stringify(log.old_values, null, 2)
                                : '—'
                        }}</pre
                    >
                </div>
                <div class="surface-card p-6">
                    <h3 class="text-sm font-semibold text-slate-900">
                        Nilai baru
                    </h3>
                    <pre
                        class="mt-3 overflow-x-auto rounded-xl bg-slate-50 p-4 text-xs text-slate-700"
                        >{{
                            log.new_values
                                ? JSON.stringify(log.new_values, null, 2)
                                : '—'
                        }}</pre
                    >
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
