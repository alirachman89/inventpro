<script setup>
defineProps({
    compact: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="flex items-center gap-3" :class="compact ? 'gap-2' : ''">
        <div
            class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 text-sm font-bold text-white shadow-soft"
            :class="compact ? 'h-9 w-9' : ''"
        >
            IP
        </div>
        <div v-if="!compact">
            <div class="text-base font-bold tracking-tight text-slate-900">
                InventPro
            </div>
            <div class="text-xs font-medium text-slate-500">
                Inventory Warehouse Platform
            </div>
        </div>
    </div>
</template>
