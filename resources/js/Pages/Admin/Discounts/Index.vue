<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    discounts: Array,
});

const formatPercentage = (value) => {
    if (value == null || isNaN(value)) return 'N/A';
    try {
        return `${parseFloat(value)}%`;
    } catch { return 'Error %'; }
};
</script>

<template>
    <Head title="Gestionar Descuentos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">Gestionar Descuentos</h1>
            </div>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
            <div v-if="$page.props.flash.success" class="p-4 mb-4 bg-green-100 border border-green-300 text-green-800 rounded-md shadow-sm">
                {{ $page.props.flash.success }}
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200 overflow-x-auto">
                        
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <!-- INICIO: CAMBIOS -->
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Porcentaje (%)</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Duración (Meses)</th>
                                    <!-- FIN: CAMBIOS -->
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="discount in discounts" :key="discount.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#{{ discount.id }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ discount.name }}</td>
                                    <!-- INICIO: CAMBIOS -->
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ formatPercentage(discount.percentage) }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ discount.duration_months }}</td>
                                    <!-- FIN: CAMBIOS -->
                                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2">
                                        <Link :href="route('admin.discounts.edit', discount.id)" class="text-indigo-600 hover:text-indigo-800">Editar</Link>
                                    </td>
                                </tr>
                                <tr v-if="!discounts || discounts.length === 0">
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500 italic">
                                        No se encontraron descuentos.
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>