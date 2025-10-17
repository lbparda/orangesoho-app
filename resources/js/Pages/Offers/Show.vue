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
    if (!props.offer) return null;

    const savedCentralita = props.offer.addons?.find(a => a.type === 'centralita');
    const savedOperadora = props.offer.addons?.find(a => a.type === 'centralita_feature');
    const contractedExtensions = props.offer.addons?.filter(a => a.type === 'centralita_extension');

    const includedCentralita = props.offer.package?.addons?.find(a => a.type === 'centralita' && a.pivot.is_included);
    const includedOperadora = props.offer.package?.addons?.find(a => a.type === 'centralita_feature' && a.pivot.is_included);

    const finalCentralita = savedCentralita || includedCentralita;
    const finalOperadora = savedOperadora || includedOperadora;

    if (!finalCentralita && !finalOperadora && (!contractedExtensions || contractedExtensions.length === 0)) {
        return null;
    }

    return {
        centralita: finalCentralita,
        operadora: finalOperadora,
        contractedExtensions: contractedExtensions || [],
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

                            <div class="flex items-center space-x-2">
                                <a
                                    :href="route('offers.pdf', offer.id)"
                                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                >
                                    Imprimir PDF
                                </a>

                                <Link :href="route('offers.index')">
                                    <PrimaryButton>Volver al Listado</PrimaryButton>
                                </Link>
                            </div>
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
                            <div class="border-t pt-4 mt-4 space-y-2">
                                <div v-for="(item, index) in offer.summary.summaryBreakdown" :key="index" class="flex justify-between text-sm" :class="{'text-gray-700': item.price >= 0, 'text-red-600': item.price < 0}">
                                    <span>{{ item.description }}</span>
                                    <span class="font-medium">
                                        {{ item.price >= 0 ? '+' : '' }}{{ item.price.toFixed(2) }}€
                                    </span>
                                </div>
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