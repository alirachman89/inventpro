<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    workflows: Array,
});
</script>

<template>
    <Head title="Workflow Approval" />

    <AuthenticatedLayout>
        <template #header-title>Workflow Approval</template>
        <template #header-subtitle
            >Konfigurasi alur persetujuan dokumen</template
        >

        <div class="space-y-4">
            <div
                v-for="workflow in workflows"
                :key="workflow.id"
                class="surface-card p-5"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">
                            {{ workflow.name }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Tipe: {{ workflow.document_type }} ·
                            {{ workflow.steps_count }} langkah ·
                            {{ workflow.is_active ? 'Aktif' : 'Nonaktif' }}
                        </p>
                        <ol class="mt-3 space-y-1 text-sm text-slate-600">
                            <li
                                v-for="step in workflow.steps"
                                :key="step.id"
                            >
                                {{ step.step_order }}. {{ step.name }} —
                                role
                                <span class="font-medium">{{
                                    step.approver_role
                                }}</span>
                                ({{ step.mode }})
                            </li>
                        </ol>
                    </div>
                    <Link
                        :href="
                            route(
                                'admin.approval-workflows.edit',
                                workflow.id,
                            )
                        "
                        class="btn-secondary"
                    >
                        Atur
                    </Link>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
