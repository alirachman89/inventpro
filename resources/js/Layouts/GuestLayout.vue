<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col justify-center px-4 py-10 sm:px-6"
    >
        <div
            class="pointer-events-none absolute inset-0 overflow-hidden"
            aria-hidden="true"
        >
            <div
                class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-brand-200/40 blur-3xl"
            />
            <div
                class="absolute -right-16 bottom-0 h-80 w-80 rounded-full bg-sky-200/30 blur-3xl"
            />
        </div>

        <div class="relative mx-auto w-full max-w-md">
            <div class="mb-8 flex justify-center">
                <Link :href="route('login')">
                    <ApplicationLogo />
                </Link>
            </div>

            <div class="surface-card px-6 py-8 sm:px-8">
                <slot />
            </div>

            <p class="mt-6 text-center text-xs text-slate-500">
                InventPro &middot; Secure inventory operations
            </p>
        </div>

        <ConfirmModal />
    </div>
</template>
