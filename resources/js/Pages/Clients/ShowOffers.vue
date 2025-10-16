<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
// Aquí podrías añadir un componente de paginación si lo tienes
// import Pagination from '@/Components/Pagination.vue'; 

defineProps({
    client: Object,
    offers: Object, // offers es un objeto de paginación
});
</script>

<template>
    <Head :title="`Ofertas de ${client.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Ofertas de: {{ client.name }}
                </h2>
                <Link :href="route('clients.index')" class="text-sm text-blue-600 hover:underline">
                    &larr; Volver a Clientes
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paquete</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Final</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Acciones</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="offer in offers.data" :key="offer.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ offer.id }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ offer.package.name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ offer.summary.finalPrice }}€</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ new Date(offer.created_at).toLocaleDateString() }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link :href="route('offers.show', offer.id)" class="text-indigo-600 hover:text-indigo-900">Ver Detalles</Link>
                                    </td>
                                </tr>
                                <tr v-if="offers.data.length === 0">
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">Este cliente no tiene ofertas.</td>
                                </tr>
                            </tbody>
                        </table>

                        </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>