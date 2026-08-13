<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    workflow: Object,
    roles: Array,
});

const form = useForm({
    name: props.workflow.name,
    is_active: props.workflow.is_active,
    steps:
        props.workflow.steps?.length > 0
            ? props.workflow.steps.map((step) => ({ ...step }))
            : [
                  {
                      name: 'Langkah 1',
                      approver_role: props.roles[0] || 'approver',
                      mode: 'any',
                  },
              ],
});

function addStep() {
    form.steps.push({
        name: `Langkah ${form.steps.length + 1}`,
        approver_role: props.roles[0] || 'approver',
        mode: 'any',
    });
}

function removeStep(index) {
    if (form.steps.length === 1) {
        return;
    }
    form.steps.splice(index, 1);
}

function submit() {
    form.put(route('admin.approval-workflows.update', props.workflow.id));
}
</script>

<template>
    <Head title="Atur Workflow" />

    <AuthenticatedLayout>
        <template #header-title>Atur Workflow</template>
        <template #header-subtitle>{{ workflow.document_type }}</template>

        <form class="mx-auto max-w-3xl space-y-6" @submit.prevent="submit">
            <div class="surface-card space-y-5 p-6">
                <div>
                    <InputLabel for="name" value="Nama workflow" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.name" />
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input
                        v-model="form.is_active"
                        type="checkbox"
                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                    />
                    Workflow aktif
                </label>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <InputLabel value="Langkah approval" />
                        <button
                            type="button"
                            class="text-sm font-medium text-brand-700"
                            @click="addStep"
                        >
                            Tambah langkah
                        </button>
                    </div>

                    <div
                        v-for="(step, index) in form.steps"
                        :key="index"
                        class="rounded-xl border border-slate-200 p-4"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-semibold text-slate-800">
                                Langkah {{ index + 1 }}
                            </p>
                            <button
                                type="button"
                                class="text-xs text-danger"
                                @click="removeStep(index)"
                            >
                                Hapus
                            </button>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="sm:col-span-3">
                                <InputLabel value="Nama langkah" />
                                <TextInput
                                    v-model="step.name"
                                    class="mt-1 block w-full"
                                    required
                                />
                            </div>
                            <div>
                                <InputLabel value="Role approver" />
                                <select
                                    v-model="step.approver_role"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm"
                                >
                                    <option
                                        v-for="role in roles"
                                        :key="role"
                                        :value="role"
                                    >
                                        {{ role }}
                                    </option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Mode" />
                                <select
                                    v-model="step.mode"
                                    class="mt-1 block w-full rounded-lg border-slate-300 text-sm"
                                >
                                    <option value="any">Any (salah satu)</option>
                                    <option value="all">All (semua)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <InputError :message="form.errors.steps" />
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <Link :href="route('admin.approval-workflows.index')">
                    <SecondaryButton type="button">Batal</SecondaryButton>
                </Link>
                <PrimaryButton :disabled="form.processing">Simpan</PrimaryButton>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
