<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    role: Object,
    permissionGroups: Object,
    selectedPermissions: Array,
});

const isEdit = computed(() => !!props.role);
const isSuperadminRole = computed(() => !!props.role?.is_superadmin);

const form = useForm({
    name: props.role?.name || '',
    permissions: [...(props.selectedPermissions || [])],
});

function togglePermission(name) {
    if (form.permissions.includes(name)) {
        form.permissions = form.permissions.filter((item) => item !== name);
    } else {
        form.permissions.push(name);
    }
}

function toggleGroup(permissions) {
    const allSelected = permissions.every((item) =>
        form.permissions.includes(item),
    );

    if (allSelected) {
        form.permissions = form.permissions.filter(
            (item) => !permissions.includes(item),
        );
    } else {
        form.permissions = Array.from(
            new Set([...form.permissions, ...permissions]),
        );
    }
}

function submit() {
    if (isEdit.value) {
        form.put(route('admin.roles.update', props.role.id));
    } else {
        form.post(route('admin.roles.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Role' : 'Tambah Role'" />

    <AuthenticatedLayout>
        <template #header-title>{{
            isEdit ? 'Edit Role' : 'Tambah Role'
        }}</template>
        <template #header-subtitle
            >Atur nama role dan permission</template
        >

        <form class="mx-auto max-w-4xl space-y-6" @submit.prevent="submit">
            <div class="surface-card space-y-5 p-6">
                <div>
                    <InputLabel for="name" value="Nama role" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        class="mt-1 block w-full"
                        :disabled="isSuperadminRole"
                        required
                    />
                    <p
                        v-if="isSuperadminRole"
                        class="mt-2 text-xs text-slate-500"
                    >
                        Nama role superadmin tidak dapat diubah.
                    </p>
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div class="space-y-4">
                    <InputLabel value="Permission matrix" />
                    <div
                        v-for="(permissions, group) in permissionGroups"
                        :key="group"
                        class="rounded-xl border border-slate-200 p-4"
                    >
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h3
                                class="text-sm font-semibold capitalize text-slate-800"
                            >
                                {{ group }}
                            </h3>
                            <button
                                type="button"
                                class="text-xs font-medium text-brand-700 hover:text-brand-600"
                                @click="toggleGroup(permissions)"
                            >
                                Pilih semua
                            </button>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            <label
                                v-for="permission in permissions"
                                :key="permission"
                                class="flex items-center gap-2 text-sm text-slate-700"
                            >
                                <input
                                    type="checkbox"
                                    class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                    :checked="
                                        form.permissions.includes(permission)
                                    "
                                    @change="togglePermission(permission)"
                                />
                                <span>{{ permission }}</span>
                            </label>
                        </div>
                    </div>
                    <InputError :message="form.errors.permissions" />
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link :href="route('admin.roles.index')">
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">
                    {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
