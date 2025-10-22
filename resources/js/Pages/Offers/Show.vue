<script setup>
import { computed, ref } from 'vue'; // ref añadido
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
// Importa otros componentes si los necesitas (ej. DangerButton, Modal)

const props = defineProps({
    offer: Object, // La oferta con relaciones cargadas
});

const page = usePage();

// --- LÓGICA VISIBILIDAD COMISIONES (Tu lógica original) ---
const canViewCommissions = computed(() => {
    const userRole = page.props.auth.user?.role;
    // Asumiendo que is_manager se pasa correctamente
    return userRole === 'admin' || page.props.auth.user?.is_manager;
});

// --- LÓGICA CENTRALITA (Tu lógica original) ---
const centralitaInfo = computed(() => {
    if (!props.offer) return null;
    const savedCentralita = props.offer.addons?.find(a => a.type === 'centralita');
    const savedOperadora = props.offer.addons?.find(a => a.type === 'centralita_feature');
    const contractedExtensions = props.offer.addons?.filter(a => a.type === 'centralita_extension') || [];
    const includedCentralita = props.offer.package?.addons?.find(a => a.type === 'centralita' && a.pivot.is_included);
    const includedOperadora = props.offer.package?.addons?.find(a => a.type === 'centralita_feature' && a.pivot.is_included);
    const finalCentralita = savedCentralita || includedCentralita;
    const finalOperadora = savedOperadora || includedOperadora;
    const packageIncludedExtensions = props.offer.package?.addons?.filter(a =>
        a.type === 'centralita_extension' && a.pivot.is_included && a.pivot.included_quantity > 0
    ) || [];
    if (!finalCentralita && !finalOperadora && contractedExtensions.length === 0 && packageIncludedExtensions.length === 0) {
        return null;
    }
    return {
        centralita: finalCentralita,
        operadora: finalOperadora,
        contractedExtensions: contractedExtensions,
        packageIncludedExtensions: packageIncludedExtensions
    };
});

// --- LÓGICA INTERNET Y TV (Corregida) ---
const baseInternetAddon = computed(() => props.offer.addons?.find(a => a.type === 'internet'));
const additionalInternetAddons = computed(() => props.offer.addons?.filter(a => a.type === 'internet_additional') || []);
const tvAddons = computed(() => props.offer.addons?.filter(a => a.type === 'tv' || a.type === 'tv_base' || a.type === 'tv_premium') || []);

// --- FUNCIONES FORMATO (Tu lógica original) ---
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        return new Date(dateString).toLocaleString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    } catch { return 'Fecha inválida'; }
};
const formatCurrency = (value) => {
    if (value == null || isNaN(value)) return 'N/A';
    try {
        return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);
    } catch { return 'Error €'; }
};
const formatPrice = (value) => { // Para el desglose
     if (value == null || isNaN(value)) return 'N/A';
    const formatted = formatCurrency(value);
    return value >= 0 ? `+${formatted}` : formatted;
};

// --- LÓGICA ENVÍO EMAIL ---
const sendEmailForm = useForm({});
const sendOfferByEmail = () => { sendEmailForm.post(route('offers.send', props.offer.id), { preserveScroll: true }); };

// --- ESTADO PARA DESPLEGABLES ---
const openDetails = ref({
    lines: false, // Por defecto, líneas plegadas
    internetTv: false, // Combinamos internet y TV
    centralita: false,
    commissionBreakdown: false, // Por defecto, desglose plegado
});

</script>

<template>
    <Head :title="`Detalle Oferta #${offer.id}`" />

    <AuthenticatedLayout>
        <template #header>
             <div class="flex flex-wrap justify-between items-center gap-4">
                 <h2 class="font-semibold text-xl text-gray-800 leading-tight">Detalle de la Oferta #{{ offer.id }}</h2>
                 <div class="space-x-2 flex items-center flex-wrap">
                     <Link :href="route('offers.index')"><SecondaryButton>Volver</SecondaryButton></Link>
                     <Link :href="route('offers.edit', offer.id)"><PrimaryButton>Editar</PrimaryButton></Link>
                     <PrimaryButton @click="sendOfferByEmail" :disabled="sendEmailForm.processing || !offer.client?.email" :class="{ 'opacity-25': sendEmailForm.processing || !offer.client?.email }" :title="!offer.client?.email ? 'El cliente no tiene email' : 'Enviar email'">
                         <svg v-if="sendEmailForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A8 8 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ sendEmailForm.processing ? 'Enviando...' : 'Email' }}
                     </PrimaryButton>
                     <a :href="route('offers.pdf', offer.id)" target="_blank" download class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">PDF</a>
                 </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
           <div v-if="$page.props.flash.success" class="p-4 mb-4 bg-green-100 border border-green-300 text-green-800 rounded-md shadow-sm transition duration-300 ease-in-out">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash.error" class="p-4 mb-4 bg-red-100 border border-red-300 text-red-800 rounded-md shadow-sm transition duration-300 ease-in-out">
                {{ $page.props.flash.error }}
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                    <div class="lg:col-span-2 space-y-8">

                        <section class="bg-white p-6 shadow-sm sm:rounded-lg space-y-4">
                             <div class="flex flex-wrap justify-between items-start gap-y-2 mb-4">
                                <div>
                                    <p class="text-sm text-gray-500">Creada el {{ formatDate(offer.created_at) }} por <span class="font-medium">{{ offer.user?.name || 'N/A' }}</span> (Equipo: {{ offer.user?.team?.name || 'N/A' }})</p>
                                    <p class="text-sm text-gray-500 mt-1">Paquete Base: <span class="font-medium">{{ offer.package?.name || 'N/A' }}</span></p>
                                </div>
                                <div class="text-right">
                                     <span class="font-semibold text-gray-600 block">Estado:</span>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                          :class="{
                                            'bg-yellow-100 text-yellow-800': offer.status === 'pending',
                                            'bg-green-100 text-green-800': offer.status === 'accepted',
                                            'bg-red-100 text-red-800': offer.status === 'rejected',
                                            'bg-blue-100 text-blue-800': offer.status === 'sent',
                                            'bg-gray-100 text-gray-800': !offer.status
                                          }">
                                        {{ offer.status || 'Desconocido' }}
                                    </span>
                                </div>
                            </div>
                            <h3 class="text-lg font-medium leading-6 text-gray-900 border-b pb-2">👤 Datos del Cliente</h3>
                             <div v-if="offer.client" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm p-4 bg-gray-50 rounded-lg">
                                 <div><p class="text-gray-500">Nombre / Razón Social</p><Link :href="route('clients.edit', offer.client.id)" class="font-medium text-blue-600 hover:underline">{{ offer.client.name }}</Link></div>
                                <div><p class="text-gray-500">CIF / NIF</p><p class="font-medium text-gray-900">{{ offer.client.cif_nif }}</p></div>
                                <div><p class="text-gray-500">Email</p><p class="font-medium text-gray-900">{{ offer.client.email || 'No especificado' }}</p></div>
                                <div><p class="text-gray-500">Teléfono</p><p class="font-medium text-gray-900">{{ offer.client.phone || 'No especificado' }}</p></div>
                            </div>
                             <div v-else><p class="italic text-gray-500">No hay cliente asociado.</p></div>
                        </section>

                        <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                             <details class="group" :open="openDetails.lines" @toggle="openDetails.lines = $event.target.open">
                                <summary class="flex justify-between items-center font-medium cursor-pointer list-none">
                                    <h3 class="text-lg leading-6 text-gray-900">📱 Líneas Móviles y Terminales <span class="text-gray-500 text-sm font-normal">({{ offer.lines?.length || 0 }})</span></h3>
                                    <span class="transition group-open:rotate-180">
                                        <svg fill="none" height="20" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="20"><path d="M6 9l6 6 6-6"></path></svg>
                                    </span>
                                </summary>
                                <div class="mt-4 space-y-4 border-t pt-4">
                                     <div v-if="offer.lines && offer.lines.length > 0" class="space-y-4">
                                        <div v-for="(line, index) in offer.lines" :key="line.id || index" class="p-4 border rounded-lg bg-white shadow-sm hover:shadow-md transition-shadow duration-200">
                                             <p class="font-bold text-gray-800 mb-2">Línea {{ index + 1 }}: <span class="font-mono">{{ line.phone_number || 'Número no especificado' }}</span></p>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 text-sm text-gray-600">
                                                <div><span class="font-semibold block text-gray-500">Tipo</span> {{ line.is_extra ? 'Adicional' : 'Principal' }}</div>
                                                <div><span class="font-semibold block text-gray-500">Portabilidad</span> <span :class="line.is_portability ? 'text-green-700' : 'text-orange-700'">{{ line.is_portability ? 'Sí' : 'No' }}<span v-if="line.is_portability"> (desde {{ line.source_operator || 'N/A' }})</span></span></div>
                                                <div><span class="font-semibold block text-gray-500">VAP</span> <span :class="line.has_vap ? 'text-red-700 font-bold' : 'text-gray-500'">{{ line.has_vap ? 'Sí' : 'No' }}</span></div>
                                                <div class="md:col-span-3 mt-3 pt-3 border-t border-gray-100" v-if="line.terminal_details">
                                                    <span class="font-semibold block text-gray-500 mb-1">Terminal Asociado</span>
                                                    <div class="flex flex-wrap justify-between items-center gap-2">
                                                        <span>{{ line.terminal_details.brand }} {{ line.terminal_details.model }} ({{ line.terminal_details.duration_months }} meses)</span>
                                                        <span class="text-xs bg-gray-100 px-2 py-1 rounded">Ini: {{ formatCurrency(line.initial_cost) }} / Mes: {{ formatCurrency(line.monthly_cost) }}</span>
                                                    </div>
                                                </div>
                                                <div class="md:col-span-3 mt-2 italic text-xs text-gray-400" v-else>Sin terminal asociado.</div>
                                            </div>
                                        </div>
                                    </div>
                                    <p v-else class="italic text-gray-500">No se añadieron líneas móviles.</p>
                                </div>
                            </details>
                        </section>

                        <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                             <details class="group" :open="openDetails.internetTv" @toggle="openDetails.internetTv = $event.target.open">
                                <summary class="flex justify-between items-center font-medium cursor-pointer list-none">
                                    <h3 class="text-lg leading-6 text-gray-900">🌐 Internet y TV</h3>
                                    <span class="transition group-open:rotate-180">
                                        <svg fill="none" height="20" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="20"><path d="M6 9l6 6 6-6"></path></svg>
                                    </span>
                                </summary>
                                <div class="mt-4 space-y-4 border-t pt-4">
                                    <div class="text-sm bg-blue-50 p-3 rounded shadow-sm">
                                        <span class="font-semibold text-blue-800">Fibra Base:</span>
                                        <span v-if="baseInternetAddon" class="ml-2">{{ baseInternetAddon.name }}</span>
                                        <span v-else class="italic text-gray-500 ml-2">(No especificada o no contratada)</span>
                                    </div>
                                    <div v-if="additionalInternetAddons.length > 0">
                                        <div v-for="addon in additionalInternetAddons" :key="addon.id" class="text-sm bg-blue-50 p-3 rounded mt-2 shadow-sm">
                                            <span class="font-semibold text-blue-800">Internet Adicional:</span>
                                            <span class="ml-2">{{ addon.name }}</span>
                                            <span v-if="addon.pivot.quantity > 1" class="ml-1 text-xs text-blue-600">(x{{ addon.pivot.quantity }})</span>
                                        </div>
                                    </div>
                                    <div v-if="tvAddons.length > 0" class="text-sm bg-purple-50 p-3 rounded mt-2 shadow-sm">
                                        <span class="font-semibold block mb-1 text-purple-800">Televisión:</span>
                                        <ul class="list-disc list-inside ml-4 space-y-1">
                                            <li v-for="tv in tvAddons" :key="tv.id">{{ tv.name }}</li>
                                        </ul>
                                    </div>
                                     <div v-else class="text-sm text-gray-500 italic mt-2">
                                        Sin addons de TV contratados.
                                    </div>
                                </div>
                            </details>
                        </section>

                        <section v-if="centralitaInfo" class="bg-white p-6 shadow-sm sm:rounded-lg">
                            <details class="group" :open="openDetails.centralita" @toggle="openDetails.centralita = $event.target.open">
                                <summary class="flex justify-between items-center font-medium cursor-pointer list-none">
                                    <h3 class="text-lg leading-6 text-gray-900">📞 Centralita Virtual</h3>
                                    <span class="transition group-open:rotate-180">
                                        <svg fill="none" height="20" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="20"><path d="M6 9l6 6 6-6"></path></svg>
                                    </span>
                                </summary>
                                <div class="mt-4 space-y-3 text-sm bg-indigo-50 p-4 rounded shadow-sm border-t pt-4">
                                    <p v-if="centralitaInfo.centralita"><span class="font-semibold text-indigo-800">Base:</span> {{ centralitaInfo.centralita.name }}</p>
                                    <p v-if="centralitaInfo.operadora"><span class="font-semibold text-indigo-800">Operadora Automática:</span> {{ centralitaInfo.operadora.name }}</p>
                                    <div>
                                        <span class="font-semibold block text-indigo-800 mb-1">Extensiones:</span>
                                         <ul v-if="centralitaInfo.contractedExtensions.length > 0" class="list-disc list-inside ml-4 space-y-1">
                                            <li v-for="ext in centralitaInfo.contractedExtensions" :key="ext.id">
                                                {{ ext.name }} (x{{ ext.pivot.quantity }})
                                            </li>
                                        </ul>
                                         <ul v-else-if="centralitaInfo.packageIncludedExtensions.length > 0" class="list-disc list-inside ml-4 text-gray-500 space-y-1">
                                             <li v-for="ext in centralitaInfo.packageIncludedExtensions" :key="ext.id">
                                                {{ ext.name }} (x{{ ext.pivot.included_quantity }} incluidas en paquete)
                                            </li>
                                        </ul>
                                        <p v-else class="ml-4 italic text-gray-500">Sin extensiones adicionales contratadas o incluidas.</p>
                                    </div>
                                </div>
                            </details>
                        </section>
                        <section v-else class="bg-white p-6 shadow-sm sm:rounded-lg italic text-gray-500">
                             No se incluyó centralita en esta oferta.
                        </section>

                    </div> <div class="lg:col-span-1 space-y-8">
                         <div class="sticky top-8 space-y-8"> <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 border-b pb-2 mb-4">💰 Resumen Económico</h3>
                                <div class="p-4 bg-gray-100 rounded-lg shadow-inner">
                                    <h4 class="font-semibold text-gray-800 text-center mb-3">Resumen Precios</h4>
                                    <div class="flex flex-wrap justify-between items-baseline gap-x-4 mb-2">
                                        <span class="text-2xl font-bold text-gray-900">Precio Final:</span>
                                        <span class="text-2xl font-bold text-indigo-600">{{ formatCurrency(offer.summary?.finalPrice) }}<span class="text-md font-medium text-gray-600">/mes</span></span>
                                    </div>
                                    <div class="flex flex-wrap justify-between items-baseline gap-x-4 text-md font-semibold text-gray-800">
                                        <span>Pago Inicial:</span>
                                        <span>{{ formatCurrency(offer.summary?.totalInitialPayment) }}</span>
                                    </div>
                                    <div class="border-t border-gray-300 pt-3 mt-3 space-y-1">
                                        <h5 class="text-xs font-semibold text-gray-500 mb-1 uppercase">Desglose Mensual:</h5>
                                        <div v-for="(item, index) in offer.summary?.summaryBreakdown" :key="'sum-'+index" class="flex justify-between text-xs" :class="{'text-gray-600': item.price >= 0, 'text-red-500': item.price < 0}">
                                            <span>{{ item.description }}</span>
                                            <span class="font-medium font-mono">{{ formatPrice(item.price) }}</span>
                                        </div>
                                         <p v-if="!offer.summary?.summaryBreakdown || offer.summary.summaryBreakdown.length === 0" class="italic text-gray-400 text-xs">No hay desglose de precios.</p>
                                    </div>
                                </div>
                             </section>

                             <section v-if="canViewCommissions" class="bg-white p-6 shadow-sm sm:rounded-lg">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 border-b pb-2 mb-4">📊 Comisiones</h3>
                                <details class="group bg-yellow-50 border border-yellow-200 rounded-lg p-3 shadow-sm mb-4" :open="openDetails.commissionBreakdown" @toggle="openDetails.commissionBreakdown = $event.target.open">
                                    <summary class="flex justify-between items-center font-medium cursor-pointer list-none text-yellow-800 text-sm">
                                        <h4 class="font-semibold">Ver Desglose Detallado</h4>
                                        <span class="transition group-open:rotate-180">
                                            <svg fill="none" height="16" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="16"><path d="M6 9l6 6 6-6"></path></svg>
                                        </span>
                                    </summary>
                                    <div class="mt-3 space-y-3 text-xs border-t border-yellow-200 pt-3">
                                        <div v-for="(commissions, category) in offer.summary?.commissionDetails" :key="'com-cat-'+category">
                                            <p class="font-semibold text-yellow-700 mb-1 capitalize">{{ category.replace('_', ' ') }}</p>
                                            <div class="space-y-1 border-l-2 border-yellow-400 pl-2 ml-1">
                                                <div v-for="(commission, index) in commissions" :key="'com-item-'+index" class="flex justify-between">
                                                    <span class="text-gray-600">{{ commission.description }}</span>
                                                    <span class="font-medium text-gray-800 font-mono">{{ formatCurrency(commission.amount) }}</span>
                                                </div>
                                            </div>
                                        </div>
                                         <p v-if="!offer.summary?.commissionDetails || Object.keys(offer.summary.commissionDetails).length === 0" class="italic text-gray-500">No hay desglose detallado.</p>
                                    </div>
                                </details>

                                <div class="space-y-2 text-sm bg-green-50 p-4 rounded-lg border border-green-200 shadow-sm">
                                     <div class="flex justify-between font-medium text-gray-600">
                                        <span>Comisión Bruta (100%):</span>
                                        <span class="font-mono">{{ formatCurrency(offer.summary?.totalCommission) }}</span>
                                    </div>
                                    <div v-if="page.props.auth.user?.role !== 'admin'" class="flex justify-between font-medium text-gray-700">
                                        <span>Comisión Equipo:</span>
                                        <span class="font-mono">{{ formatCurrency(offer.summary?.teamCommission) }}</span>
                                    </div>
                                    <div class="flex justify-between text-lg font-bold text-emerald-700 pt-2 border-t border-green-300 mt-2">
                                        <span>Comisión Vendedor:</span>
                                        <span class="font-mono">{{ formatCurrency(offer.summary?.userCommission) }}</span>
                                    </div>
                                </div>
                            </section>
                            <section v-else class="bg-white p-6 shadow-sm sm:rounded-lg italic text-sm text-gray-500">
                                El desglose de comisiones no está visible para tu rol.
                            </section>

                        </div> </div> </div> </div> </div> </AuthenticatedLayout>
</template>