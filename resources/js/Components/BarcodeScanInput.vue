<script setup>
import axios from 'axios';
import { nextTick, ref } from 'vue';

const props = defineProps({
    autofocus: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: 'Scan barcode / SKU lalu Enter…',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    /**
     * item  → GET admin.items.lookup
     * asset → GET admin.asset-units.lookup
     * custom → gunakan resolveFn
     */
    mode: {
        type: String,
        default: 'item',
        validator: (value) => ['item', 'asset', 'custom'].includes(value),
    },
    availableOnly: {
        type: Boolean,
        default: false,
    },
    /**
     * async (code) => ({ label: string, data: any } | null)
     */
    resolveFn: {
        type: Function,
        default: null,
    },
});

const emit = defineEmits(['resolved', 'not-found', 'error']);

const code = ref('');
const busy = ref(false);
const message = ref('');
const messageTone = ref('slate');
const inputRef = ref(null);

const focus = async () => {
    await nextTick();
    inputRef.value?.focus();
};

const setMessage = (text, tone = 'slate') => {
    message.value = text;
    messageTone.value = tone;
};

const resolveViaApi = async (term) => {
    if (props.mode === 'asset') {
        const { data } = await axios.get(route('admin.asset-units.lookup'), {
            params: {
                code: term,
                available_only: props.availableOnly ? 1 : undefined,
            },
        });

        if (!data.found || !data.asset) {
            return null;
        }

        return {
            label: `${data.asset.asset_tag} — ${data.asset.item?.sku || ''}`,
            data: data.asset,
        };
    }

    const { data } = await axios.get(route('admin.items.lookup'), {
        params: { code: term },
    });

    if (!data.found || !data.item) {
        return null;
    }

    if (!data.item.is_active) {
        return { inactive: true, label: data.item.sku, data: data.item };
    }

    return {
        label: `${data.item.sku} — ${data.item.name}`,
        data: data.item,
    };
};

const scan = async () => {
    const term = code.value.trim();
    if (!term || busy.value || props.disabled) {
        return;
    }

    busy.value = true;
    setMessage('Mencari…', 'slate');

    try {
        let result = null;

        if (props.mode === 'custom' && typeof props.resolveFn === 'function') {
            result = await props.resolveFn(term);
        } else {
            result = await resolveViaApi(term);
        }

        if (!result) {
            setMessage('Tidak ditemukan.', 'rose');
            emit('not-found', term);
            return;
        }

        if (result.inactive) {
            setMessage(`${result.label} nonaktif — tidak ditambahkan.`, 'amber');
            emit('not-found', term);
            return;
        }

        setMessage(`OK: ${result.label}`, 'emerald');
        emit('resolved', result.data, term);
        code.value = '';
    } catch (err) {
        if (err.response?.status === 404) {
            setMessage('Kode tidak ditemukan.', 'rose');
            emit('not-found', term);
        } else {
            setMessage('Gagal lookup. Coba lagi.', 'rose');
            emit('error', err);
        }
    } finally {
        busy.value = false;
        focus();
    }
};

defineExpose({ focus });

if (props.autofocus) {
    focus();
}
</script>

<template>
    <div class="space-y-1.5">
        <div class="flex gap-2">
            <input
                ref="inputRef"
                v-model="code"
                type="text"
                autocomplete="off"
                :disabled="disabled || busy"
                :placeholder="placeholder"
                class="w-full rounded-xl border-slate-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-50"
                @keydown.enter.prevent="scan"
            />
            <button
                type="button"
                class="btn-secondary shrink-0"
                :disabled="disabled || busy || !code.trim()"
                @click="scan"
            >
                Scan
            </button>
        </div>
        <p
            v-if="message"
            class="text-xs"
            :class="{
                'text-slate-500': messageTone === 'slate',
                'text-rose-600': messageTone === 'rose',
                'text-amber-700': messageTone === 'amber',
                'text-emerald-700': messageTone === 'emerald',
            }"
        >
            {{ message }}
        </p>
    </div>
</template>
