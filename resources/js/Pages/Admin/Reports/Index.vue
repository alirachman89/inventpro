<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    catalog: Array,
});
</script>

<template>
    <Head title="Laporan" />

    <AuthenticatedLayout>
        <template #header-title>Laporan Inventory</template>
        <template #header-subtitle>Stok, mutasi, PO, opname, pinjam, asset — export Excel/PDF</template>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="item in catalog"
                :key="item.key"
                :href="route('admin.reports.show', item.key)"
                class="surface-card block p-5 transition hover:border-brand-300 hover:shadow-sm"
            >
                <h3 class="text-base font-semibold text-slate-900">{{ item.title }}</h3>
                <p class="mt-2 text-sm text-slate-600">{{ item.description }}</p>
                <p class="mt-4 text-xs font-medium text-brand-700">Buka laporan →</p>
            </Link>
        </div>
    </AuthenticatedLayout>
</template>
