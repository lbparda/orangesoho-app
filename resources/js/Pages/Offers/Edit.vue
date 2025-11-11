<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue'; // Asegúrate que la ruta sea correcta
import { useOfferCalculations } from '@/composables/useOfferCalculations.js';

// --- 1. MODIFICACIÓN: Aceptar las nuevas props del controlador ---
const props = defineProps({
    offer: Object,
    packages: Array,
    // --- INICIO MODIFICACIÓN BENEFICIOS/SOLUCIONES ---
    allAddons: Array, // <-- ¡NUEVO PROP!
    initialSelectedBenefitIds: Array, // <-- ¡NUEVO PROP!
    initialSelectedDigitalAddonIds: Array, // <-- ¡NUEVO PROP!
    // --- FIN MODIFICACIÓN BENEFICIOS/SOLUCIONES ---
    discounts: Array,
    operators: Array,
    portabilityCommission: Number,
    additionalInternetAddons: Array,
    centralitaExtensions: Array,
    auth: Object,
    clients: Array,
    probabilityOptions: Array,
    portabilityExceptions: Array,
    fiberFeatures: Array,
    initialAdditionalInternetLines: Array, // <-- ¡AÑADIDO!
    initialMainIpFijaSelected: Boolean,  // <-- ¡AÑADIDO!
    initialMainFibraOroSelected: Boolean, // <-- AÑADIDO PARA FIBRA ORO (Línea 31)
});

const selectedClient = ref(null);
const isReassigningClient = ref(false);

const showReassignSelector = () => {
    isReassigningClient.value = true;
};

const formatDateForInput = (dateString) => {
    if (!dateString) return '';
    try {
        return dateString.substring(0, 10);
    } catch (e) {
        return '';
    }
};

// --- Funciones auxiliares para inicializar el estado del form ---
const getAddonId = (type) => props.offer.addons.find(a => a.type === type)?.id;
const getAddons = (type) => props.offer.addons.filter(a => a.type === type);
// hasAddon ya no se usa para la IP Fija principal

// --- 2. MODIFICACIÓN: Inicializar 'useForm' con las nuevas props ---
const form = useForm({
    client_id: props.offer.client_id,
    package_id: props.offer.package_id,
    lines: [], // Se rellena abajo
    internet_addon_id: getAddonId('internet'),
    // Usar la prop del controlador en lugar de 'getAddons'
    additional_internet_lines: props.initialAdditionalInternetLines, // <-- ¡CORREGIDO!
    centralita: {}, // Se rellena abajo
    tv_addons: getAddons('tv').map(a => a.id),
    // --- INICIO MODIFICACIÓN BENEFICIOS/SOLUCIONES ---
    digital_addons: props.initialSelectedDigitalAddonIds || [], // <-- ¡NUEVO!
    applied_benefit_ids: props.initialSelectedBenefitIds || [], // <-- ¡NUEVO!
    // --- FIN MODIFICACIÓN BENEFICIOS/SOLUCIONES ---
    // Usar la prop del controlador
    is_ip_fija_selected: props.initialMainIpFijaSelected, // <-- ¡CORREGIDO!
    is_fibra_oro_selected: props.initialMainFibraOroSelected, // <-- AÑADIDO (Línea 60)
    summary: {}, // Se recalcula
    probability: props.offer.probability,
    signing_date: formatDateForInput(props.offer.signing_date),
    processing_date: formatDateForInput(props.offer.processing_date),
});

const selectedPackageId = ref(props.offer.package_id); // Mantenemos este ref para reactividad del paquete

// --- CAMBIO 1: Inicializar 'lines' con la lógica de descuento ---
const lines = ref(props.offer.lines.map((line, index) => { // <-- Obtenemos el index
    const terminalPivotData = line.terminal_pivot;
    const terminalInfo = terminalPivotData?.terminal;

    // Leemos los valores de la BBDD
    const initialCostFromDB = parseFloat(line.initial_cost || 0);
    const monthlyCostFromDB = parseFloat(line.monthly_cost || 0);
    const initialDiscountFromDB = parseFloat(line.initial_cost_discount || 0);
    const monthlyDiscountFromDB = parseFloat(line.monthly_cost_discount || 0);

    let originalInitial = initialCostFromDB;
    let originalMonthly = monthlyCostFromDB;
    
    // Si es la línea 0, el precio guardado está descontado.
    // Reconstruimos el original sumando el descuento.
    if (index === 0 && terminalPivotData) {
        originalInitial = initialCostFromDB + initialDiscountFromDB;
        originalMonthly = monthlyCostFromDB + monthlyDiscountFromDB;
    }

    return {
        ...line,
        id: line.id || Date.now() + Math.random(),
        is_extra: !!line.is_extra,
        is_portability: !!line.is_portability,
        has_vap: !!line.has_vap,
        selected_brand: terminalInfo?.brand || null,
        selected_model_id: terminalInfo?.id || null,
        selected_duration: terminalPivotData?.duration_months || null,
        terminal_pivot: terminalPivotData,
        package_terminal_id: line.package_terminal_id,
        
        // Estos son los v-models (ya están descontados desde la BBDD)
        initial_cost: initialCostFromDB,
        monthly_cost: monthlyCostFromDB,

        // Guardamos los valores originales y de descuento para recálculos
        original_initial_cost: originalInitial,
        original_monthly_cost: originalMonthly,
        initial_cost_discount: initialDiscountFromDB,
        monthly_cost_discount: monthlyDiscountFromDB,
    };
}));
// --- FIN CAMBIO 1 ---

// --- 3. MODIFICACIÓN: Inicializar los 'ref' locales con las nuevas props ---
const selectedInternetAddonId = ref(form.internet_addon_id); 
// Usar la prop del controlador
const additionalInternetLines = ref(props.initialAdditionalInternetLines); // <-- ¡CORREGIDO!
const selectedTvAddonIds = ref(form.tv_addons); 
// --- INICIO MODIFICACIÓN BENEFICIOS/SOLUCIONES ---
const selectedDigitalAddonIds = ref(props.initialSelectedDigitalAddonIds || []);
const selectedBenefitIds = ref(props.initialSelectedBenefitIds || []);
// --- FIN MODIFICACIÓN BENEFICIOS/SOLUCIONES ---
// --- FIN MODIFICACIÓN ---

const isOperadoraAutomaticaSelected = ref(!!getAddonId('centralita_feature'));
// Modificado para ignorar las centralitas multisede
const initialOptionalCentralita = props.offer.addons.find(a => a.type === 'centralita' && !a.pivot.selected_centralita_id);
const selectedCentralitaId = ref(initialOptionalCentralita?.id || null);
const showCommissionDetails = ref(false);

// Computeds (sin cambios aquí, usan los refs de arriba)
const selectedPackage = computed(() => props.packages.find(p => p.id === selectedPackageId.value) || null);

// --- INICIO MODIFICACIÓN BENEFICIOS: Computeds de Lógica ---
const benefitLimit = computed(() => selectedPackage.value?.benefit_limit || 0);
const availableBenefits = computed(() => selectedPackage.value?.benefits || []);

// Agrupar beneficios por categoría para la UI
const benefitsEmpresa = computed(() => availableBenefits.value.filter(b => b.category === 'Empresa'));
const benefitsHogar = computed(() => availableBenefits.value.filter(b => b.category === 'Hogar'));

// Lógica de Reglas de Selección
const selectedBenefits = computed(() =>
    availableBenefits.value.filter(b => selectedBenefitIds.value.includes(b.id))
);

const totalSelectedCount = computed(() => selectedBenefitIds.value.length);
const hogarSelectedCount = computed(() =>
    selectedBenefits.value.filter(b => b.category === 'Hogar').length
);

// Regla 1: Límite total alcanzado
const isTotalLimitReached = computed(() => totalSelectedCount.value >= benefitLimit.value);

// Regla 2: Límite de "Hogar" alcanzado
const isHogarLimitReached = computed(() => hogarSelectedCount.value >= 1);
// --- FIN MODIFICACIÓN BENEFICIOS ---

const mobileAddonInfo = computed(() => selectedPackage.value?.addons.find(a => a.type === 'mobile_line'));
const internetAddonOptions = computed(() => selectedPackage.value?.addons.filter(a => a.type === 'internet') || []);
const tvAddonOptions = computed(() => selectedPackage.value?.addons.filter(a => a.type === 'tv') || []);
const centralitaAddonOptions = computed(() => selectedPackage.value?.addons.filter(a => a.type === 'centralita' && !a.pivot.is_included) || []);
const includedCentralita = computed(() => selectedPackage.value?.addons.find(a => a.type === 'centralita' && a.pivot.is_included));
const isCentralitaActive = computed(() => !!includedCentralita.value || !!selectedCentralitaId.value); // <-- Clave para la lógica
const autoIncludedExtension = computed(() => {
    if (!selectedCentralitaId.value) return null;
    const selected = centralitaAddonOptions.value.find(c => c.id === selectedCentralitaId.value);
    if (!selected) return null; const type = selected.name.split(' ')[1];
    return props.centralitaExtensions.find(ext => ext.name.includes(type));
});
const includedCentralitaExtensions = computed(() => isCentralitaActive.value ? selectedPackage.value?.addons.filter(a => a.type === 'centralita_extension' && a.pivot.is_included) : []);
const operadoraAutomaticaInfo = computed(() => selectedPackage.value?.addons.find(a => a.type === 'centralita_feature'));
const availableTerminals = computed(() => selectedPackage.value?.terminals || []);
const availableO2oDiscounts = computed(() => selectedPackage.value?.o2o_discounts || []);
const brandsForSelectedPackage = computed(() => [...new Set(availableTerminals.value.map(t => t.brand))]);
const availableAdditionalExtensions = computed(() => props.centralitaExtensions);

// --- INICIO MODIFICACIÓN SOLUCIONES ---
const digitalSolutionAddons = computed(() => {
    if (!props.allAddons) return [];
    // CORRECCIÓN: Incluir los tipos 'service' y 'software' para reflejar la lógica del controlador.
    return props.allAddons.filter(a => ['service', 'software'].includes(a.type));
});
// --- FIN MODIFICACIÓN SOLUCIONES ---


// Inicialización de cantidades de extensiones (sin cambios aquí)
const initialExtensionQuantities = {};
const savedExtensions = getAddons('centralita_extension');
const includedExtensionIds = includedCentralitaExtensions.value.map(ext => ext.id);
const additionalSavedExtensions = savedExtensions.filter(ext => !includedExtensionIds.includes(ext.id));
additionalSavedExtensions.forEach(ext => {
    let q = ext.pivot.quantity || 0;
    if (autoIncludedExtension.value && ext.id === autoIncludedExtension.value.id && !includedCentralita.value) q = Math.max(0, q - 1);
    if (q > 0) initialExtensionQuantities[ext.id] = q;
});
const centralitaExtensionQuantities = ref(initialExtensionQuantities);

// --- INICIO: CAMBIO (Línea 147) ---
// Pasamos el objeto 'form' completo al composable
// AÑADIMOS: ipFijaAddonInfo y fibraOroAddonInfo
// --- INICIO MODIFICACIÓN BENEFICIOS/SOLUCIONES ---
const { calculationSummary, ipFijaAddonInfo, fibraOroAddonInfo } = useOfferCalculations(
    props, 
    selectedPackageId, 
    lines, 
    selectedInternetAddonId, 
    additionalInternetLines, // <-- Pasamos el ref actualizado
    selectedCentralitaId, 
    centralitaExtensionQuantities, 
    isOperadoraAutomaticaSelected, 
    selectedTvAddonIds,
    selectedDigitalAddonIds, // <-- ¡NUEVO!
    form, // <-- Pasar el objeto form completo
    selectedBenefits // <-- ¡NUEVO!
);
// --- FIN MODIFICACIÓN BENEFICIOS/SOLUCIONES ---
// --- FIN: CAMBIO ---

const modelsByBrand = (brand) => availableTerminals.value.filter(t => t.brand === brand).filter((v, i, a) => a.findIndex(t => t.model === v.model) === i);
// Buscamos el pivot usando el ID del terminal y la duración
const findTerminalPivot = (line) => availableTerminals.value.find(t => t.id === line.selected_model_id && t.pivot.duration_months === line.selected_duration)?.pivot;

// --- CAMBIO 2: Actualizar 'assignTerminalPrices' con TU lógica ---
const assignTerminalPrices = (line) => {
    const pivot = findTerminalPivot(line);
    
    // 1. Obtenemos todos los valores del pivot
    const originalInitial = parseFloat(pivot?.initial_cost || 0);
    const originalMonthly = parseFloat(pivot?.monthly_cost || 0);
    const initialDiscount = parseFloat(pivot?.initial_cost_discount || 0);
    const monthlyDiscount = parseFloat(pivot?.monthly_cost_discount || 0);

    // 2. Guardamos los valores originales y de descuento en la línea (para el cálculo de comisiones)
    line.original_initial_cost = originalInitial;
    line.original_monthly_cost = originalMonthly;
    line.initial_cost_discount = initialDiscount;
    line.monthly_cost_discount = monthlyDiscount;

    // --- INICIO DE TU LÓGICA ---
    // Buscamos el índice de la línea actual
    const lineIndex = lines.value.findIndex(l => l.id === line.id);

    // 3. Aplicamos el descuento SÓLO a la PRIMERA línea (index 0)
    if (lineIndex === 0) {
        // Estos son los v-model de los inputs, así que se actualizarán
        line.initial_cost = originalInitial - initialDiscount;
        line.monthly_cost = originalMonthly - monthlyDiscount;
    } else {
        // Todas las demás líneas (principales o extras) usan el precio normal
        line.initial_cost = originalInitial;
        line.monthly_cost = originalMonthly;
    }
    // --- FIN DE TU LÓGICA ---

    // 4. Asignamos el resto de datos
    line.terminal_pivot = pivot;
    line.package_terminal_id = pivot?.id || null; // Guardamos el ID de la tabla pivote
};
// --- FIN FUNCIÓN MODIFICADA ---


const copyPreviousLine = (line, index) => {
    if (index <= 0 || !lines.value[index - 1]) return;
    const prev = lines.value[index - 1];
    line.is_portability = prev.is_portability;
    line.source_operator = prev.source_operator;
    line.has_vap = prev.has_vap;
    line.o2o_discount_id = prev.o2o_discount_id;
    line.selected_brand = prev.selected_brand;
    line.selected_model_id = prev.selected_model_id;
    line.selected_duration = prev.selected_duration;
    assignTerminalPrices(line); // Asigna pivot, costs y package_terminal_id (y ahora descuentos)
};

// --- INICIO CÓDIGO CORREGIDO (Reactividad) ---
// --- CAMBIO 3: Actualizar 'addLine' (para nuevas líneas) ---
const addLine = () => {
    const newLine = { 
        id: Date.now(), 
        is_extra: true, 
        is_portability: false, 
        phone_number: '', 
        source_operator: null, 
        has_vap: false, 
        o2o_discount_id: null, 
        selected_brand: null, 
        selected_model_id: null, 
        selected_duration: null, 
        terminal_pivot: null, 
        package_terminal_id: null, 
        initial_cost: 0, 
        monthly_cost: 0,
        // --- LÍNEAS AÑADIDAS ---
        original_initial_cost: 0,
        original_monthly_cost: 0,
        initial_cost_discount: 0,
        monthly_cost_discount: 0
        // --- FIN LÍNEAS AÑADIDAS ---
    };
    lines.value.push(newLine); 
    addWatchersToLine(lines.value[lines.value.length - 1]); // Apuntar al objeto reactivo
};
// --- FIN CAMBIO 3 ---
const removeLine = (index) => { if (lines.value[index]?.is_extra) lines.value.splice(index, 1); };

// Watcher para líneas de internet adicionales
const addWatchersToAdditionalLine = (line) => {
    watch(() => line.selected_centralita_id, (isCentralita) => {
        if (isCentralita) { // Si hay ID de centralita, marcar IP Fija
            line.has_ip_fija = true;
        } else { // Si se quita la centralita, desmarcar IP Fija
            // No desmarcamos automáticamente
              line.has_ip_fija = false;
        }
    });
};

const addInternetLine = () => {
    const newLine = {
        id: Date.now(),
        addon_id: null,
        has_ip_fija: false,
        has_fibra_oro: false, // <-- AÑADIDO (Línea 250)
        selected_centralita_id: null
    };
    additionalInternetLines.value.push(newLine);
    addWatchersToAdditionalLine(additionalInternetLines.value[additionalInternetLines.value.length - 1]); // Apuntar al objeto reactivo
};
// --- FIN CÓDIGO CORREGIDO ---

const removeInternetLine = (index) => additionalInternetLines.value.splice(index, 1);
const getDurationsForModel = (line) => [...new Set(availableTerminals.value.filter(t => t.id === line.selected_model_id).map(t => t.pivot.duration_months))].sort((a, b) => a - b);
const getO2oDiscountsForLine = (line, index) => {
    if (!mobileAddonInfo.value) return availableO2oDiscounts.value;
    const limit = mobileAddonInfo.value.pivot.line_limit ?? 0;
    const extrasBefore = lines.value.slice(0, index).filter(l => l.is_extra).length;
    return line.is_extra && extrasBefore < limit ? availableO2oDiscounts.value.filter(d => (parseFloat(d.total_discount_amount) / parseFloat(d.duration_months)) <= 1) : availableO2oDiscounts.value;
};

// --- CAMBIO 4: Actualizar 'saveOffer' para enviar los descuentos ---
const saveOffer = () => {
    try {
        let finalExt = [];
        for (const [id, qty] of Object.entries(centralitaExtensionQuantities.value)) if (qty > 0) finalExt.push({ addon_id: parseInt(id), quantity: qty });
        if (!includedCentralita.value && autoIncludedExtension.value) { const e = finalExt.find(x => x.addon_id == autoIncludedExtension.value.id); if (e) e.quantity++; else finalExt.push({ addon_id: autoIncludedExtension.value.id, quantity: 1 }); }

        form.lines = lines.value.map(l => ({
            is_extra: l.is_extra,
            is_portability: l.is_portability,
            phone_number: l.phone_number,
            source_operator: l.source_operator,
            has_vap: l.has_vap,
            o2o_discount_id: l.o2o_discount_id,
            terminal_pivot_id: l.package_terminal_id, 
            initial_cost: l.initial_cost, // Ya está descontado si es línea 0
            monthly_cost: l.monthly_cost, // Ya está descontado si es línea 0
            // ¡AÑADIMOS LOS DESCUENTOS AL GUARDAR!
            initial_cost_discount: l.initial_cost_discount,
            monthly_cost_discount: l.monthly_cost_discount,
        }));
        // --- FIN CAMBIO 4 ---

        form.internet_addon_id = selectedInternetAddonId.value;
        // --- 4. MODIFICACIÓN: Usar el ref local para guardar ---
        form.additional_internet_lines = additionalInternetLines.value // <-- ¡CORREGIDO!
            .filter(l => l.addon_id)
            .map(l => ({ 
                addon_id: l.addon_id, 
                has_ip_fija: l.has_ip_fija,
                has_fibra_oro: l.has_fibra_oro, // <-- AÑADIDO (Línea 303)
                selected_centralita_id: l.selected_centralita_id 
            }));
        // --- FIN MODIFICACIÓN ---
        form.centralita = { id: selectedCentralitaId.value || includedCentralita.value?.id || null, operadora_automatica_selected: isOperadoraAutomaticaSelected.value, operadora_automatica_id: operadoraAutomaticaInfo.value?.id || null, extensions: finalExt };
        form.tv_addons = selectedTvAddonIds.value;
        
        // --- INICIO MODIFICACIÓN BENEFICIOS/SOLUCIONES ---
        form.digital_addons = selectedDigitalAddonIds.value;
        form.applied_benefit_ids = selectedBenefitIds.value;
        // --- FIN MODIFICACIÓN BENEFICIOS/SOLUCIONES ---
        
        form.summary = calculationSummary.value;

        form.put(route('offers.update', props.offer.id), { onSuccess: () => alert('¡Oferta actualizada!'), onError: (e) => { console.error(e); alert('Error al actualizar.'); } });
    } catch (e) { console.error(e); alert("Error inesperado al preparar la actualización."); }
};

const addWatchersToLine = (line) => {
    watch(() => line.is_portability, (isPort, old) => { if (old && !isPort) { line.has_vap = false; line.selected_brand = null; line.selected_model_id = null; line.selected_duration = null; assignTerminalPrices(line); line.source_operator = null; } });
    watch(() => line.has_vap, (hasVap, old) => { if (old && !hasVap) { line.selected_brand = null; line.selected_model_id = null; line.selected_duration = null; assignTerminalPrices(line); } });
    watch(() => [line.selected_model_id, line.selected_duration], () => assignTerminalPrices(line));
};

// --- APLICAR WATCHERS A LÍNEAS CARGADAS ---
// Esto aplica los watchers a los datos que SÍ se cargaron de las props
additionalInternetLines.value.forEach(addWatchersToAdditionalLine);
lines.value.forEach(addWatchersToLine);
// --- FIN ---

watch(
    () => form.client_id,
    (newClientId) => {
        if (newClientId) {
            selectedClient.value = props.clients.find(c => c.id === Number(newClientId)) || null;
            isReassigningClient.value = false;
        } else {
            selectedClient.value = null;
        }
    },
    { immediate: true }
);

// Sincronizar refs locales con cambios en el form
watch(() => form.internet_addon_id, (newVal) => { selectedInternetAddonId.value = newVal; });
watch(() => form.tv_addons, (newVal) => { selectedTvAddonIds.value = newVal; }, { deep: true });
// Sincronizar el ref local con el form (si cambia por otra razón)
watch(() => form.additional_internet_lines, (newVal) => { 
    additionalInternetLines.value = newVal; 
}, { deep: true });

// --- INICIO MODIFICACIÓN BENEFICIOS/SOLUCIONES ---
// Sincronizar nuevos refs con el form
watch(() => form.digital_addons, (newVal) => { 
    selectedDigitalAddonIds.value = newVal; 
}, { deep: true });

watch(() => form.applied_benefit_ids, (newVal) => { 
    selectedBenefitIds.value = newVal; 
}, { deep: true });
// --- FIN MODIFICACIÓN BENEFICIOS/SOLUCIONES ---


// Watcher para marcar/desmarcar IP Fija según la centralita (para la línea PRINCIPAL)
watch(isCentralitaActive, (isActive) => {
    if (isActive) {
        form.is_ip_fija_selected = true;
    } else {
        form.is_ip_fija_selected = false;
    }
}, { immediate: true }); 

</script>

<template>
    <Head title="Editar Oferta" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                 <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Oferta #{{ offer.id }}</h2>
                 <Link :href="route('offers.index')">
                     <SecondaryButton>Volver a la Lista</SecondaryButton>
                 </Link>
             </div>
        </template>

        <div class="flex flex-col md:flex-row">

            <div class="w-full md:w-4/5 p-4 sm:p-6 lg:p-8">
                <div class="space-y-8">

                    <div class="bg-white shadow-sm sm:rounded-lg p-8">
                            <div class="mb-8">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Cliente</label>

                                <div v-if="!isReassigningClient && selectedClient">
                                    <div class="p-4 border rounded-md bg-gray-50 shadow-sm">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-1 text-sm">
                                            <p><strong>Nombre:</strong> {{ selectedClient.name }}</p>
                                            <p><strong>CIF/NIF:</strong> {{ selectedClient.cif_nif }}</p>
                                            <p><strong>Email:</strong> {{ selectedClient.email || 'No disponible' }}</p>
                                            <p><strong>Teléfono:</strong> {{ selectedClient.phone || 'No disponible' }}</p>
                                            <p class="col-span-2"><strong>Dirección:</strong> {{ selectedClient.address || 'No disponible' }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <SecondaryButton type="button" @click="showReassignSelector">
                                            Reasignar Cliente
                                        </SecondaryButton>
                                    </div>
                                </div>

                                <div v-else>
                                    <select v-model="form.client_id" id="client" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }} ({{ client.cif_nif }})</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.client_id" />
                                </div>
                            </div>
                            <div>
                                <label for="package" class="block text-sm font-medium text-gray-700 mb-2">Paquete Base</label>
                                <input type="text" :value="selectedPackage?.name" id="package" disabled class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-100 cursor-not-allowed">
                            </div>
                    </div>

                    <div v-if="selectedPackage" class="bg-white shadow-sm sm:rounded-lg p-8 space-y-6">

                        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="probability" class="block text-sm font-medium text-gray-700">Probabilidad (%)</label>
                                <select v-model="form.probability" id="probability" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option :value="null">-- Selecciona --</option>
                                    <option v-for="option in probabilityOptions" :key="option" :value="option">{{ option }}%</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.probability" />
                            </div>
                            <div>
                                <label for="signing_date" class="block text-sm font-medium text-gray-700">Fecha Firma</label>
                                <input type="date" v-model="form.signing_date" id="signing_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <InputError class="mt-2" :message="form.errors.signing_date" />
                            </div>
                            <div>
                                <label for="processing_date" class="block text-sm font-medium text-gray-700">Fecha Tramitación</label>
                                <input type="date" v-model="form.processing_date" id="processing_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <InputError class="mt-2" :message="form.errors.processing_date" />
                            </div>
                        </section>

                       <div v-if="internetAddonOptions.length > 0 || (props.fiberFeatures && props.fiberFeatures.length > 0)" class="p-6 bg-slate-50 rounded-lg">
                           <label class="block text-sm font-medium text-gray-700 mb-2">Fibra Principal</label>
                           <div v-if="internetAddonOptions.length > 0" class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4 mt-1">
                               <label v-for="addon in internetAddonOptions" :key="addon.id"
                                   :class="['flex-1 text-center px-4 py-3 rounded-md border cursor-pointer transition', { 'bg-indigo-600 text-white border-indigo-600 shadow-lg': selectedInternetAddonId === addon.id, 'bg-white border-gray-300 hover:bg-gray-50': selectedInternetAddonId !== addon.id }]">
                                   <input type="radio" :value="addon.id" v-model="selectedInternetAddonId" @change="form.internet_addon_id = $event.target.value" class="sr-only">
                                   <span class="block font-semibold">{{ addon.name }}</span>
                                   <span class="block text-xs mt-1" v-if="parseFloat(addon.pivot.price) > 0">+{{ parseFloat(addon.pivot.price).toFixed(2) }}€/mes</span>
                               </label>
                           </div>
                           
                           <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2">
                               <div v-if="ipFijaAddonInfo"> <label class="flex items-center">
                                       <Checkbox v-model:checked="form.is_ip_fija_selected" />
                                       <span class="ml-2 text-sm text-gray-600">
                                           Añadir IP Fija ({{ ipFijaAddonInfo.price }}€)
                                       </span>
                                   </label>
                                   <p class="text-xs text-gray-500 ml-6">
                                       Gratis si se incluye Centralita.
                                   </p>
                               </div>

                               <div v-if="fibraOroAddonInfo"> <label class="flex items-center">
                                       <Checkbox v-model:checked="form.is_fibra_oro_selected" />
                                       <span class="ml-2 text-sm text-gray-600">
                                           Añadir Fibra Oro ({{ fibraOroAddonInfo.price }}€)
                                       </span>
                                   </label>
                               </div>
                               </div>
                           </div>


                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div v-if="tvAddonOptions.length > 0" class="space-y-4 p-6 bg-slate-50 rounded-lg h-full">
                                <h3 class="text-lg font-semibold text-gray-800">Televisión</h3>
                                <div class="space-y-2">
                                    <div v-for="addon in tvAddonOptions" :key="addon.id" class="flex items-center">
                                        <input :id="`tv_addon_${addon.id}`" :value="addon.id" v-model="selectedTvAddonIds" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <label :for="`tv_addon_${addon.id}`" class="ml-3 block text-sm text-gray-900">
                                            {{ addon.name }} <span v-if="parseFloat(addon.pivot.price) > 0" class="text-gray-600">(+{{ parseFloat(addon.pivot.price).toFixed(2) }}€)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div v-if="centralitaAddonOptions.length > 0 || includedCentralita" class="space-y-4 p-6 bg-slate-50 rounded-lg h-full">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">Centralita</h3>
                                <div v-if="includedCentralita" class="p-4 bg-green-100 border border-green-300 rounded-md text-center">
                                    <p class="font-semibold text-green-800">✅ {{ includedCentralita.name }} Incluida</p>
                                </div>
                                <div v-else-if="centralitaAddonOptions.length > 0">
                                    <label for="centralita_optional" class="block text-sm font-medium text-gray-700">Añadir Centralita</label>
                                    <select v-model="selectedCentralitaId" id="centralita_optional" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option :value="null">No añadir</option>
                                        <option v-for="centralita in centralitaAddonOptions" :key="centralita.id" :value="centralita.id">{{ centralita.name }} (+{{ parseFloat(centralita.pivot.price).toFixed(2) }}€)</option>
                                    </select>
                                </div>
                                <div v-if="isCentralitaActive" class="space-y-4 pt-4 border-t border-dashed">
                                    <div v-if="operadoraAutomaticaInfo">
                                        <div v-if="operadoraAutomaticaInfo.pivot.is_included" class="p-2 bg-gray-100 rounded-md text-sm text-gray-800">✅ Op. Automática Incluida</div>
                                        <div v-else class="flex items-center">
                                            <input v-model="isOperadoraAutomaticaSelected" id="operadora_automatica_cb" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <label for="operadora_automatica_cb" class="ml-2 block text-sm text-gray-900">Añadir Op. Automática (+{{ parseFloat(operadoraAutomaticaInfo.pivot.price).toFixed(2) }}€)</label>
                                        </div>
                                    </div>
                                    <div class="pt-2">
                                        <div v-if="includedCentralitaExtensions.length > 0" class="mb-4 space-y-2">
                                            <p class="text-sm font-medium text-gray-700">Ext. Incluidas:</p>
                                            <div v-for="ext in includedCentralitaExtensions" :key="`inc_${ext.id}`" class="p-2 bg-gray-100 rounded-md text-sm text-gray-800">✅ {{ ext.pivot.included_quantity }}x {{ ext.name }}</div>
                                        </div>
                                        <div v-if="autoIncludedExtension && !includedCentralita" class="mb-4">
                                            <p class="text-sm font-medium text-gray-700">Ext. Incluida:</p>
                                            <div class="p-2 bg-gray-100 rounded-md text-sm text-gray-800">✅ 1x {{ autoIncludedExtension.name }}</div>
                                        </div>
                                        <p class="text-sm font-medium text-gray-700">Ext. Adicionales:</p>
                                        <div v-for="extension in availableAdditionalExtensions" :key="extension.id" class="flex items-center justify-between mt-2">
                                            <label :for="`ext_add_${extension.id}`" class="text-sm text-gray-800">{{ extension.name }} (+{{ parseFloat(extension.price).toFixed(2) }}€)</label>
                                            <input :id="`ext_add_${extension.id}`" type="number" min="0" v-model.number="centralitaExtensionQuantities[extension.id]" class="w-20 rounded-md border-gray-300 shadow-sm text-center focus:border-indigo-500 focus:ring-indigo-500" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4 p-6 bg-slate-50 rounded-lg h-full">
                                <h3 class="text-lg font-semibold text-gray-800">Internet Adicional</h3>
                                <div v-for="(line, index) in additionalInternetLines" :key="line.id" class="p-3 border rounded-lg bg-blue-50 border-blue-200 space-y-2"> 
                                    <div class="flex-1">
                                        <div class="flex justify-between items-center mb-1">
                                            <label class="block text-xs font-medium text-gray-500">Línea Adicional {{ index + 1 }}</label>
                                            <button @click="removeInternetLine(index)" type="button" class="text-red-500 hover:text-red-700"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg></button>
                                        </div>
                                        <select v-model="line.addon_id" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option :value="null" disabled>-- Selecciona --</option>
                                            <option v-for="addon in additionalInternetAddons" :key="addon.id" :value="addon.id">{{ addon.name }} (+{{ parseFloat(addon.price).toFixed(2) }}€)</option>
                                        </select>
                                    </div>

                                    <div v-if="line.addon_id && centralitaAddonOptions.length > 0">
                                         <label :for="`multi_centralita_${line.id}`" class="block text-xs font-medium text-gray-500">Centralita Multisede</label>
                                         <select v-model="line.selected_centralita_id" :id="`multi_centralita_${line.id}`" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                             <option :value="null">-- Sin Centralita Multisede --</option>
                                             <option v-for="centralita in centralitaAddonOptions" :key="centralita.id" :value="centralita.id">
                                                 {{ centralita.name }} (+{{ parseFloat(centralita.pivot.price).toFixed(2) }}€)
                                             </option>
                                         </select>
                                    </div>
                                    
                                    <div v-if="line.addon_id" class="mt-2 flex flex-wrap gap-x-6 gap-y-1">
                                        <div v-if="ipFijaAddonInfo">
                                            <label class="flex items-center">
                                                <Checkbox v-model:checked="line.has_ip_fija" />
                                                <span class="ml-2 text-xs text-gray-600">
                                                    Añadir IP Fija (+{{ ipFijaAddonInfo.price }}€)
                                                </span>
                                            </label>
                                        </div>

                                        <div v-if="fibraOroAddonInfo">
                                            <label class="flex items-center">
                                                <Checkbox v-model:checked="line.has_fibra_oro" />
                                                <span class="ml-2 text-xs text-gray-600">
                                                    Añadir Fibra Oro (+{{ fibraOroAddonInfo.price }}€)
                                                </span>
                                            </label>
                                        </div>
                                        </div>
                                    </div>
                                <PrimaryButton @click="addInternetLine" type="button" class="w-full justify-center">Añadir Internet</PrimaryButton>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedPackage && digitalSolutionAddons.length > 0" class="bg-white shadow-sm sm:rounded-lg p-8 space-y-6">
                        <h3 class="text-lg font-semibold text-gray-800 text-center">Soluciones Digitales</h3>
                        <p class="text-sm text-gray-600 text-center -mt-4 mb-4">
                            Selecciona los productos. Si has elegido el beneficio correspondiente, el descuento se aplicará en el resumen.
                        </p>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="addon in digitalSolutionAddons" :key="addon.id" class="flex items-center p-3 border rounded-md hover:bg-gray-50">
                                <input
                                    :id="'digital_addon_' + addon.id"
                                    type="checkbox"
                                    :value="addon.id"
                                    v-model="selectedDigitalAddonIds"
                                    class="h-4 w-4 text-orange-600 border-gray-300 rounded focus:ring-orange-500"
                                />
                                <label :for="'digital_addon_' + addon.id" class="ml-3 block text-sm font-medium text-gray-700">
                                    {{ addon.name }}
                                    <span class="text-xs text-gray-500">({{ parseFloat(addon.price).toFixed(2) }}€/mes)</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div v-if="availableBenefits.length > 0" class="bg-white shadow-sm sm:rounded-lg p-8 -my-6 border-t border-b">
                        
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">
                            Beneficios a Elegir (Selecciona {{ benefitLimit }})
                        </h3>
                        <p v-if="isTotalLimitReached" class="text-sm font-medium text-red-600">
                            Has alcanzado el límite de {{ benefitLimit }} beneficios.
                        </p>
                        <p v-else class="text-sm text-gray-500">
                            Te quedan {{ benefitLimit - totalSelectedCount }} por elegir.
                        </p>

                        <div class="mt-4">
                            <h4 class="font-semibold text-gray-700 border-b pb-2">Categoría Empresa</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-3">
                                <label v-for="benefit in benefitsEmpresa" :key="benefit.id" class="flex items-center p-3 border rounded-md hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :value="benefit.id"
                                        v-model="selectedBenefitIds"
                                        :disabled="!selectedBenefitIds.includes(benefit.id) && isTotalLimitReached"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 disabled:opacity-50"
                                    />
                                    <span class="ml-3 text-sm text-gray-700">{{ benefit.description }}</span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-6">
                            <h4 class="font-semibold text-gray-700 border-b pb-2">Categoría Hogar</h4>
                            <p class="text-xs text-gray-500 mt-1">(Máximo 1 beneficio de esta categoría)</p>
                            <p v-if="isHogarLimitReached && !isTotalLimitReached" class="text-sm font-medium text-yellow-600">
                                Has alcanzado el límite de 1 beneficio de Hogar.
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-3">
                                <label v-for="benefit in benefitsHogar" :key="benefit.id" class="flex items-center p-3 border rounded-md hover:bg-gray-50">
                                    <input
                                        type="checkbox"
                                        :value="benefit.id"
                                        v-model="selectedBenefitIds"
                                        :disabled="(!selectedBenefitIds.includes(benefit.id) && isTotalLimitReached) || (!selectedBenefitIds.includes(benefit.id) && isHogarLimitReached)"
                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 disabled:opacity-50"
                                    />
                                    <span class="ml-3 text-sm text-gray-700">{{ benefit.description }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div v-if="selectedPackage" class="bg-white shadow-sm sm:rounded-lg p-8 space-y-6">
                        <h3 class="text-lg font-semibold text-gray-800 text-center">Líneas Móviles</h3>
                        <div v-for="(line, index) in lines" :key="line.id" class="p-6 border rounded-lg max-w-full mx-auto" :class="{'bg-gray-50 border-gray-200': !line.is_extra, 'bg-green-50 border-green-200': line.is_extra}">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center mb-4">
                                <div class="md:col-span-2 flex justify-between items-center">
                                    <span class="font-medium text-gray-700">
                                        {{ line.is_extra ? `Línea Adicional ${index + 1 - lines.filter(l => !l.is_extra).length}` : `Línea Principal ${index + 1}` }}
                                    </span>
                                     <div class="flex space-x-2">
                                         <button
                                             v-if="index > 0"
                                             @click="copyPreviousLine(line, index)"
                                             type="button"
                                             class="text-blue-600 hover:text-blue-800 p-1 rounded-full hover:bg-blue-100"
                                             title="Copiar configuración de la línea anterior"
                                         >
                                             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                             </svg>
                                         </button>
                                         <button
                                             v-if="line.is_extra"
                                             @click="removeLine(index)"
                                             type="button"
                                             class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-100"
                                         >
                                             <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                 <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                             </svg>
                                         </button>
                                     </div>
                                </div>
                                <div class="md:col-span-4">
                                    <label class="block text-xs font-medium text-gray-500">Nº Teléfono</label>
                                    <input v-model="line.phone_number" type="tel" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ej: 612345678">
                                </div>
                                <div class="md:col-span-2 flex items-end pb-1">
                                    <div class="flex items-center h-full">
                                        <input v-model="line.is_portability" :id="`portability_${line.id}`" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <label :for="`portability_${line.id}`" class="ml-2 block text-sm text-gray-900">Portabilidad</label>
                                    </div>
                                </div>
                            </div>
                            <div v-if="line.is_portability" class="space-y-4 border-t pt-4 mt-4">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                                    <div class="md:col-span-4">
                                        <label class="block text-xs font-medium text-gray-500">Operador Origen</label>
                                        <select v-model="line.source_operator" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option :value="null" disabled>-- Selecciona --</option>
                                            <option v-for="op in operators" :key="op" :value="op">{{ op }}</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2 flex items-end pb-1">
                                        <div class="flex items-center h-full">
                                            <input v-model="line.has_vap" :id="`vap_${line.id}`" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <label :for="`vap_${line.id}`" class="ml-2 block text-sm text-gray-900">con VAP</label>
                                        </div>
                                    </div>
                                    <div class="md:col-span-4">
                                        <label class="block text-xs font-medium text-gray-500">Descuento O2O</label>
                                        <select v-model="line.o2o_discount_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option :value="null">-- Sin subvención --</option>
                                            <option v-for="o2o in getO2oDiscountsForLine(line, index)" :key="o2o.id" :value="o2o.id">{{ o2o.name }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div v-if="line.has_vap" class="space-y-4 pt-4 border-t border-dashed">
                                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                                        <div class="md:col-span-2"><label class="block text-xs font-medium text-gray-500">Marca</label><select v-model="line.selected_brand" @change="line.selected_model_id = null; line.selected_duration = null; assignTerminalPrices(line);" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"><option :value="null">-- Marca --</option><option v-for="brand in brandsForSelectedPackage" :key="brand" :value="brand">{{ brand }}</option></select></div>
                                        <div class="md:col-span-3"><label class="block text-xs font-medium text-gray-500">Modelo</label><select v-model="line.selected_model_id" @change="line.selected_duration = null; assignTerminalPrices(line);" :disabled="!line.selected_brand" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"><option :value="null">-- Modelo --</option><option v-for="terminal in modelsByBrand(line.selected_brand)" :key="terminal.id" :value="terminal.id">{{ terminal.model }}</option></select></div>
                                        <div class="md:col-span-2"><label class="block text-xs font-medium text-gray-500">Meses</label><select v-model="line.selected_duration" @change="assignTerminalPrices(line)" :disabled="!line.selected_model_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"><option :value="null">-- Meses --</option><option v-for="duration in getDurationsForModel(line)" :key="duration" :value="duration">{{ duration }} meses</option></select></div>
                                        <div class="md:col-span-2"><label class="block text-xs font-medium text-gray-500">Pago Inicial (€)</label><input v-model.number="line.initial_cost" type="number" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                                        <div class="md:col-span-2"><label class="block text-xs font-medium text-gray-500">Cuota Mensual (€)</label><input v-model.number="line.monthly_cost" type="number" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                         <div class="flex justify-center pt-4">
                             <PrimaryButton @click="addLine" type="button">Añadir Línea Móvil Adicional</PrimaryButton>
                         </div>
                    </div>

                    <div v-if="selectedPackage" class="mt-10 flex justify-center">
                        <PrimaryButton @click="saveOffer" :disabled="form.processing">
                            Actualizar Oferta
                        </PrimaryButton>
                    </div>

                    <div v-if="!selectedPackage" class="text-center text-gray-500 mt-10 p-8 bg-white rounded-lg shadow-sm">
                        Cargando datos de la oferta...
                    </div>
                </div>
            </div>

            <div class="w-full md:w-1/5 bg-gray-50 p-6 min-h-screen border-l border-gray-200">
                <div class="sticky top-10 space-y-6">
                    <div class="p-6 bg-white rounded-lg shadow-sm space-y-3">
                        <h2 class="text-xl font-semibold text-gray-800 text-center">Resumen de la Oferta</h2>
                         <div v-if="calculationSummary.summaryBreakdown && calculationSummary.summaryBreakdown.length > 0" class="space-y-2 border-t pt-4 mt-4">
                             <div v-for="(item, index) in calculationSummary.summaryBreakdown" :key="'sum-'+index" class="flex justify-between text-sm" :class="{'text-gray-700': item.price >= 0, 'text-red-600': item.price < 0}">
                                 <span>{{ item.description }}</span>
                                 <span class="font-medium">{{ item.price >= 0 ? '+' : '' }}{{ item.price.toFixed(2) }}€</span>
                             </div>
                         </div>
                        <div class="border-t pt-4 mt-4 space-y-3">
                            <div class="flex justify-between text-lg font-bold text-gray-800">
                                <span>Pago Inicial Total:</span>
                                <span>{{ calculationSummary.totalInitialPayment }}€</span>
                            </div>
                            <div class="flex justify-between text-3xl font-extrabold text-gray-900 items-baseline">
                                <span>Precio Final Mensual:</span>
                                <span>{{ calculationSummary.finalPrice }}<span class="text-lg font-medium text-gray-600">€/mes</span></span>
                            </div>
                        </div>
                    </div>
                     <div class="p-6 bg-white rounded-lg shadow-sm space-y-3">
                        <h2 class="text-xl font-semibold text-gray-800 text-center">Resumen Comisión</h2>
                        <div class="border-t pt-4 mt-4 space-y-2">
                             <p v-if="$page.props.auth.user.role === 'admin' || $page.props.auth.user.role === 'team_lead'" class="text-md text-gray-500 text-center">
                                 Comisión Bruta (100%): {{ calculationSummary.totalCommission }}€
                             </p>
                             <p v-if="$page.props.auth.user.role === 'team_lead'" class="text-lg text-gray-600 text-center">
                                 Comisión Equipo ({{ auth.user?.team?.commission_percentage || 0 }}%): {{ calculationSummary.teamCommission }}€
                             </p>
                             <p class_="text-xl font-bold text-emerald-600 text-center mt-2">
                                 Tu Comisión: {{ calculationSummary.userCommission }}€
                             </p>
                             <div class="text-center pt-2">
                                 <SecondaryButton @click="showCommissionDetails = !showCommissionDetails">
                                     {{ showCommissionDetails ? 'Ocultar Detalle' : 'Ver Detalle' }}
                                 </SecondaryButton>
                             </div>
                             <div v-if="showCommissionDetails" class="mt-4 border-t pt-4 text-left">
                                 <h4 class="text-md font-semibold text-gray-700 mb-2">Desglose de Comisiones</h4>
                                 <div v-for="(items, category) in calculationSummary.commissionDetails" :key="'com-'+category" class="mb-3">
                                     <h5 class="font-bold text-sm text-gray-600">{{ category }}</h5>
                                     <ul class="list-disc list-inside text-xs text-gray-600 space-y-1 mt-1">
                                         <li v-for="(item, index) in items" :key="'com-item-'+index" class="flex justify-between">
                                             <span>{{ item.description }}</span>
                                             <span class="font-mono" :class="{'text-red-500': item.amount < 0}">{{ item.amount.toFixed(2) }}€</span>
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