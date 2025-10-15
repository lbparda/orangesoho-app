<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

defineProps({
    offers: Object, // Viene paginado desde Laravel
});
</script>

<template>
    <Head title="Listado de Ofertas" />

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Ofertas Guardadas</h1>
                <Link :href="route('dashboard')">
                    <PrimaryButton>Atrás</PrimaryButton>
                </Link>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Oferta</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paquete</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creado Por</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Final</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Creación</th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Ver</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="offer in offers.data" :key="offer.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#{{ offer.id }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ offer.package.name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ offer.user?.name || 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ offer.summary.finalPrice }}€/mes</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ new Date(offer.created_at).toLocaleDateString() }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="route('offers.show', offer.id)" class="text-indigo-600 hover:text-indigo-900">Ver Detalles</Link>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <Link :href="route('offers.edit', offer.id)" class="text-indigo-600 hover:text-indigo-900">
                                        Editar
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="offers.data.length === 0">
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">No hay ofertas guardadas todavía.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>