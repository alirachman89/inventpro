<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { useCan } from '@/composables/useCan';
import { useToast } from '@/composables/useToast';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();
const sidebarOpen = ref(false);
const { can } = useCan();
const toast = useToast();

const user = computed(() => page.props.auth.user);

const navItems = computed(() => {
    const items = [
        {
            label: 'Dashboard',
            href: route('dashboard'),
            active: route().current('dashboard'),
            show: can('dashboard.view'),
            icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4',
        },
        {
            label: 'Pengguna',
            href: route('admin.users.index'),
            active: route().current('admin.users.*'),
            show: can('users.view'),
            icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
        },
        {
            label: 'Role & Permission',
            href: route('admin.roles.index'),
            active: route().current('admin.roles.*'),
            show: can('roles.view'),
            icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
        },
        {
            label: 'Audit Log',
            href: route('admin.audit-logs.index'),
            active: route().current('admin.audit-logs.*'),
            show: can('audit_logs.view'),
            icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        },
        {
            label: 'Profil',
            href: route('profile.edit'),
            active: route().current('profile.*'),
            show: true,
            icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
        },
    ];

    return items.filter((item) => item.show);
});

watch(
    () => page.url,
    () => {
        sidebarOpen.value = false;
    },
);

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toast.success('Berhasil', flash.success);
        }
        if (flash?.error) {
            toast.error('Gagal', flash.error);
        }
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <div class="min-h-screen">
        <!-- Mobile overlay -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-300 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : ''"
        >
            <div class="flex h-16 items-center border-b border-slate-200 px-5">
                <Link :href="route('dashboard')">
                    <ApplicationLogo />
                </Link>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <p
                    class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                >
                    Menu utama
                </p>
                <Link
                    v-for="item in navItems"
                    :key="item.label"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition"
                    :class="
                        item.active
                            ? 'bg-brand-50 text-brand-700'
                            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                    "
                >
                    <svg
                        class="h-5 w-5 shrink-0"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            :d="item.icon"
                        />
                    </svg>
                    {{ item.label }}
                </Link>
            </nav>

            <div class="border-t border-slate-200 p-4">
                <p class="text-xs leading-relaxed text-slate-500">
                    InventPro &middot; Inventory Warehouse Platform
                </p>
            </div>
        </aside>

        <!-- Main -->
        <div class="lg:pl-72">
            <header
                class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur"
            >
                <div
                    class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6"
                >
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 lg:hidden"
                            @click="sidebarOpen = true"
                        >
                            <span class="sr-only">Buka menu</span>
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                            </svg>
                        </button>
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-semibold text-slate-900"
                            >
                                <slot name="header-title">InventPro</slot>
                            </p>
                            <p class="truncate text-xs text-slate-500">
                                <slot name="header-subtitle"
                                    >Platform inventory warehouse</slot
                                >
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3">
                        <Dropdown align="right" width="72">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="relative inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                                    title="Notifikasi"
                                    aria-label="Notifikasi"
                                >
                                    <svg
                                        class="h-5 w-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                                        />
                                    </svg>
                                </button>
                            </template>
                            <template #content>
                                <div class="px-4 py-4">
                                    <p class="text-sm font-semibold text-slate-900">
                                        Notifikasi
                                    </p>
                                    <p class="mt-2 text-sm leading-relaxed text-slate-500">
                                        Belum ada notifikasi.
                                    </p>
                                </div>
                            </template>
                        </Dropdown>

                        <div class="relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-2.5 py-1.5 text-sm hover:bg-slate-50"
                                    >
                                        <span
                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-xs font-bold text-white"
                                        >
                                            {{
                                                user?.name
                                                    ?.charAt(0)
                                                    ?.toUpperCase()
                                            }}
                                        </span>
                                        <span
                                            class="hidden max-w-[140px] truncate text-left sm:block"
                                        >
                                            <span
                                                class="block font-semibold text-slate-800"
                                                >{{ user?.name }}</span
                                            >
                                            <span
                                                class="block text-xs text-slate-500"
                                                >{{ user?.email }}</span
                                            >
                                        </span>
                                    </button>
                                </template>

                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">
                                        Profil
                                    </DropdownLink>
                                    <DropdownLink
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                    >
                                        Keluar
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>

        <ConfirmModal />
    </div>
</template>
