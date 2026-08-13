import { usePage } from '@inertiajs/vue3';

export function useCan() {
    const page = usePage();

    function can(permission) {
        const user = page.props.auth?.user;
        if (!user) {
            return false;
        }

        if (user.is_superadmin) {
            return true;
        }

        return (user.permissions || []).includes(permission);
    }

    function canAny(permissions = []) {
        return permissions.some((permission) => can(permission));
    }

    return { can, canAny };
}
