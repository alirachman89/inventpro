<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import GlobalSearch from '@/Components/GlobalSearch.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { useCan } from '@/composables/useCan';
import { useToast } from '@/composables/useToast';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const page = usePage();
const sidebarOpen = ref(false);
const { can } = useCan();
const toast = useToast();

const user = computed(() => page.props.auth.user);
const unreadCount = ref(page.props.notifications?.unread_count || 0);
const latestNotifications = ref(page.props.notifications?.latest || []);
let pollTimer = null;

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
            label: 'Persetujuan',
            href: route('approvals.index'),
            active: route().current('approvals.*'),
            show: true,
            icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        },
        {
            label: 'Uji Approval',
            href: route('approval-demos.index'),
            active: route().current('approval-demos.*'),
            show: can('approval_demos.manage'),
            icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z',
        },
        {
            label: 'Workflow Approval',
            href: route('admin.approval-workflows.index'),
            active: route().current('admin.approval-workflows.*'),
            show: can('approvals.manage'),
            icon: 'M4 6h16M4 10h16M4 14h10M4 18h6',
        },
        {
            label: 'Barang',
            href: route('admin.items.index'),
            active: route().current('admin.items.*') || route().current('admin.asset-units.*'),
            show: can('items.view'),
            icon: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        },
        {
            label: 'Vendor',
            href: route('admin.vendors.index'),
            active: route().current('admin.vendors.*'),
            show: can('vendors.view'),
            icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        },
        {
            label: 'Purchase Order',
            href: route('admin.purchase-orders.index'),
            active:
                route().current('admin.purchase-orders.*') ||
                route().current('admin.goods-receipts.*'),
            show: can('purchases.view'),
            icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        },
        {
            label: 'Mutasi Stok',
            href: route('admin.stock-movements.index'),
            active: route().current('admin.stock-movements.*'),
            show: can('stock_movements.view'),
            icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
        },
        {
            label: 'Stock Opname',
            href: route('admin.stock-opnames.index'),
            active: route().current('admin.stock-opnames.*'),
            show: can('stock_opnames.view'),
            icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
        },
        {
            label: 'Peminjaman',
            href: route('admin.borrows.index'),
            active: route().current('admin.borrows.*'),
            show: can('borrows.view'),
            icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
        },
        {
            label: 'Laporan',
            href: route('admin.reports.index'),
            active: route().current('admin.reports.*'),
            show: can('reports.view'),
            icon: 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        },
        {
            label: 'Client',
            href: route('admin.clients.index'),
            active: route().current('admin.clients.*'),
            show: can('clients.view'),
            icon: 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        },
        {
            label: 'Satuan (UOM)',
            href: route('admin.units.index'),
            active: route().current('admin.units.*'),
            show: can('units.view'),
            icon: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
        },
        {
            label: 'Kategori',
            href: route('admin.categories.index'),
            active: route().current('admin.categories.*'),
            show: can('categories.view'),
            icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
        },
        {
            label: 'Lokasi & Rak',
            href: route('admin.locations.index'),
            active: route().current('admin.locations.*') || route().current('admin.racks.*'),
            show: can('locations.view'),
            icon: 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z',
        },
        {
            label: 'Pengaturan',
            href: route('admin.settings.edit'),
            active: route().current('admin.settings.*'),
            show: can('settings.view'),
            icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
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

async function refreshNotifications() {
    try {
        const { data } = await axios.get(route('notifications.latest'));
        unreadCount.value = data.unread_count;
        latestNotifications.value = data.items;
    } catch {
        // ignore polling errors
    }
}

function openNotification(item) {
    router.post(route('notifications.read', item.id));
}

watch(
    () => page.url,
    () => {
        sidebarOpen.value = false;
    },
);

watch(
    () => page.props.notifications,
    (value) => {
        unreadCount.value = value?.unread_count || 0;
        latestNotifications.value = value?.latest || [];
    },
    { deep: true },
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

onMounted(() => {
    pollTimer = setInterval(refreshNotifications, 45000);
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
    }
});
</script>

<template>
    <div class="min-h-screen overflow-x-hidden">
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
        <div class="min-w-0 lg:pl-72">
            <header
                class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur"
            >
                <div
                    class="flex h-14 items-center gap-2 px-3 sm:h-16 sm:gap-3 sm:px-6"
                >
                    <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 lg:hidden"
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
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-semibold text-slate-900"
                            >
                                <slot name="header-title">InventPro</slot>
                            </p>
                            <p class="hidden truncate text-xs text-slate-500 sm:block">
                                <slot name="header-subtitle"
                                    >Platform inventory warehouse</slot
                                >
                            </p>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
                        <GlobalSearch />

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
                                    <span
                                        v-if="unreadCount > 0"
                                        class="absolute -right-1 -top-1 inline-flex min-w-5 items-center justify-center rounded-full bg-danger px-1 text-[10px] font-bold text-white"
                                    >
                                        {{ unreadCount > 9 ? '9+' : unreadCount }}
                                    </span>
                                </button>
                            </template>
                            <template #content>
                                <div class="border-b border-slate-100 px-4 py-3">
                                    <p class="text-sm font-semibold text-slate-900">
                                        Notifikasi
                                    </p>
                                </div>
                                <div
                                    v-if="latestNotifications.length"
                                    class="max-h-80 overflow-y-auto py-1"
                                >
                                    <button
                                        v-for="item in latestNotifications"
                                        :key="item.id"
                                        type="button"
                                        class="block w-full px-4 py-3 text-left hover:bg-slate-50"
                                        :class="
                                            item.is_unread
                                                ? 'bg-brand-50/50'
                                                : ''
                                        "
                                        @click="openNotification(item)"
                                    >
                                        <p
                                            class="text-sm font-medium text-slate-900"
                                        >
                                            {{ item.title }}
                                        </p>
                                        <p
                                            class="mt-0.5 line-clamp-2 text-xs text-slate-500"
                                        >
                                            {{ item.message }}
                                        </p>
                                    </button>
                                </div>
                                <div
                                    v-else
                                    class="px-4 py-4 text-sm text-slate-500"
                                >
                                    Belum ada notifikasi.
                                </div>
                                <div class="border-t border-slate-100 px-4 py-2">
                                    <Link
                                        :href="route('notifications.index')"
                                        class="text-sm font-medium text-brand-700 hover:text-brand-600"
                                    >
                                        Lihat semua
                                    </Link>
                                </div>
                            </template>
                        </Dropdown>

                        <div class="relative shrink-0">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button
                                        type="button"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white hover:bg-slate-50 sm:h-auto sm:w-auto sm:gap-2 sm:rounded-xl sm:px-2.5 sm:py-1.5"
                                        title="Profil & keluar"
                                        aria-label="Menu profil"
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
                                            class="hidden max-w-[140px] truncate text-left text-sm sm:block"
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
