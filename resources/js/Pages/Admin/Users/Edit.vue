<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    user: Object, // El usuario a editar
    teams: Array, // La lista de equipos
});

// Precargamos el formulario con los datos del usuario
const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    team_id: props.user.team_id,
    role: props.user.role,
});

const submit = () => {
    // Usamos el método PUT para actualizar
    form.put(route('admin.users.update', props.user.id), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Editar Usuario" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Usuario: {{ form.name }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">
                    <form @submit.prevent="submit">
                        <div>
                            <InputLabel for="name" value="Nombre" />
                            <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="email" value="Correo Electrónico" />
                            <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>
                        
                        <div class="mt-4">
                            <InputLabel for="password" value="Nueva Contraseña (opcional)" />
                            <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="password_confirmation" value="Confirmar Nueva Contraseña" />
                            <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="form.password_confirmation" />
                            <InputError class="mt-2" :message="form.errors.password_confirmation" />
                        </div>
                        
                        <div class="mt-4">
                            <InputLabel for="team_id" value="Equipo" />
                            <select id="team_id" v-model="form.team_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option :value="null" disabled>-- Selecciona un equipo --</option>
                                <option v-for="team in teams" :key="team.id" :value="team.id">
                                    {{ team.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.team_id" />
                        </div>

                        <div class="mt-4">
                            <InputLabel for="role" value="Rol del Usuario" />
                            <select id="role" v-model="form.role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                <option value="user">Usuario</option>
                                <option value="team_lead">Jefe de Equipo</option>
                                <option value="admin">Administrador</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.role" />
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <Link :href="route('admin.users.index')" class="text-sm text-gray-600 hover:text-gray-900 rounded-md">
                                Cancelar
                            </Link>
                            <PrimaryButton class="ms-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Actualizar Usuario
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>