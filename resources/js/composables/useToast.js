import { toast } from 'vue-sonner';

export function useToast() {
    return {
        success(message, description = undefined) {
            toast.success(message, { description });
        },
        error(message, description = undefined) {
            toast.error(message, { description });
        },
        warning(message, description = undefined) {
            toast.warning(message, { description });
        },
        info(message, description = undefined) {
            toast.info(message, { description });
        },
    };
}
