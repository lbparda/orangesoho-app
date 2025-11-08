<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { ref } from 'vue';

defineProps({
    discounts: Array,
});

// --- Lógica de Eliminación ---
const confirmingDeletion = ref(false);
const discountToDelete = ref(null);
const deleteForm = useForm({});

const confirmDeletion = (discount) => {
    discountToDelete.value = discount;
    confirmingDeletion.value = true;
};

const closeModal = () => {
    confirmingDeletion.value = false;
    discountToDelete.value = null;
};

const deleteDiscount = () => {
    if (!discountToDelete.value) return;
    deleteForm.delete(route('admin.discounts.destroy', discountToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => closeModal(),
    });
};
// --- Fin Lógica de Eliminación ---

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
            <div class="flex flex-wrap justify-between items-center gap-4">
                <h1 class="text-2xl font-bold text-gray-800">Gestionar Descuentos</h1>
                
                <div class="flex flex-wrap items-center space-x-2">
                    
                    <Link :href="route('admin.discounts.importCsv')">
                        <SecondaryButton>Importar CSV</SecondaryButton>
                    </Link>

                    <a :href="route('admin.discounts.exportCsv')"
                       class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        Exportar a CSV
                    </a>

                    <a :href="route('admin.discounts.generateSeeder')"
                       class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-500 active:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Generar Seeder (TXT)
                    </a>
                    
                    <Link :href="route('admin.discounts.create')">
                        <PrimaryButton>Crear Descuento</PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
             <div v-if="$page.props.flash.success" class="p-4 mb-4 bg-green-100 border border-green-300 text-green-800 rounded-md shadow-sm">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash.error" class="p-4 mb-4 bg-red-100 border border-red-300 text-red-800 rounded-md shadow-sm">
                {{ $page.props.flash.error }}
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Porcentaje (%)</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Duración (Meses)</th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="discount in discounts" :key="discount.id" class="hover:bg-gray-50">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ discount.name }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ formatPercentage(discount.percentage) }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ discount.duration_months }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2">
                                        <Link :href="route('admin.discounts.edit', discount.id)" class="text-indigo-600 hover:text-indigo-800">
                                            Editar
                                        </Link>
                                        <button @click="confirmDeletion(discount)" class="text-red-600 hover:text-red-800">
                                            Eliminar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="discounts.length === 0">
                                    <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500 italic">
                                        No hay descuentos creados.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <ConfirmationModal :show="confirmingDeletion" @close="closeModal">
            <template #title>
                Eliminar Descuento
            </template>
            <template #content>
                ¿Estás seguro de que quieres eliminar el descuento "{{ discountToDelete?.name }}"? Esta acción no se puede deshacer.
            </template>
            <template #footer>
                <SecondaryButton @click="closeModal">
                    Cancelar
                </SecondaryButton>
                <DangerButton
                    class="ms-3"
                    :class="{ 'opacity-25': deleteForm.processing }"
                    :disabled="deleteForm.processing"
                    @click="deleteDiscount"
                >
                    Eliminar
                </DangerButton>
            </template>
        </ConfirmationModal>

    </AuthenticatedLayout>
</template>