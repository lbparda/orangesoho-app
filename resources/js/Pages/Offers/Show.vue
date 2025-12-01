<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import { useOfferCalculations } from '@/composables/useOfferCalculations';

const props = defineProps({
    offer: Object,
    centralitaExtensions: Array,
    // Props nuevas necesarias para que useOfferCalculations no falle
    allAddons: Array, 
    discounts: Array,
    portabilityCommission: Number,
    portabilityExceptions: Array,
    additionalInternetAddons: Array,
    fiberFeatures: Array,
});

const page = usePage();
const currentUser = computed(() => page.props.auth.user);

// --- INICIO: INTEGRACIÓN DEL COMPOSABLE PARA CÁLCULOS EN TIEMPO REAL ---

// Mock de props mínimas que necesita el composable
const composablePropsMock = {
    packages: [props.offer.package], // Mock
    allAddons: props.allAddons,
    discounts: props.discounts,
    portabilityCommission: props.portabilityCommission,
    portabilityExceptions: props.portabilityExceptions,
    additionalInternetAddons: props.additionalInternetAddons,
    fiberFeatures: props.fiberFeatures,
    centralitaExtensions: props.centralitaExtensions,
    auth: { user: props.offer.user } // Importante para fallback
};

const selectedPackageIdRef = computed(() => props.offer.package_id);
const linesRef = computed(() => props.offer.lines);

const selectedInternetAddonIdRef = computed(() => 
    props.offer.addons.find(a => a.type === 'internet')?.id || null
);

const additionalInternetLinesRef = computed(() => {
    return props.offer.addons
        .filter(a => a.type === 'internet_additional')
        .map(a => ({
            addon_id: a.id,
            has_ip_fija: !!a.pivot.has_ip_fija,
            has_fibra_oro: !!a.pivot.has_fibra_oro,
            selected_centralita_id: a.pivot.selected_centralita_id
        }));
});

const selectedCentralitaIdRef = computed(() => 
    props.offer.addons.find(a => a.type === 'centralita' && a.pivot.selected_centralita_id === null)?.id || null
);

const centralitaExtensionQuantitiesRef = computed(() => {
    const quantities = {};
    props.offer.addons
        .filter(a => a.type === 'centralita_extension')
        .forEach(a => {
            if (a.pivot.quantity > 0) quantities[a.id] = a.pivot.quantity;
        });
    return quantities;
});

const isOperadoraAutomaticaSelectedRef = computed(() => 
    !!props.offer.addons.find(a => a.type === 'centralita_feature' && a.name === 'Operadora Automática')
);

const selectedTvAddonIdsRef = computed(() => 
    props.offer.addons.filter(a => a.type === 'tv').map(a => a.id)
);

const selectedDigitalAddonIdsRef = computed(() => 
    props.offer.addons.filter(a => ['service', 'software'].includes(a.type)).map(a => a.id)
);

const selectedBenefitsRef = computed(() => props.offer.benefits || []);

const formMock = {
    is_ip_fija_selected: props.offer.addons.some(a => a.name === 'IP Fija' && a.type === 'internet_feature'),
    is_fibra_oro_selected: props.offer.addons.some(a => a.name === 'Fibra Oro' && a.type === 'internet_feature'),
    ddi_quantity: props.offer.addons.find(a => a.name === 'DDI' && a.type === 'centralita_feature')?.pivot?.quantity || 0
};

// --- LLAMADA AL COMPOSABLE (MODIFICADA PARA INMUTABILIDAD) ---
// 1. Obtenemos el cálculo "en vivo" pero lo renombramos a liveCalculationSummary
const { calculationSummary: liveCalculationSummary } = useOfferCalculations(
    composablePropsMock,
    selectedPackageIdRef,
    linesRef,
    selectedInternetAddonIdRef,
    additionalInternetLinesRef,
    selectedCentralitaIdRef,
    centralitaExtensionQuantitiesRef,
    isOperadoraAutomaticaSelectedRef,
    selectedTvAddonIdsRef,
    selectedDigitalAddonIdsRef,
    formMock,
    selectedBenefitsRef,
    props.offer.user 
);

// 2. Creamos una computed property que decide qué resumen mostrar
const calculationSummary = computed(() => {
    // Si la oferta está finalizada y tiene un snapshot, usamos el guardado en BDD (INMUTABLE)
    if (props.offer.status === 'finalizada' && props.offer.summary) {
        return props.offer.summary;
    }
    // Si es borrador o no hay snapshot, usamos el cálculo en tiempo real
    return liveCalculationSummary.value;
});
// --- FIN INTEGRACIÓN COMPOSABLE ---


// --- LÓGICA DE VISUALIZACIÓN ORIGINAL ---

const canViewCommissions = computed(() => {
    const userRole = currentUser.value?.role;
    return userRole === 'admin' || currentUser.value?.is_manager || userRole === 'user';
});

const confirmingLockOffer = ref(false);
const lockForm = useForm({});
const confirmLockOffer = () => confirmingLockOffer.value = true;
const closeModal = () => { confirmingLockOffer.value = false; confirmingSendEmail.value = false; };
const lockOffer = () => {
    lockForm.post(route('offers.lock', props.offer.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

const confirmingSendEmail = ref(false);
const sendEmailForm = useForm({ email: '' });
const confirmSendEmail = () => {
    sendEmailForm.email = props.offer.client?.email || '';
    confirmingSendEmail.value = true;
};
const sendOfferByEmail = () => {
    sendEmailForm.post(route('offers.send', props.offer.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
    });
};

// --- LÓGICA CENTRALITA (Principal) ---
const centralitaInfo = computed(() => {
    if (!props.offer) return null;

    const savedCentralita = props.offer.addons?.find(a => a.type === 'centralita' && a.pivot.selected_centralita_id === null);
    const savedOperadora = props.offer.addons?.find(a => a.type === 'centralita_feature' && a.pivot.addon_name === 'Operadora Automática');
    const savedDdi = props.offer.addons?.find(a => a.type === 'centralita_feature' && a.pivot.addon_name === 'DDI'); 

    const mainCentralitaPivotId = savedCentralita?.pivot.id;
    const contractedExtensions = props.offer.addons?.filter(a => 
        a.type === 'centralita_extension' && a.pivot.related_centralita_id === mainCentralitaPivotId
    ) || [];
    
    const includedCentralita = props.offer.package?.addons?.find(a => a.type === 'centralita' && a.pivot.is_included);
    const includedOperadora = props.offer.package?.addons?.find(a => a.type === 'centralita_feature' && a.pivot.addon_name === 'Operadora Automática' && a.pivot.is_included);
    
    const finalCentralita = savedCentralita || includedCentralita;
    const finalOperadora = savedOperadora || includedOperadora;
    
    const packageIncludedExtensions = props.offer.package?.addons?.filter(a =>
        a.type === 'centralita_extension' && a.pivot.is_included && a.pivot.included_quantity > 0
    ) || [];

    if (!finalCentralita && !finalOperadora && contractedExtensions.length === 0 && packageIncludedExtensions.length === 0 && !savedDdi) { 
        return null; 
    }
    
    return {
        centralita: finalCentralita,
        operadora: finalOperadora,
        ddi: savedDdi,
        contractedExtensions: contractedExtensions,
        packageIncludedExtensions: packageIncludedExtensions
    };
});

// --- LÓGICA INTERNET, TV Y MULTISEDE ---
// Usamos props.offer.addons directamente para evitar conflictos con la prop 'allAddons'
const offerAddons = computed(() => props.offer.addons || []);
const allPackageAddons = computed(() => props.offer.package?.addons || []);

const baseInternetAddon = computed(() => offerAddons.value.find(a => a.type === 'internet'));

const ipFijaPrincipal = computed(() => offerAddons.value.find(a => 
    a.type === 'internet_feature' && a.pivot.addon_name === 'IP Fija'
));
const fibraOroPrincipal = computed(() => offerAddons.value.find(a => 
    a.type === 'internet_feature' && a.pivot.addon_name === 'Fibra Oro'
));

const additionalInternetAddonsData = computed(() => offerAddons.value.filter(a => a.type === 'internet_additional') || []);
const tvAddons = computed(() => offerAddons.value.filter(a => a.type === 'tv' || a.type === 'tv_base' || a.type === 'tv_premium') || []);

const getPackageCentralitaDetails = (centralitaId) => {
    if (!centralitaId) return null;
    return allPackageAddons.value.find(a => a.type === 'centralita' && a.id === centralitaId);
};

const findAutoIncludedExtension = (centralitaAddon) => {
    if (!centralitaAddon) return null;
    const type = centralitaAddon.name.split(' ')[1]; 
    if (!type) return null;
    return props.centralitaExtensions.find(a => 
        a.type === 'centralita_extension' && a.name.includes(type)
    );
};

const centralitasMultisede = computed(() => {
    if (!props.centralitaExtensions) return [];

    return additionalInternetAddonsData.value
        .filter(addon => !!addon.pivot.selected_centralita_id)
        .map(addon => {
            const centralitaDetails = getPackageCentralitaDetails(addon.pivot.selected_centralita_id);
            const includedExtension = findAutoIncludedExtension(centralitaDetails); 

            const multiCentralitaPivotId = offerAddons.value.find(c => c.id === addon.pivot.selected_centralita_id)?.pivot.id;
            const multiContractedExtensions = offerAddons.value.filter(a => 
                a.type === 'centralita_extension' && a.pivot.related_centralita_id === multiCentralitaPivotId
            ) || [];

            return {
                id: addon.id,
                lineName: addon.pivot.addon_name,
                centralitaName: centralitaDetails?.name || `ID ${addon.pivot.selected_centralita_id}`,
                has_ip_fija: addon.pivot.has_ip_fija,
                has_fibra_oro: addon.pivot.has_fibra_oro,
                extensionName: includedExtension?.name || null,
                contractedExtensions: multiContractedExtensions
            };
        });
});

const digitalSolutions = computed(() => {
    if (!props.offer?.addons) return [];
    return props.offer.addons.filter(a => ['service', 'software'].includes(a.type));
});

const appliedBenefits = computed(() => {
    return props.offer?.benefits || [];
});

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
const formatPrice = (value) => { 
      if (value == null || isNaN(value)) return 'N/A';
    const formatted = formatCurrency(value);
    return value >= 0 ? `+${formatted}` : formatted;
};

const openDetails = ref({
    lines: false,
    internetTv: false,
    centralita: false,
    benefits: false, 
    solutions: false, 
    commissionBreakdown: false,
});

</script>

<template>
    <Head :title="`Detalle Oferta #${offer.id}`" />

    <AuthenticatedLayout>
        <template #header>
             <div class="flex flex-wrap justify-between items-center gap-4">
                 <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                     Detalle de la Oferta #{{ offer.id }}
                     <span v-if="currentUser.id !== offer.user_id" class="text-sm font-normal text-gray-500 ml-2">
                         (Propietario: {{ offer.user?.name }})
                     </span>
                 </h2>
                 <div class="space-x-2 flex items-center flex-wrap">
                      <Link :href="route('offers.index')"><SecondaryButton>Volver</SecondaryButton></Link>
                      
                      <Link v-if="offer.status === 'borrador' || currentUser.role === 'admin'" :href="route('offers.edit', offer.id)">
                          <PrimaryButton>Editar</PrimaryButton>
                      </Link>
                      
                      <DangerButton @click="confirmLockOffer" v-if="offer.status === 'borrador'">
                          Finalizar y Bloquear
                      </DangerButton>
                      
                      <PrimaryButton @click="confirmSendEmail" :title="'Enviar email'">
                          Email
                      </PrimaryButton>
                      
                      <a :href="route('offers.pdf', offer.id)" target="_blank" download class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">PDF</a>
                 </div>
             </div>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
            <div v-if="$page.props.flash.success" class="p-4 mb-4 bg-green-100 border border-green-300 text-green-800 rounded-md shadow-sm">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash.warning" class="p-4 mb-4 bg-yellow-100 border border-yellow-300 text-yellow-800 rounded-md shadow-sm">
                  {{ $page.props.flash.warning }}
            </div>
            <div v-if="$page.props.flash.error" class="p-4 mb-4 bg-red-100 border border-red-300 text-red-800 rounded-md shadow-sm">
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
                                     <p class="text-sm text-gray-500 mt-1">Paquete Base: <span class="font-medium">{{ offer.package_name || offer.package?.name || 'N/A' }}</span></p>
                                 </div>
                                 <div class="text-right">
                                     <span class="font-semibold text-gray-600 block">Estado:</span>
                                     <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize"
                                         :class="{
                                             'bg-blue-100 text-blue-800': offer.status === 'borrador',
                                             'bg-green-100 text-green-800': offer.status === 'finalizada',
                                             'bg-gray-100 text-gray-800': !offer.status
                                         }">
                                         {{ offer.status || 'Borrador' }}
                                     </span>
                                 </div>
                             </div>
                             <h3 class="text-lg font-medium leading-6 text-gray-900 border-b pb-2">👤 Datos del Cliente</h3>
                             <div v-if="offer.client" class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm p-4 bg-gray-50 rounded-lg">
                                 <div><p class="text-gray-500">Nombre / Razón Social</p><Link :href="route('clients.edit', offer.client.id)" class="font-medium text-blue-600 hover:underline">{{ offer.client.name }}</Link></div>
                                 <div><p class="text-gray-500">CIF / NIF</p><p class="font-medium text-gray-900">{{ offer.client.cif_nif }}</p></div>
                                 <div><p class="text-gray-500">Email</p><p class="font-medium text-gray-900">{{ offer.client.email || 'No especificado' }}</p></div>
                                 <div><p class="text-gray-500">Teléfono</p><p class="font-medium text-gray-900">{{ offer.client.phone || 'No especificado' }}</p></div>
                                 <div><p class="text-gray-500">Dirección</p><p class="font-medium text-gray-900">{{ offer.client.address || 'No especificada' }}</p></div>
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
                                                 
                                                 <div class="md:col-span-3 mt-3 pt-3 border-t border-gray-100" v-if="line.terminal_name">
                                                     <span class="font-semibold block text-gray-500 mb-1">Terminal Asociado</span>
                                                     <div class="flex flex-wrap justify-between items-center gap-2">
                                                         <span>{{ line.terminal_name }}</span> <span class="text-xs bg-gray-100 px-2 py-1 rounded">Ini: {{ formatCurrency(line.initial_cost) }} / Mes: {{ formatCurrency(line.monthly_cost) }}</span>
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
                                        <span v-if="baseInternetAddon" class="ml-2">{{ baseInternetAddon.pivot.addon_name }}</span>
                                        <span v-else class="italic text-gray-500 ml-2">(No especificada o no contratada)</span>
                                        
                                        <div v-if="ipFijaPrincipal" class="mt-2 pt-2 border-t border-blue-200 ml-4">
                                            <span class="text-xs font-semibold text-gray-700">IP Fija Principal:</span>
                                            <span class="ml-1 text-xs">
                                                Incluida
                                                <span v-if="centralitaInfo?.centralita || centralitasMultisede.length > 0">(Gratis por Centralita)</span>
                                            </span>
                                        </div>
                                        
                                        <div v-if="fibraOroPrincipal" class="mt-2 pt-2 border-t border-blue-200 ml-4">
                                            <span class="text-xs font-semibold text-gray-700">Fibra Oro Principal:</span>
                                            <span class="ml-1 text-xs">
                                                Incluida
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div v-if="additionalInternetAddonsData.length > 0">
                                        <div v-for="(addon, index) in additionalInternetAddonsData" :key="addon.id + '-' + index" class="text-sm bg-blue-50 p-3 rounded mt-2 shadow-sm">
                                            <span class="font-semibold text-blue-800">Internet Adicional:</span>
                                            <span class="ml-2">{{ addon.pivot.addon_name }}</span>

                                            <div v-if="addon.pivot.has_ip_fija && !addon.pivot.selected_centralita_id" class="mt-2 pt-2 border-t border-blue-200 ml-4 space-y-1">
                                                <span class="text-xs font-semibold text-gray-700">IP Fija:</span>
                                                <span class="ml-1 text-xs">Incluida</span>
                                            </div>
                                            
                                            <div v-if="addon.pivot.has_fibra_oro && !addon.pivot.selected_centralita_id" class="mt-2 pt-2 border-t border-blue-200 ml-4 space-y-1">
                                                <span class="text-xs font-semibold text-gray-700">Fibra Oro:</span>
                                                <span class="ml-1 text-xs">Incluida</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div v-if="tvAddons.length > 0" class="text-sm bg-purple-50 p-3 rounded mt-2 shadow-sm">
                                        <span class="font-semibold block mb-1 text-purple-800">Televisión:</span>
                                        <ul class="list-disc list-inside ml-4 space-y-1">
                                            <li v-for="tv in tvAddons" :key="tv.id">{{ tv.pivot.addon_name }}</li>
                                        </ul>
                                    </div>
                                    <div v-else class="text-sm text-gray-500 italic mt-2">
                                        Sin addons de TV contratados.
                                    </div>
                                </div>
                            </details>
                        </section>

                        <section v-if="centralitaInfo || centralitasMultisede.length > 0" class="bg-white p-6 shadow-sm sm:rounded-lg">
                            <details class="group" :open="openDetails.centralita" @toggle="openDetails.centralita = $event.target.open">
                                <summary class="flex justify-between items-center font-medium cursor-pointer list-none">
                                    <h3 class="text-lg leading-6 text-gray-900">📞 Centralita Virtual y Multisede</h3>
                                    <span class="transition group-open:rotate-180">
                                        <svg fill="none" height="20" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="20"><path d="M6 9l6 6 6-6"></path></svg>
                                    </span>
                                </summary>
                                
                                <div class="mt-4 space-y-4 border-t pt-4">
                                    
                                    <div v-if="centralitaInfo" class="text-sm bg-indigo-50 p-4 rounded shadow-sm space-y-2">
                                        <p class="font-medium text-indigo-900">Centralita Principal</p>
                                        <p v-if="centralitaInfo.centralita"><span class="font-semibold text-indigo-800">Base:</span> {{ centralitaInfo.centralita.name }}</p>
                                        <p v-if="centralitaInfo.operadora"><span class="font-semibold text-indigo-800">Operadora Automática:</span> {{ centralitaInfo.operadora.pivot.addon_name }}</p>

                                        <div v-if="centralitaInfo.ddi" class="mt-2 pt-2 border-t border-indigo-200">
                                            <p class="font-semibold text-indigo-800">DDI</p>
                                            <p class="ml-4 text-xs">{{ centralitaInfo.ddi.pivot.quantity }} unidades contratadas</p>
                                        </div>
                                        
                                        <div class="mt-2 pt-2 border-t border-indigo-200">
                                            <span class="font-semibold block text-indigo-800 mb-1">Extensiones (Principal):</span>
                                            
                                            <ul v-if="centralitaInfo.packageIncludedExtensions.length > 0" class="list-disc list-inside ml-4 text-gray-500 space-y-1">
                                                <li v-for="ext in centralitaInfo.packageIncludedExtensions" :key="ext.id">
                                                    {{ ext.name }} (x{{ ext.pivot.included_quantity }} incluidas)
                                                </li>
                                            </ul>

                                            <ul v-if="centralitaInfo.contractedExtensions.length > 0" class="list-disc list-inside ml-4 space-y-1">
                                                <li v-for="ext in centralitaInfo.contractedExtensions" :key="ext.id">
                                                    {{ ext.pivot.addon_name || ext.name }} (x{{ ext.pivot.quantity }})
                                                </li>
                                            </ul>

                                            <p v-if="centralitaInfo.contractedExtensions.length === 0 && centralitaInfo.packageIncludedExtensions.length === 0" class="ml-4 italic text-gray-500">Sin extensiones.</p>
                                        </div>
                                    </div>
                                    
                                    <div v-if="centralitasMultisede.length > 0" class="space-y-3">
                                        <div v-for="multi in centralitasMultisede" :key="multi.id" class="text-sm bg-indigo-50 p-4 rounded shadow-sm">
                                            <p class="font-medium text-indigo-900 mb-2">Centralita Multisede (en {{ multi.lineName }})</p>
                                            <p><span class="font-semibold text-indigo-800">Base:</span> {{ multi.centralitaName }}</p>
                                            
                                            <div v-if="multi.extensionName" class="mt-2 pt-2 border-t border-indigo-200">
                                                <span class="font-semibold block text-indigo-800 mb-1">Extensión Incluida (Multisede):</span>
                                                <ul class="list-disc list-inside ml-4 space-y-1">
                                                    <li>{{ multi.extensionName }} (x1 Incluida)</li>
                                                </ul>
                                            </div>
                                            
                                            <div v-if="multi.has_ip_fija" class="mt-2 pt-2 border-t border-indigo-200">
                                                <span class="text-xs font-semibold text-gray-700">IP Fija:</span>
                                                <span class="ml-1 text-xs">Incluida (Gratis por Centralita)</span>
                                            </div>

                                            <div v-if="multi.has_fibra_oro" class="mt-2 pt-2 border-t border-indigo-200">
                                                <span class="text-xs font-semibold text-gray-700">Fibra Oro:</span>
                                                <span class="ml-1 text-xs">Incluida</span>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </details>
                        </section>
                        
                        <section v-if="!centralitaInfo && centralitasMultisede.length === 0" class="bg-white p-6 shadow-sm sm:rounded-lg italic text-gray-500">
                            No se incluyó ninguna centralita en esta oferta.
                        </section>

                        <section v-if="digitalSolutions.length > 0" class="bg-white p-6 shadow-sm sm:rounded-lg">
                            <details class="group" :open="openDetails.solutions" @toggle="openDetails.solutions = $event.target.open">
                                <summary class="flex justify-between items-center font-medium cursor-pointer list-none">
                                    <h3 class="text-lg leading-6 text-gray-900">🚀 Soluciones Digitales</h3>
                                    <span class="transition group-open:rotate-180">
                                        <svg fill="none" height="20" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="20"><path d="M6 9l6 6 6-6"></path></svg>
                                    </span>
                                </summary>
                                <div class="mt-4 space-y-2 border-t pt-4 text-sm">
                                    <ul class="list-disc list-inside ml-4 space-y-1">
                                        <li v-for="solution in digitalSolutions" :key="solution.id">
                                            {{ solution.pivot.addon_name }} 
                                            <span class="text-xs text-gray-500">({{ formatCurrency(solution.pivot.addon_price) }}/mes)</span>
                                        </li>
                                    </ul>
                                </div>
                            </details>
                        </section>
                        <section v-if="appliedBenefits.length > 0" class="bg-white p-6 shadow-sm sm:rounded-lg">
                            <details class="group" :open="openDetails.benefits" @toggle="openDetails.benefits = $event.target.open">
                                <summary class="flex justify-between items-center font-medium cursor-pointer list-none">
                                    <h3 class="text-lg leading-6 text-gray-900">🎁 Beneficios Aplicados</h3>
                                    <span class="transition group-open:rotate-180">
                                        <svg fill="none" height="20" shape-rendering="geometricPrecision" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" viewBox="0 0 24 24" width="20"><path d="M6 9l6 6 6-6"></path></svg>
                                    </span>
                                </summary>
                                <div class="mt-4 space-y-2 border-t pt-4 text-sm">
                                    <ul class="list-disc list-inside ml-4 space-y-1">
                                        <li v-for="benefit in appliedBenefits" :key="benefit.id">
                                            {{ benefit.description }}
                                            <span v-if="benefit.addon" class="text-xs text-gray-500">(Aplica a: {{ benefit.addon.name }})</span>
                                        </li>
                                    </ul>
                                </div>
                            </details>
                        </section>
                    </div> 
                    
                    <div class="lg:col-span-1 space-y-8">
                         <div class="sticky top-8 space-y-8"> 
                             <section class="bg-white p-6 shadow-sm sm:rounded-lg">
                                 <h3 class="text-lg font-medium leading-6 text-gray-900 border-b pb-2 mb-4">💰 Resumen Económico</h3>
                                 <div class="p-4 bg-gray-100 rounded-lg shadow-inner">
                                     <h4 class="font-semibold text-gray-800 text-center mb-3">Resumen Precios</h4>
                                     <div class="flex flex-wrap justify-between items-baseline gap-x-4 mb-2">
                                         <span class="text-2xl font-bold text-gray-900">Precio Final:</span>
                                         <span class="text-2xl font-bold text-indigo-600">{{ formatCurrency(calculationSummary.finalPrice) }}<span class="text-md font-medium text-gray-600">/mes</span></span>
                                     </div>
                                     <div class="flex flex-wrap justify-between items-baseline gap-x-4 text-md font-semibold text-gray-800">
                                         <span>Pago Inicial:</span>
                                         <span>{{ formatCurrency(calculationSummary.totalInitialPayment) }}</span>
                                     </div>
                                     <div class="border-t border-gray-300 pt-3 mt-3 space-y-1">
                                         <h5 class="text-xs font-semibold text-gray-500 mb-1 uppercase">Desglose Mensual:</h5>
                                         <div v-for="(item, index) in calculationSummary.summaryBreakdown" :key="'sum-'+index" class="flex justify-between text-xs" :class="{'text-gray-600': item.price >= 0, 'text-red-500': item.price < 0}">
                                             <span>{{ item.description }}</span>
                                             <span class="font-medium font-mono">{{ formatPrice(item.price) }}</span>
                                         </div>
                                         <p v-if="!calculationSummary.summaryBreakdown || calculationSummary.summaryBreakdown.length === 0" class="italic text-gray-400 text-xs">No hay desglose de precios.</p>
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
                                         <div v-for="(commissions, category) in calculationSummary.commissionDetails" :key="'com-cat-'+category">
                                             <p class="font-semibold text-yellow-700 mb-1 capitalize">{{ category.replace('_', ' ') }}</p>
                                             <div class="space-y-1 border-l-2 border-yellow-400 pl-2 ml-1">
                                                 <div v-for="(commission, index) in commissions" :key="'com-item-'+index" class="flex justify-between">
                                                     <span class="text-gray-600">{{ commission.description }}</span>
                                                     <span class="font-medium text-gray-800 font-mono">{{ formatCurrency(commission.amount) }}</span>
                                                 </div>
                                             </div>
                                         </div>
                                         <p v-if="!calculationSummary.commissionDetails || Object.keys(calculationSummary.commissionDetails).length === 0" class="italic text-gray-500">No hay desglose detallado.</p>
                                     </div>
                                 </details>

                                 <div class="space-y-2 text-sm bg-green-50 p-4 rounded-lg border border-green-200 shadow-sm">
                                     <div v-if="currentUser.role === 'admin'" class="flex justify-between font-medium text-gray-600">
                                         <span>Comisión Bruta (100%):</span>
                                         <span class="font-mono">{{ formatCurrency(calculationSummary.totalCommission) }}</span>
                                     </div>
                                     
                                     <div v-if="currentUser.role === 'admin' || currentUser.is_manager" class="flex justify-between font-medium text-gray-700">
                                         <span>Comisión Equipo:</span>
                                         <span class="font-mono">{{ formatCurrency(calculationSummary.teamCommission) }}</span>
                                     </div>

                                     <div class="flex justify-between text-lg font-bold text-emerald-700 pt-2 border-t border-green-300 mt-2">
                                         <span>{{ currentUser.role === 'admin' || currentUser.is_manager ? 'Comisión Vendedor:' : 'Tu Comisión:' }}</span>
                                         <span class="font-mono">{{ formatCurrency(calculationSummary.userCommission) }}</span>
                                     </div>
                                 </div>
                             </section>
                             <section v-else class="bg-white p-6 shadow-sm sm:rounded-lg italic text-sm text-gray-500">
                                 El desglose de comisiones no está visible para tu rol.
                             </section>

                         </div> 
                     </div> 
                 </div> 
             </div> 
         </div> 
        
        <Modal :show="confirmingLockOffer" @close="closeModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    ¿Finalizar y bloquear esta oferta?
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Una vez finalizada, la oferta no podrá ser editada por nadie (excepto un administrador). Los precios y condiciones quedarán "congelados" permanentemente.
                </p>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal"> Cancelar </SecondaryButton>
                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': lockForm.processing }"
                        :disabled="lockForm.processing"
                        @click="lockOffer"
                    >
                        Finalizar Oferta
                    </DangerButton>
                </div>
            </div>
        </Modal>

        <Modal :show="confirmingSendEmail" @close="closeEmailModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Enviar Oferta por Email
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Introduce la dirección de correo electrónico a la que quieres enviar la oferta.
                </p>
                
                <div class="mt-6">
                    <InputLabel for="email" value="Email" />
                    <TextInput
                        id="email"
                        v-model="sendEmailForm.email"
                        type="email"
                        class="mt-1 block w-full"
                        placeholder="ejemplo@cliente.com"
                    />
                    <InputError :message="sendEmailForm.errors.email" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeEmailModal"> Cancelar </SecondaryButton>
                    <PrimaryButton
                        class="ms-3"
                        :class="{ 'opacity-25': sendEmailForm.processing }"
                        :disabled="sendEmailForm.processing"
                        @click="sendOfferByEmail"
                    >
                        <svg v-if="sendEmailForm.processing" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A8 8 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        {{ sendEmailForm.processing ? 'Enviando...' : 'Enviar Oferta' }}
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>