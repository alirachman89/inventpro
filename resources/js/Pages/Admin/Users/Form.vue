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
    user: Object,
    roles: Array,
    selectedRoles: Array,
});

const isEdit = computed(() => !!props.user);

const form = useForm({
    name: props.user?.name || '',
    email: props.user?.email || '',
    password: '',
    password_confirmation: '',
    roles: [...(props.selectedRoles || [])],
});

function toggleRole(roleName) {
    if (form.roles.includes(roleName)) {
        form.roles = form.roles.filter((item) => item !== roleName);
    } else {
        form.roles.push(roleName);
    }
}

function submit() {
    if (isEdit.value) {
        form.put(route('admin.users.update', props.user.id));
    } else {
        form.post(route('admin.users.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Pengguna' : 'Tambah Pengguna'" />

    <AuthenticatedLayout>
        <template #header-title>{{
            isEdit ? 'Edit Pengguna' : 'Tambah Pengguna'
        }}</template>
        <template #header-subtitle>Form pengelolaan akun</template>

        <form class="mx-auto max-w-3xl space-y-6" @submit.prevent="submit">
            <div class="surface-card space-y-5 p-6">
                <div>
                    <InputLabel for="name" value="Nama" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <div>
                    <InputLabel for="email" value="Email" />
                    <TextInput
                        id="email"
                        v-model="form.email"
                        type="email"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <InputLabel
                            for="password"
                            :value="
                                isEdit
                                    ? 'Password baru (opsional)'
                                    : 'Password'
                            "
                        />
                        <TextInput
                            id="password"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full"
                            :required="!isEdit"
                            autocomplete="new-password"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>
                    <div>
                        <InputLabel
                            for="password_confirmation"
                            value="Konfirmasi password"
                        />
                        <TextInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            :required="!isEdit"
                            autocomplete="new-password"
                        />
                    </div>
                </div>

                <div>
                    <InputLabel value="Role" />
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        <label
                            v-for="role in roles"
                            :key="role.id"
                            class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm"
                        >
                            <input
                                type="checkbox"
                                class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                :checked="form.roles.includes(role.name)"
                                @change="toggleRole(role.name)"
                            />
                            <span class="font-medium text-slate-700">{{
                                role.name
                            }}</span>
                        </label>
                    </div>
                    <InputError class="mt-2" :message="form.errors.roles" />
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link :href="route('admin.users.index')">
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">
                    {{ isEdit ? 'Simpan Perubahan' : 'Simpan' }}
                </PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
