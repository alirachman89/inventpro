<script setup>
defineProps({
    type: {
        type: String,
        default: 'info',
        validator: (value) =>
            ['info', 'success', 'warning', 'danger'].includes(value),
    },
    title: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div
        class="rounded-xl border px-4 py-3 text-sm"
        :class="{
            'border-sky-200 bg-info-soft text-sky-900': type === 'info',
            'border-emerald-200 bg-success-soft text-emerald-900':
                type === 'success',
            'border-amber-200 bg-warning-soft text-amber-900':
                type === 'warning',
            'border-red-200 bg-danger-soft text-red-900': type === 'danger',
        }"
        role="alert"
    >
        <p v-if="title" class="font-semibold">{{ title }}</p>
        <div :class="title ? 'mt-1' : ''">
            <slot />
        </div>
    </div>
</template>
