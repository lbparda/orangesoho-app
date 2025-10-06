<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    offer: Object,
});

// Computed properties para categorizar addons y limpiar la vista
const mainFiber = computed(() => props.offer.addons.find(a => a.type === 'internet'));
const additionalFibers = computed(() => props.offer.addons.filter(a => a.type === 'internet_additional'));
const centralitaService = computed(() => props.offer.addons.find(a => a.type === 'centralita'));
const centralitaFeature = computed(() => props.offer.addons.find(a => a.type === 'centralita_feature'));
const centralitaExtensions = computed(() => props.offer.addons.filter(a => a.type === 'centralita_extension'));

</script>

<template>
    <Head :title="`Detalle Oferta #${offer.id}`" />

    <div class="py-12 bg-gray-50">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-start mb-6">
                <Link :href="route('offers.index')" class="text-indigo-600 hover:text-indigo-900 font-medium">&larr; Volver al listado</Link>
            </div>
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 bg-white border-b border-gray-200 space-y-10">
                    
                    <!-- Resumen General -->
                    <div class="pb-6 border-b">
                        <h1 class="text-3xl font-bold text-gray-900">Resumen de la Oferta #{{ offer.id }}</h1>
                        <p class="mt-1 text-sm text-gray-500">Creada el {{ new Date(offer.created_at).toLocaleString() }}</p>
                        
                        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
                            <div class="p-4 bg-gray-100 rounded-lg">
                                <span class="text-sm font-medium text-gray-500 uppercase tracking-wider">Paquete</span>
                                <p class="text-xl font-semibold text-gray-900 mt-1">{{ offer.package.name }}</p>
                            </div>
                            <div class="p-4 bg-emerald-100 rounded-lg">
                                <span class="text-sm font-medium text-emerald-600 uppercase tracking-wider">Comisión Total</span>
                                <p class="text-xl font-semibold text-emerald-800 mt-1">{{ offer.summary.totalCommission }}€</p>
                            </div>
                             <div class="p-4 bg-indigo-100 rounded-lg">
                                <span class="text-sm font-medium text-indigo-600 uppercase tracking-wider">Precio Final</span>
                                <p class="text-xl font-semibold text-indigo-800 mt-1">{{ offer.summary.finalPrice }}€/mes</p>
                            </div>
                        </div>
                    </div>

                    <!-- Líneas Móviles -->
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-800">Líneas Móviles</h2>
                        <ul class="mt-4 space-y-4">
                            <li v-for="(line, index) in offer.lines" :key="line.id" class="p-4 border rounded-lg bg-gray-50">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ line.is_extra ? `Línea Adicional ${index + 1}` : 'Línea Principal' }}</p>
                                        <p class="text-sm text-gray-600">{{ line.phone_number || 'Número no especificado' }}</p>
                                    </div>
                                    <span v-if="line.is_portability" class="px-2 py-1 text-xs font-medium text-blue-800 bg-blue-100 rounded-full">Portabilidad</span>
                                </div>
                                <div class="mt-3 space-y-2 text-sm text-gray-700">
                                    <p v-if="line.is_portability"><strong>Operador Origen:</strong> {{ line.source_operator }}</p>
                                    
                                    <div v-if="line.has_vap && line.terminal_details" class="pt-2 border-t mt-2">
                                        <p><strong>Terminal:</strong> {{ line.terminal_details.brand }} {{ line.terminal_details.model }} ({{ line.terminal_details.duration_months }} meses)</p>
                                        <p><strong>Coste:</strong> {{ line.initial_cost }}€ + {{ line.monthly_cost }}€/mes</p>
                                    </div>
                                     <p v-else-if="line.has_vap && !line.terminal_details" class="pt-2 border-t mt-2">
                                        Línea con VAP sin terminal asociado.
                                    </p>
                                    
                                    <p v-if="line.o2o_discount_id" class="text-green-600 font-medium">
                                        <strong>Descuento O2O Aplicado</strong>
                                    </p>
                                </div>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Servicios Adicionales -->
                     <div>
                        <h2 class="text-2xl font-semibold text-gray-800">Servicios Adicionales</h2>
                         <div class="mt-4 space-y-3 text-gray-800">
                            <p v-if="mainFiber"><strong>Fibra Principal:</strong> {{ mainFiber.name }}</p>
                            <p v-if="centralitaService"><strong>Servicio de Centralita:</strong> {{ centralitaService.name }}</p>
                            <p v-if="centralitaFeature"><strong>Opción de Centralita:</strong> {{ centralitaFeature.name }}</p>
                            
                            <div v-if="additionalFibers.length > 0">
                                <p><strong>Fibras Adicionales:</strong></p>
                                <ul class="list-disc list-inside ml-4">
                                    <li v-for="addon in additionalFibers" :key="addon.id">
                                        {{ addon.pivot.quantity }}x {{ addon.name }}
                                    </li>
                                </ul>
                            </div>
                            <div v-if="centralitaExtensions.length > 0">
                                <p><strong>Extensiones de Centralita:</strong></p>
                                <ul class="list-disc list-inside ml-4">
                                    <li v-for="addon in centralitaExtensions" :key="addon.id">
                                        {{ addon.pivot.quantity }}x {{ addon.name }}
                                    </li>
                                </ul>
                            </div>
                         </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
