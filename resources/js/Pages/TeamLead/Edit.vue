<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({ member: Object });

const form = useForm({
    commission_percentage: props.member.commission_percentage || 0,
});

const submit = () => {
    form.put(route('team-lead.users.update', props.member.id));
};
</script>

<template>
    <Head :title="`Asignar Comisión a ${member.name}`" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl">Asignar Comisión a {{ member.name }}</h2>
        </template>
        <div class="py-12">
            <div class="max-w-md mx-auto sm:px-6 lg:px-8 bg-white p-8 rounded-lg shadow-sm">
                <form @submit.prevent="submit">
                    <InputLabel for="commission" value="Porcentaje de Comisión (%)" />
                    <TextInput id="commission" v-model="form.commission_percentage" type="number" step="0.01" min="0" class="w-full" />
                    <InputError :message="form.errors.commission_percentage" />
                    <PrimaryButton class="mt-4" :disabled="form.processing">Guardar</PrimaryButton>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>