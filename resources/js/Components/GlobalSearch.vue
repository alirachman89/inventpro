<script setup>
import Modal from '@/Components/Modal.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const open = ref(false);
const q = ref('');
const loading = ref(false);
const results = ref([]);
const activeIndex = ref(0);
const inputRef = ref(null);
let debounceTimer = null;

const grouped = computed(() => {
    const map = {};
    for (const item of results.value) {
        if (!map[item.type_label]) map[item.type_label] = [];
        map[item.type_label].push(item);
    }
    return Object.entries(map).map(([label, items]) => ({ label, items }));
});

const flatResults = computed(() => results.value.filter((item) => item.href));

function openSearch() {
    open.value = true;
    nextTick(() => inputRef.value?.focus());
}

function closeSearch() {
    open.value = false;
    q.value = '';
    results.value = [];
    activeIndex.value = 0;
}

async function runSearch() {
    const term = q.value.trim();
    if (term.length < 2) {
        results.value = [];
        return;
    }

    loading.value = true;
    try {
        const { data } = await axios.get(route('search'), { params: { q: term } });
        results.value = data.results || [];
        activeIndex.value = 0;
    } catch {
        results.value = [];
    } finally {
        loading.value = false;
    }
}

watch(q, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(runSearch, 250);
});

function goTo(item) {
    if (!item?.href) return;
    closeSearch();
    router.visit(item.href);
}

function onKeydown(event) {
    const isMac = navigator.platform.toUpperCase().includes('MAC');
    const meta = isMac ? event.metaKey : event.ctrlKey;

    if (meta && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        if (open.value) {
            closeSearch();
        } else {
            openSearch();
        }
        return;
    }

    if (!open.value) return;

    if (event.key === 'Escape') {
        event.preventDefault();
        closeSearch();
        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        if (!flatResults.value.length) return;
        activeIndex.value = (activeIndex.value + 1) % flatResults.value.length;
        return;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        if (!flatResults.value.length) return;
        activeIndex.value =
            (activeIndex.value - 1 + flatResults.value.length) % flatResults.value.length;
        return;
    }

    if (event.key === 'Enter') {
        event.preventDefault();
        const item = flatResults.value[activeIndex.value];
        if (item) goTo(item);
    }
}

function isActive(item) {
    return flatResults.value[activeIndex.value] === item;
}

function setActive(item) {
    const index = flatResults.value.indexOf(item);
    if (index >= 0) activeIndex.value = index;
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => {
    window.removeEventListener('keydown', onKeydown);
    clearTimeout(debounceTimer);
});

defineExpose({ openSearch });
</script>

<template>
    <button
        type="button"
        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 sm:h-auto sm:w-auto sm:gap-2 sm:rounded-xl sm:px-3 sm:py-1.5 sm:text-sm sm:text-slate-500"
        title="Cari (Ctrl+K)"
        aria-label="Cari"
        @click="openSearch"
    >
        <svg class="h-5 w-5 sm:h-4 sm:w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"
            />
        </svg>
        <span class="hidden sm:inline">Cari…</span>
        <kbd
            class="hidden rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-semibold text-slate-500 md:inline"
        >
            Ctrl K
        </kbd>
    </button>

    <Modal :show="open" max-width="2xl" @close="closeSearch">
        <div class="border-b border-slate-100 p-4">
            <input
                ref="inputRef"
                v-model="q"
                type="search"
                placeholder="Cari SKU, asset tag, PO/GR/MOV/OPN/BRW, vendor, client, lokasi…"
                class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
            />
            <p class="mt-2 text-xs text-slate-500">
                Minimal 2 karakter · ↑↓ pilih · Enter buka · Esc tutup
            </p>
        </div>

        <div class="max-h-[60vh] overflow-y-auto p-2">
            <p v-if="loading" class="px-3 py-6 text-center text-sm text-slate-500">
                Mencari…
            </p>
            <p
                v-else-if="q.trim().length >= 2 && !results.length"
                class="px-3 py-6 text-center text-sm text-slate-500"
            >
                Tidak ada hasil untuk “{{ q.trim() }}”
            </p>
            <p
                v-else-if="q.trim().length < 2"
                class="px-3 py-6 text-center text-sm text-slate-500"
            >
                Ketik untuk mencari di seluruh modul yang Anda punya akses.
            </p>

            <div v-for="group in grouped" :key="group.label" class="mb-2">
                <p
                    class="px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-400"
                >
                    {{ group.label }}
                </p>
                <button
                    v-for="item in group.items"
                    :key="item.type + item.title + (item.href || '')"
                    type="button"
                    class="flex w-full flex-col rounded-xl px-3 py-2 text-left hover:bg-slate-50"
                    :class="{ 'bg-brand-50 ring-1 ring-brand-200': isActive(item) }"
                    @click="goTo(item)"
                    @mouseenter="setActive(item)"
                >
                    <span class="text-sm font-medium text-slate-900">{{ item.title }}</span>
                    <span v-if="item.subtitle" class="text-xs text-slate-500">
                        {{ item.subtitle }}
                    </span>
                </button>
            </div>
        </div>
    </Modal>
</template>
