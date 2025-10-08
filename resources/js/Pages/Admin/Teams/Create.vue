<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
    name: '',
    commission_percentage: 0,
});

const submit = () => {
    form.post(route('admin.teams.store'));
};
</script>

<template>
    <Head title="Crear Equipo" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Crear Nuevo Equipo</h2>
        </template>
        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">
                    <form @submit.prevent="submit">
                        <div>
                            <InputLabel for="name" value="Nombre del Equipo" />
                            <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        <div class="mt-4">
                            <InputLabel for="commission_percentage" value="Porcentaje de Comisión (%)" />
                            <TextInput id="commission_percentage" v-model="form.commission_percentage" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" required />
                            <InputError class="mt-2" :message="form.errors.commission_percentage" />
                        </div>
                        <div class="flex items-center justify-end mt-6">
                            <PrimaryButton :disabled="form.processing">Guardar Equipo</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>