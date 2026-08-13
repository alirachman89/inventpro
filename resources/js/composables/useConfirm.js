import { reactive } from 'vue';

const state = reactive({
    open: false,
    title: '',
    message: '',
    confirmLabel: 'Ya, lanjutkan',
    cancelLabel: 'Batal',
    variant: 'danger',
    resolve: null,
});

export function useConfirm() {
    function confirm(options = {}) {
        state.open = true;
        state.title = options.title || 'Konfirmasi';
        state.message = options.message || 'Apakah Anda yakin?';
        state.confirmLabel = options.confirmLabel || 'Ya, lanjutkan';
        state.cancelLabel = options.cancelLabel || 'Batal';
        state.variant = options.variant || 'danger';

        return new Promise((resolve) => {
            state.resolve = resolve;
        });
    }

    function handleConfirm() {
        state.open = false;
        state.resolve?.(true);
        state.resolve = null;
    }

    function handleCancel() {
        state.open = false;
        state.resolve?.(false);
        state.resolve = null;
    }

    return {
        state,
        confirm,
        handleConfirm,
        handleCancel,
    };
}
