<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import OfferForm from './Partials/OfferForm.vue';
import PymeOfferForm from './Partials/PymeOfferForm.vue';
import { ref } from 'vue';

const props = defineProps({
    // Props Comunes
    clients: Array,
    auth: Object,
    initialClientId: [Number, String, null],
    probabilityOptions: Array,
    
    // Props SOHO (Algunas compartidas)
    packages: Array,
    allAddons: Array,
    discounts: Array,
    operators: Array,
    portabilityCommission: Number,
    additionalInternetAddons: Array,
    centralitaExtensions: Array,
    portabilityExceptions: Array,
    fiberFeatures: Array,

    // Props PYME
    pymePackages: Array,
    pymeO2oDiscounts: Array,
    // --- NUEVAS PROPS RECIBIDAS DEL CONTROLADOR PYME ---
    centralitaMobileAddons: Array,
    centralitaFeatures: Array,
    
    // Prop para forzar el modo inicial (viene del PymeOfferController)
    initialMode: { type: String, default: null }
});

// Estado del selector
const offerType = ref(props.initialMode);

const selectType = (type) => {
    if (type === 'pyme') {
        // Si estamos en modo PYME y ya tenemos el modo inicial, solo cambiamos el estado local
        alert('El segmento PYME estará disponible próximamente.');
        /*
        if (props.initialMode === 'pyme') {
             offerType.value = 'pyme';
        } else {
             // Si estamos en la ruta base y seleccionamos PYME, redirigimos al controlador de PYME
             window.location.href = route('pyme.offers.create');
        }
             */
    } else {
        // Para SOHO, nos quedamos en la vista actual
        offerType.value = type;
    }
};

// Resetear selección
const resetSelection = () => {
    if (props.initialMode === 'pyme') {
        window.location.href = route('offers.create'); 
    } else {
        offerType.value = null;
    }
};
</script>

<template>
    <Head title="Crear Oferta" />
    <AuthenticatedLayout>
        
        <!-- 1. SELECTOR (Solo si no hay tipo seleccionado) -->
        <div v-if="!offerType" class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-10">
                    <div class="text-center mb-10">
                        <h2 class="text-3xl font-bold text-gray-900">Nueva Oferta Comercial</h2>
                        <p class="mt-2 text-gray-600">Selecciona el segmento del cliente.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
                        <!-- Botón SOHO -->
                        <button @click="selectType('soho')" class="group relative flex flex-col items-center p-8 bg-white border-2 border-gray-200 rounded-2xl hover:border-orange-500 hover:shadow-xl transition-all duration-300 text-center">
                            <div class="h-20 w-20 bg-orange-100 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 group-hover:text-orange-600 transition-colors">SOHO / Autónomos</h3>
                            <div class="mt-6 px-4 py-2 bg-orange-50 text-orange-700 rounded-full text-sm font-semibold group-hover:bg-orange-600 group-hover:text-white transition-colors">Crear Oferta SOHO &rarr;</div>
                        </button>

                        <!-- Botón PYME -->
                        <button @click="selectType('pyme')" class="group relative flex flex-col items-center p-8 bg-white border-2 border-gray-200 rounded-2xl hover:border-blue-600 hover:shadow-xl transition-all duration-300 text-center">
                            <div class="h-20 w-20 bg-blue-100 rounded-full flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            </div>
                            <h3 class="text-2xl font-bold text-gray-900 group-hover:text-blue-600 transition-colors">PYME / Empresas</h3>
                            <div class="mt-6 px-4 py-2 bg-blue-50 text-blue-700 rounded-full text-sm font-semibold group-hover:bg-blue-600 group-hover:text-white transition-colors">Crear Oferta PYME &rarr;</div>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. FORMULARIO PYME (CORREGIDO) -->
        <PymeOfferForm 
            v-else-if="offerType === 'pyme'" 
            :clients="clients"
            :packages="pymePackages" 
            :discounts="pymeO2oDiscounts"
            :operators="['Movistar', 'Vodafone', 'Orange', 'Yoigo', 'Otros']"
            :auth="auth"
            :initialClientId="initialClientId"
            :probabilityOptions="[0, 25, 50, 75, 90, 100]"
            
            :allAddons="allAddons" 
            :centralitaMobileAddons="centralitaMobileAddons"
            :centralitaExtensions="centralitaExtensions"
            :centralitaFeatures="centralitaFeatures"
            :fiberFeatures="fiberFeatures"
            
            :portabilityCommission="0"
            :additionalInternetAddons="[]"
            :portabilityExceptions="[]"
            @update:offerType="resetSelection" 
        />

        <!-- 3. FORMULARIO SOHO -->
        <OfferForm 
            v-else 
            v-bind="props" 
        />

    </AuthenticatedLayout>
</template>