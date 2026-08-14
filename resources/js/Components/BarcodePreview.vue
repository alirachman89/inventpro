<script setup>
import JsBarcode from 'jsbarcode';
import { onMounted, ref, watch } from 'vue';

const props = defineProps({
    value: {
        type: String,
        default: '',
    },
    height: {
        type: Number,
        default: 56,
    },
    displayValue: {
        type: Boolean,
        default: true,
    },
    /** Compact for inline forms */
    compact: {
        type: Boolean,
        default: false,
    },
});

const svgRef = ref(null);
const error = ref('');

const render = () => {
    error.value = '';
    if (!svgRef.value) {
        return;
    }

    const code = (props.value || '').trim();
    if (!code) {
        svgRef.value.innerHTML = '';
        return;
    }

    try {
        JsBarcode(svgRef.value, code, {
            format: 'CODE128',
            lineColor: '#0f172a',
            width: props.compact ? 1.2 : 1.6,
            height: props.height,
            displayValue: props.displayValue,
            fontSize: props.compact ? 12 : 14,
            margin: props.compact ? 4 : 8,
            background: '#ffffff',
        });
    } catch (e) {
        error.value = 'Nilai barcode tidak bisa digambar (CODE128).';
        svgRef.value.innerHTML = '';
    }
};

onMounted(render);
watch(() => [props.value, props.height, props.displayValue, props.compact], render);
</script>

<template>
    <div class="inline-block">
        <svg ref="svgRef" class="max-w-full" />
        <p v-if="error" class="mt-1 text-xs text-rose-600">{{ error }}</p>
    </div>
</template>
