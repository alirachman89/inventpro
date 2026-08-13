<script setup>
import Modal from '@/Components/Modal.vue';
import { useConfirm } from '@/composables/useConfirm';

const { state, handleConfirm, handleCancel } = useConfirm();
</script>

<template>
    <Modal :show="state.open" max-width="md" @close="handleCancel">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-slate-900">
                {{ state.title }}
            </h3>
            <p class="mt-2 text-sm leading-relaxed text-slate-600">
                {{ state.message }}
            </p>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <button type="button" class="btn-secondary" @click="handleCancel">
                    {{ state.cancelLabel }}
                </button>
                <button
                    type="button"
                    :class="
                        state.variant === 'warning' ? 'btn-warning' : 'btn-danger'
                    "
                    @click="handleConfirm"
                >
                    {{ state.confirmLabel }}
                </button>
            </div>
        </div>
    </Modal>
</template>
