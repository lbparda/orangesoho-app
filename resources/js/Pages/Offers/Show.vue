<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    offer: Object,
});

const page = usePage();
const canViewCommissions = computed(() => {
    const role = page.props.auth.user?.role;
    return role === 'admin' || role === 'team_lead';
});

const centralitaInfo = computed(() => {
    if (!props.offer.addons) return null;
    
    const centralita = props.offer.addons.find(a => a.type === 'centralita');
    if (!centralita) return null;

    const operadora = props.offer.addons.find(a => a.type === 'centralita_feature');
    const contractedExtensions = props.offer.addons.filter(a => a.type === 'centralita_extension');

    return {
        centralita,
        operadora,
        contractedExtensions
    };
});

const packageIncludedExtensions = computed(() => {
    if (!props.offer.package?.addons) return [];
    return props.offer.package.addons.filter(a => 
        a.type === 'centralita_extension' && a.pivot.is_included && a.pivot.included_quantity > 0
    );
});
</script>

<template>
    <Head :title="`Detalle Oferta #${offer.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detalle de la Oferta</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8 bg-white border-b border-gray-200 space-y-8">
                        
                        <div class="flex justify-between items-start">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">Resumen de la Oferta #{{ offer.id }}</h1>
                                <p class="text-sm text-gray-500">Creada el {{ new Date(offer.created_at).toLocaleString() }} por <span class="font-medium">{{ offer.user?.name || 'N/A' }}</span></p>
                            </div>
                            <Link :href="route('offers.index')">
                                <PrimaryButton>Volver al Listado</PrimaryButton>
                            </Link>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-700 mb-3">👤 Datos del Cliente</h3>
                            <div v-if="offer.client" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm p-4 bg-slate-50 rounded-lg">
                                <div>
                                    <p class="text-gray-500">Nombre</p>
                                    <p class="font-medium text-gray-900">{{ offer.client.name }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">CIF / NIF</p>
                                    <p class="font-medium text-gray-900">{{ offer.client.cif_nif }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Email</p>
                                    <p class="font-medium text-gray-900">{{ offer.client.email || 'No especificado' }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Teléfono</p>
                                    <p class="font-medium text-gray-900">{{ offer.client.phone || 'No especificado' }}</p>
                                </div>
                            </div>
                             <div v-else>
                                 <p class="text-gray-500 p-4 bg-slate-50 rounded-lg">No hay cliente asociado a esta oferta.</p>
                             </div>
                        </div>

                        <div class="p-6 bg-gray-100 rounded-lg">
                            <h2 class="text-xl font-semibold text-gray-800 text-center mb-4">{{ offer.package.name }}</h2>
                            <div class="flex justify-between text-3xl font-extrabold text-gray-900 items-baseline">
                                <span>Precio Final:</span>
                                <span>{{ offer.summary.finalPrice }}<span class="text-lg font-medium text-gray-600">€/mes</span></span>
                            </div>
                            <div class="flex justify-between text-lg font-bold text-gray-800 mt-2">
                                <span>Pago Inicial Total:</span>
                                <span>{{ offer.summary.totalInitialPayment }}€</span>
                            </div>
                        </div>

                        <div class="border-t pt-6">
                            <details v-if="canViewCommissions" class="group">
                                <summary class="flex justify-between items-center font-medium cursor-pointer list-none">
                                    <h3 class="text-lg font-semibold text-gray-700">💰 Desglose de Comisiones</h3>
                                    <span class="transition group-open:rotate-180">
                                        <svg fill="none" height="24" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="24"><path d="M6 9l6 6 6-6"></path></svg>
                                    </span>
                                </summary>
                                <div class="mt-4 space-y-4">
                                    <div v-for="(commissions, category) in offer.summary.commissionDetails" :key="category" class="text-sm">
                                        <h4 class="font-semibold text-gray-600 mb-2">{{ category }}</h4>
                                        <div class="space-y-1 border-l-2 pl-4 ml-2">
                                            <div v-for="(commission, index) in commissions" :key="index" class="flex justify-between">
                                                <span class="text-gray-500">{{ commission.description }}</span>
                                                <span class="font-medium text-gray-800">{{ commission.amount.toFixed(2) }}€</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </details>
                            
                            <div class="border-t pt-4 mt-4 space-y-2">
                                <div v-if="canViewCommissions" class="flex justify-between text-md font-medium text-gray-500">
                                    <span>Comisión Bruta (100%):</span>
                                    <span>{{ offer.summary.totalCommission }}€</span>
                                </div>
                                <div v-if="canViewCommissions && offer.user.role !== 'admin'" class="flex justify-between text-lg font-medium text-gray-600">
                                    <span>Comisión de Equipo:</span>
                                    <span>{{ offer.summary.teamCommission }}€</span>
                                </div>
                                <div class="flex justify-between text-xl font-bold text-emerald-600">
                                    <span>Comisión Final del Vendedor:</span>
                                    <span>{{ offer.summary.userCommission }}€</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="centralitaInfo || packageIncludedExtensions.length > 0" class="border-t pt-6">
                            <h3 class="text-lg font-semibold text-gray-700 mb-3">✅ Centralita Virtual y Extensiones</h3>
                            <div class="space-y-4 text-sm">
                                <div v-if="centralitaInfo" class="space-y-2">
                                    <div class="flex justify-between p-2 bg-slate-50 rounded">
                                        <span class="font-medium text-gray-800">{{ centralitaInfo.centralita.name }}</span>
                                        <span class="font-semibold">Contratada</span>
                                    </div>
                                    <div v-if="centralitaInfo.operadora" class="flex justify-between p-2 bg-slate-50 rounded">
                                        <span class="font-medium text-gray-800">Operadora Automática</span>
                                        <span>Incluida</span>
                                    </div>
                                </div>
                                
                                <div v-if="packageIncludedExtensions.length > 0">
                                    <p class="font-medium text-gray-800 mt-3 mb-1">Extensiones Incluidas por Paquete:</p>
                                    <ul class="list-disc list-inside pl-2 space-y-1">
                                        <li v-for="ext in packageIncludedExtensions" :key="`pkg_${ext.id}`">
                                            {{ ext.pivot.included_quantity }}x {{ ext.name }}
                                        </li>
                                    </ul>
                                </div>

                                <div v-if="centralitaInfo && centralitaInfo.contractedExtensions.length > 0">
                                    <p class="font-medium text-gray-800 mt-3 mb-1">Extensiones Contratadas Adicionalmente:</p>
                                    <ul class="list-disc list-inside pl-2 space-y-1">
                                        <li v-for="ext in centralitaInfo.contractedExtensions" :key="ext.id">
                                            {{ ext.pivot.quantity }}x {{ ext.name }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="border-t pt-6">
                            <h3 class="text-lg font-semibold text-gray-700 mb-3">Líneas Móviles</h3>
                            <div class="space-y-4">
                                <div v-for="(line, index) in offer.lines" :key="line.id" class="p-4 border rounded-lg">
                                    <p class="font-bold text-gray-800">Línea {{ index + 1 }} - {{ line.phone_number || 'Número no especificado' }}</p>
                                    <ul class="mt-2 text-sm text-gray-600 list-disc list-inside">
                                        <li v-if="line.is_portability">Portabilidad desde: <span class="font-semibold">{{ line.source_operator }}</span></li>
                                        <li v-else>Número nuevo</li>
                                        <li v-if="line.terminal_details">
                                            Terminal: <span class="font-semibold">{{ line.terminal_details.brand }} {{ line.terminal_details.model }} ({{ line.terminal_details.duration_months }} meses)</span>
                                            - Pago inicial: {{ line.initial_cost }}€, Cuota mensual: {{ line.monthly_cost }}€
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>