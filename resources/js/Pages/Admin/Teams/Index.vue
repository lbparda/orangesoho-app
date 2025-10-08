<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    teams: Object,
});
</script>

<template>
    <Head title="Gestión de Equipos" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Administración de Equipos</h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-end mb-6">
                    <Link :href="route('admin.teams.create')">
                        <PrimaryButton>Crear Equipo</PrimaryButton>
                    </Link>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium">Nombre del Equipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium">Comisión</th>
                                <th class="px-6 py-3 text-left text-xs font-medium">Nº de Miembros</th>
                                <th class="relative px-6 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="team in teams.data" :key="team.id">
                                <td class="px-6 py-4">{{ team.name }}</td>
                                <td class="px-6 py-4">{{ team.commission_percentage }}%</td>
                                <td class="px-6 py-4">{{ team.users_count }}</td>
                                <td class="px-6 py-4 text-right">
                                    <Link :href="route('admin.teams.edit', team.id)" class="text-indigo-600">Editar</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>