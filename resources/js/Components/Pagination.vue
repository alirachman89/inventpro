<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div v-if="links.length > 3" class="flex flex-wrap gap-1">
        <template v-for="(link, index) in links" :key="index">
            <Link
                v-if="link.url"
                :href="link.url"
                class="rounded-lg px-3 py-1.5 text-sm"
                :class="
                    link.active
                        ? 'bg-brand-600 text-white'
                        : 'border border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                "
                v-html="link.label"
            />
            <span
                v-else
                class="rounded-lg px-3 py-1.5 text-sm text-slate-400"
                v-html="link.label"
            />
        </template>
    </div>
</template>
