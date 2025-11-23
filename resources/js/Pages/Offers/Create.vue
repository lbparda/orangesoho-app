<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import OfferForm from './Partials/OfferForm.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref } from 'vue';

// Recibimos todas las props del controlador para pasarlas al OfferForm si es SOHO
const props = defineProps({
    clients: Array,
    packages: Array,
    allAddons: Array,
    discounts: Array,
    operators: Array,
    portabilityCommission: Number,
    additionalInternetAddons: Array,
    centralitaExtensions: Array,
    auth: Object,
    initialClientId: [Number, String, null],
    probabilityOptions: Array,
    portabilityExceptions: Array,
    fiberFeatures: Array,
});

// Estado para controlar la selección del tipo de oferta
// null = no seleccionado, 'soho' = formulario normal, 'pyme' = pantalla construcción
const offerType = ref(null);

const selectType = (type) => {
    offerType.value = type;
};
</script>

<template>
    <Head title="Crear Oferta" />
    <AuthenticatedLayout>
        
        <div v-if="!offerType" class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-10">
                    <div class="text-center mb-10">
                        <h2 class="text-3xl font-bold text-gray-900">Nueva Oferta Comercial</h2>
                        <p class="mt-2 text-gray-600">Selecciona el segmento del cliente para configurar la oferta adecuada.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                        <button 
                            @click="selectType('soho')"
                            class="group relative flex flex-col items-center p-8 bg-white border-2 border-gray-200 rounded-2xl hover:border-orange-500 hover:shadow-xl transition-all duration-300 text-center"
                        >
                            <div class="h-20 w-20 bg-orange-100 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 group-hover:text-orange-600 transition-colors">SOHO / Autónomos</h3>
                            <p class="text-gray-500 mt-3">Para autónomos, comercios y pequeñas empresas.</p>
                            <div class="mt-6 px-4 py-2 bg-orange-50 text-orange-700 rounded-full text-sm font-semibold group-hover:bg-orange-600 group-hover:text-white transition-colors">
                                Crear Oferta SOHO &rarr;
                            </div>
                        </button>

                        <button 
                            @click="selectType('pyme')"
                            class="group relative flex flex-col items-center p-8 bg-white border-2 border-gray-200 rounded-2xl hover:border-blue-600 hover:shadow-xl transition-all duration-300 text-center"
                        >
                            <div class="h-20 w-20 bg-blue-100 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors">PYME / Empresas</h3>
                            <p class="text-gray-500 mt-3">Soluciones a medida para medianas y grandes empresas.</p>
                            <div class="mt-6 px-4 py-2 bg-blue-50 text-blue-700 rounded-full text-sm font-semibold group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                Crear Oferta PYME &rarr;
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="offerType === 'pyme'" class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-16 text-center">
                    <div class="mx-auto h-32 w-32 bg-gray-100 rounded-full flex items-center justify-center mb-6 animate-pulse">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                        </svg>
                    </div>
                    <h2 class="text-4xl font-extrabold text-gray-900 mb-4">Módulo PYME</h2>
                    <p class="text-xl text-gray-500 mb-8 max-w-2xl mx-auto">
                        Estamos trabajando en esta funcionalidad. Muy pronto podrás gestionar ofertas complejas y grandes flotas desde aquí.
                    </p>
                    <SecondaryButton @click="offerType = null" class="px-6 py-3 text-lg">
                        &larr; Volver a seleccionar
                    </SecondaryButton>
                </div>
            </div>
        </div>

        <OfferForm v-else v-bind="props" />

    </AuthenticatedLayout>
</template>