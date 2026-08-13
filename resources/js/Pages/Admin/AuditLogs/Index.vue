<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

const props = defineProps({
    logs: Object,
    filters: Object,
    modules: Array,
    actions: Array,
});

const form = reactive({
    search: props.filters.search || '',
    module: props.filters.module || '',
    action: props.filters.action || '',
});

watch(
    form,
    (value) => {
        router.get(
            route('admin.audit-logs.index'),
            {
                search: value.search || undefined,
                module: value.module || undefined,
                action: value.action || undefined,
            },
            { preserveState: true, replace: true },
        );
    },
    { deep: true },
);
</script>

<template>
    <Head title="Audit Log" />

    <AuthenticatedLayout>
        <template #header-title>Audit Log</template>
        <template #header-subtitle
            >Jejak aktivitas sistem untuk audit</template
        >

        <div class="space-y-4">
            <div class="grid gap-3 md:grid-cols-3">
                <input
                    v-model="form.search"
                    type="search"
                    placeholder="Cari deskripsi / aktor..."
                    class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                />
                <select
                    v-model="form.module"
                    class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                >
                    <option value="">Semua modul</option>
                    <option v-for="module in modules" :key="module" :value="module">
                        {{ module }}
                    </option>
                </select>
                <select
                    v-model="form.action"
                    class="rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                >
                    <option value="">Semua aksi</option>
                    <option v-for="action in actions" :key="action" :value="action">
                        {{ action }}
                    </option>
                </select>
            </div>

            <div class="surface-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead
                            class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                        >
                            <tr>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">Aktor</th>
                                <th class="px-4 py-3">Modul</th>
                                <th class="px-4 py-3">Aksi</th>
                                <th class="px-4 py-3">Deskripsi</th>
                                <th class="px-4 py-3 text-right">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="log in logs.data" :key="log.id">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">
                                    {{ log.created_at }}
                                </td>
                                <td class="px-4 py-3 text-slate-800">
                                    {{ log.actor_name }}
                                </td>
                                <td class="px-4 py-3 text-slate-600">
                                    {{ log.module }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700"
                                    >
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="max-w-xs truncate px-4 py-3 text-slate-600">
                                    {{ log.description }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="
                                            route(
                                                'admin.audit-logs.show',
                                                log.id,
                                            )
                                        "
                                        class="btn-ghost px-2 py-1"
                                    >
                                        Lihat
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="!logs.data.length">
                                <td
                                    colspan="6"
                                    class="px-4 py-8 text-center text-slate-500"
                                >
                                    Belum ada data
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 px-4 py-3">
                    <Pagination :links="logs.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
