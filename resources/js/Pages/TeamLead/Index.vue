<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ teamMembers: Array });
</script>

<template>
    <Head title="Gestionar Mi Equipo" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestionar Mi Equipo</h2>
        </template>
        <div class="flex justify-end mb-4">
         <Link :href="route('team-lead.users.create')">
            <PrimaryButton>Crear Usuario</PrimaryButton>
         </Link>
        </div>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Comisión Asignada</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="member in teamMembers" :key="member.id">
                                <td>{{ member.name }}</td>
                                <td>{{ member.email }}</td>
                                <td>{{ member.commission_percentage || '0' }}%</td>
                                <td>
                                    <Link :href="route('team-lead.users.edit', member.id)" class="text-indigo-600">
                                        Asignar Comisión
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>