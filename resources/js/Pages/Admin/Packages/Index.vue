<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
    packages: Array, // Recibimos un array simple, no un objeto paginado
});

/**
 * Formatea un número como moneda EUR.
 */
const formatCurrency = (value) => {
    if (value == null || isNaN(value)) return 'N/A';
    try {
        return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);
    } catch { return 'Error €'; }
};
</script>

<template>
    <Head title="Gestionar Paquetes" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">Gestionar Paquetes</h1>
                <!-- 
                    Opcional: Si en el futuro creas la ruta 'admin.packages.create', 
                    puedes añadir aquí el botón de "Crear Paquete".
                -->
            </div>
        </template>

        <!-- Mensaje de éxito al guardar -->
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
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Base (€)</th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="pkg in packages" :key="pkg.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#{{ pkg.id }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ pkg.name }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ formatCurrency(pkg.base_price) }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2">
                                        <Link :href="route('admin.packages.edit', pkg.id)" class="text-indigo-600 hover:text-indigo-800">Editar</Link>
                                    </td>
                                </tr>
                                <tr v-if="!packages || packages.length === 0">
                                    <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500 italic">
                                        No se encontraron paquetes.
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

