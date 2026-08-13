<script setup>
import Pagination from '@/Components/Pagination.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps({
    notifications: Object,
    filter: String,
});

function setFilter(filter) {
    router.get(
        route('notifications.index'),
        { filter: filter === 'all' ? undefined : filter },
        { preserveState: true, replace: true },
    );
}

function markAll() {
    router.post(route('notifications.read-all'));
}

function openNotification(item) {
    router.post(route('notifications.read', item.id));
}
</script>

<template>
    <Head title="Notifikasi" />

    <AuthenticatedLayout>
        <template #header-title>Notifikasi</template>
        <template #header-subtitle
            >Semua pemberitahuan untuk akun Anda</template
        >

        <div class="space-y-4">
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium"
                        :class="
                            filter === 'all'
                                ? 'bg-brand-600 text-white'
                                : 'border border-slate-200 bg-white text-slate-600'
                        "
                        @click="setFilter('all')"
                    >
                        Semua
                    </button>
                    <button
                        type="button"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium"
                        :class="
                            filter === 'unread'
                                ? 'bg-brand-600 text-white'
                                : 'border border-slate-200 bg-white text-slate-600'
                        "
                        @click="setFilter('unread')"
                    >
                        Belum dibaca
                    </button>
                </div>
                <SecondaryButton type="button" @click="markAll"
                    >Tandai semua dibaca</SecondaryButton
                >
            </div>

            <div class="space-y-3">
                <button
                    v-for="item in notifications.data"
                    :key="item.id"
                    type="button"
                    class="surface-card w-full p-4 text-left transition hover:border-brand-200"
                    :class="item.is_unread ? 'border-brand-200 bg-brand-50/40' : ''"
                    @click="openNotification(item)"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-900">
                                {{ item.title }}
                            </p>
                            <p class="mt-1 text-sm text-slate-600">
                                {{ item.message }}
                            </p>
                        </div>
                        <span class="shrink-0 text-xs text-slate-500">{{
                            item.created_at
                        }}</span>
                    </div>
                </button>

                <div
                    v-if="!notifications.data.length"
                    class="surface-card p-8 text-center text-sm text-slate-500"
                >
                    Belum ada notifikasi.
                </div>
            </div>

            <Pagination :links="notifications.links" />
        </div>
    </AuthenticatedLayout>
</template>
