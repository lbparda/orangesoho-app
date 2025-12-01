<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { useOfferCalculations } from '@/composables/useOfferCalculations.js';

const props = defineProps({
    clients: Array,
    packages: Array,
    allAddons: Array,
    discounts: Array,
    operators: Array,
    portabilityCommission: { type: Number, default: 0 },
    additionalInternetAddons: Array,
    centralitaExtensions: Array,
    auth: Object,
    probabilityOptions: Array,
    portabilityExceptions: Array,
    fiberFeatures: Array,
    offer: { type: Object, default: null },
    initialClientId: [Number, String, null],
    initialAdditionalInternetLines: { type: Array, default: () => [] },
    initialMainIpFijaSelected: { type: Boolean, default: false },
    initialMainFibraOroSelected: { type: Boolean, default: false },
    initialDdiQuantity: { type: Number, default: 0 },
    initialSelectedDigitalAddonIds: { type: Array, default: () => [] },
    initialSelectedBenefitIds: { type: Array, default: () => [] },
});

// --- 1. Helpers y Estado Inicial ---
const isEditing = computed(() => !!props.offer);

const formatDateForInput = (dateString) => dateString ? dateString.substring(0, 10) : '';
const getAddonId = (type) => props.offer?.addons.find(a => a.type === type && a.pivot.selected_centralita_id === null)?.id || null;
const getAddonsIds = (type) => props.offer?.addons.filter(a => a.type === type).map(a => a.id) || [];

const form = useForm({
    client_id: props.offer?.client_id || props.initialClientId || null,
    package_id: props.offer?.package_id || null,
    lines: [],
    internet_addon_id: props.offer ? getAddonId('internet') : null,
    additional_internet_lines: [],
    centralita: {},
    tv_addons: getAddonsIds('tv'),
    digital_addons: props.initialSelectedDigitalAddonIds,
    applied_benefit_ids: props.initialSelectedBenefitIds,
    is_ip_fija_selected: props.initialMainIpFijaSelected,
    is_fibra_oro_selected: props.initialMainFibraOroSelected,
    ddi_quantity: props.initialDdiQuantity,
    summary: {},
    probability: props.offer?.probability || null,
    signing_date: formatDateForInput(props.offer?.signing_date),
    processing_date: formatDateForInput(props.offer?.processing_date),
});

const selectedPackageId = ref(form.package_id);
const selectedClient = ref(null);
const isReassigningClient = ref(false);
const selectedInternetAddonId = ref(form.internet_addon_id);
const selectedTvAddonIds = ref(form.tv_addons);
const selectedDigitalAddonIds = ref(form.digital_addons);
const selectedBenefitIds = ref(form.applied_benefit_ids);
const showCommissionDetails = ref(false);

// --- 2. Lógica de Líneas Móviles ---
const createNewLine = (isExtra = false) => ({
    id: Date.now() + Math.random(),
    is_extra: isExtra,
    is_portability: false,
    phone_number: '', source_operator: null, has_vap: false,
    o2o_discount_id: null, selected_brand: null, selected_model_id: null,
    selected_duration: null, terminal_pivot: null, package_terminal_id: null,
    initial_cost: 0, monthly_cost: 0,
    original_initial_cost: 0, original_monthly_cost: 0,
    initial_cost_discount: 0, monthly_cost_discount: 0,
});

const initLines = () => {
    if (!isEditing.value || !props.offer) return [];
    return props.offer.lines.map((line, index) => {
        const pivot = line.terminal_pivot;
        const terminal = pivot?.terminal;
        const initialCost = parseFloat(line.initial_cost || 0);
        const monthlyCost = parseFloat(line.monthly_cost || 0);
        const initialDisc = parseFloat(line.initial_cost_discount || 0);
        const monthlyDisc = parseFloat(line.monthly_cost_discount || 0);
        
        let originalInitial = initialCost;
        let originalMonthly = monthlyCost;
        if (index === 0 && pivot) {
            originalInitial = initialCost + initialDisc;
            originalMonthly = monthlyCost + monthlyDisc;
        }

        return {
            ...line,
            id: line.id || Date.now() + Math.random(),
            is_extra: !!line.is_extra,
            is_portability: !!line.is_portability,
            has_vap: !!line.has_vap,
            selected_brand: terminal?.brand || null,
            selected_model_id: terminal?.id || null,
            selected_duration: pivot?.duration_months || null,
            terminal_pivot: pivot,
            package_terminal_id: line.package_terminal_id,
            initial_cost: initialCost,
            monthly_cost: monthlyCost,
            original_initial_cost: originalInitial,
            original_monthly_cost: originalMonthly,
            initial_cost_discount: initialDisc,
            monthly_cost_discount: monthlyDisc,
        };
    });
};
const lines = ref(initLines());

// --- NUEVA FUNCIÓN: Calcular etiqueta de línea ---
const getLineLabel = (index) => {
    const currentLine = lines.value[index];
    if (!currentLine) return '';
    // Contamos cuántas líneas del mismo tipo hay hasta este índice
    const count = lines.value
        .slice(0, index + 1)
        .filter(l => l.is_extra === currentLine.is_extra)
        .length;
    return `${currentLine.is_extra ? 'Línea Adicional' : 'Línea Principal'} ${count}`;
};
// -------------------------------------------------

// --- 3. Internet y Centralita ---
const additionalInternetLines = ref(isEditing.value ? props.initialAdditionalInternetLines : []);
const selectedCentralitaId = ref(null);
const centralitaExtensionQuantities = ref({});
const isOperadoraAutomaticaSelected = ref(false);

// Computed principal
const selectedPackage = computed(() => props.packages.find(p => p.id === selectedPackageId.value) || null);

// Inicialización Centralita en Edit
const includedCentralita = computed(() => selectedPackage.value?.addons.find(a => a.type === 'centralita' && a.pivot.is_included));
if (isEditing.value && props.offer) {
    const optionalCentralita = props.offer.addons.find(a => a.type === 'centralita' && !a.pivot.is_included && a.pivot.selected_centralita_id === null);
    selectedCentralitaId.value = optionalCentralita?.id || null;
    isOperadoraAutomaticaSelected.value = props.offer.addons.some(a => a.type === 'centralita_feature' && a.name === 'Operadora Automática');
    
    const savedExtensions = props.offer.addons.filter(a => a.type === 'centralita_extension');
    savedExtensions.forEach(ext => {
         if(ext.pivot.quantity > 0) centralitaExtensionQuantities.value[ext.id] = ext.pivot.quantity;
    });
}

// --- 4. Computeds ---
const benefitLimit = computed(() => selectedPackage.value?.benefit_limit || 0);
const availableBenefits = computed(() => selectedPackage.value?.benefits || []);
const benefitsEmpresa = computed(() => availableBenefits.value.filter(b => b.category === 'Empresa'));
const benefitsHogar = computed(() => availableBenefits.value.filter(b => b.category === 'Hogar'));
const selectedBenefits = computed(() => availableBenefits.value.filter(b => selectedBenefitIds.value.includes(b.id)));
const totalSelectedCount = computed(() => selectedBenefitIds.value.length);
const hogarSelectedCount = computed(() => selectedBenefits.value.filter(b => b.category === 'Hogar').length);
const isTotalLimitReached = computed(() => totalSelectedCount.value >= benefitLimit.value);
const isHogarLimitReached = computed(() => hogarSelectedCount.value >= 1);

const mobileAddonInfo = computed(() => selectedPackage.value?.addons.find(a => a.type === 'mobile_line'));
const internetAddonOptions = computed(() => selectedPackage.value?.addons.filter(a => a.type === 'internet') || []);
const tvAddonOptions = computed(() => selectedPackage.value?.addons.filter(a => a.type === 'tv') || []);
const centralitaAddonOptions = computed(() => selectedPackage.value?.addons.filter(a => a.type === 'centralita' && !a.pivot.is_included) || []);
const operadoraAutomaticaInfo = computed(() => selectedPackage.value?.addons.find(a => a.type === 'centralita_feature' && a.name === 'Operadora Automática'));
const isCentralitaActive = computed(() => !!includedCentralita.value || !!selectedCentralitaId.value || additionalInternetLines.value.some(line => !!line.selected_centralita_id));
const autoIncludedExtension = computed(() => {
    if (!selectedCentralitaId.value) return null;
    const selected = centralitaAddonOptions.value.find(c => c.id === selectedCentralitaId.value);
    if (!selected) return null;
    const type = selected.name.split(' ')[1];
    return props.centralitaExtensions.find(ext => ext.name.includes(type));
});
const includedCentralitaExtensions = computed(() => isCentralitaActive.value ? selectedPackage.value?.addons.filter(a => a.type === 'centralita_extension' && a.pivot.is_included) : []);
const availableAdditionalExtensions = computed(() => props.centralitaExtensions);
const canAddLine = computed(() => !!selectedPackage.value);
const availableTerminals = computed(() => selectedPackage.value?.terminals || []);
const availableO2oDiscounts = computed(() => selectedPackage.value?.o2o_discounts || []);
const brandsForSelectedPackage = computed(() => [...new Set(availableTerminals.value.map(t => t.brand))]);
const digitalSolutionAddons = computed(() => props.allAddons ? props.allAddons.filter(a => ['service', 'software'].includes(a.type)) : []);

// --- 5. Composable ---
const { calculationSummary, ipFijaAddonInfo, fibraOroAddonInfo, ddiAddonInfo } = useOfferCalculations(
    props, selectedPackageId, lines, selectedInternetAddonId, additionalInternetLines,
    selectedCentralitaId, centralitaExtensionQuantities, isOperadoraAutomaticaSelected,
    selectedTvAddonIds, selectedDigitalAddonIds, form, selectedBenefits, props.offer?.user
);

// --- 6. Lógica de Precios ---
const getTvAddonPrice = (addon) => {
    let originalPrice = parseFloat(addon.pivot?.price ?? addon.price) || 0;
    if (selectedPackage.value?.name === 'Base Plus') {
        if (addon.name === 'Futbol') originalPrice = 38.40;
        else if (addon.name === 'Futbol y más deportes') originalPrice = 44.05;
    }
    return originalPrice;
};

const modelsByBrand = (brand) => availableTerminals.value.filter(t => t.brand === brand).filter((v, i, a) => a.findIndex(t => t.model === v.model) === i);
const findTerminalPivot = (line) => availableTerminals.value.find(t => t.id === line.selected_model_id && t.pivot.duration_months === line.selected_duration)?.pivot;

const assignTerminalPrices = (line) => {
    const pivot = findTerminalPivot(line);
    const originalInitial = parseFloat(pivot?.initial_cost || 0);
    const originalMonthly = parseFloat(pivot?.monthly_cost || 0);
    const initialDiscount = parseFloat(pivot?.initial_cost_discount || 0);
    const monthlyDiscount = parseFloat(pivot?.monthly_cost_discount || 0);

    line.original_initial_cost = originalInitial;
    line.original_monthly_cost = originalMonthly;
    line.initial_cost_discount = initialDiscount;
    line.monthly_cost_discount = monthlyDiscount;

    const lineIndex = lines.value.findIndex(l => l.id === line.id);
    if (lineIndex === 0) {
        line.initial_cost = originalInitial - initialDiscount;
        line.monthly_cost = originalMonthly - monthlyDiscount;
    } else {
        line.initial_cost = originalInitial;
        line.monthly_cost = originalMonthly;
    }
    line.terminal_pivot = pivot;
    line.package_terminal_id = pivot?.id || null;
};

// --- 7. Watchers Líneas ---
const addWatchersToLine = (line) => {
    watch(() => line.is_portability, (val, old) => { if (old && !val) { line.has_vap = false; line.selected_brand = null; assignTerminalPrices(line); } });
    watch(() => line.has_vap, (val, old) => { if (old && !val) { line.selected_brand = null; assignTerminalPrices(line); } });
    watch(() => [line.selected_model_id, line.selected_duration], () => assignTerminalPrices(line));
};
const addWatchersToAdditionalLine = (line) => {
    watch(() => line.selected_centralita_id, (val) => line.has_ip_fija = !!val);
};

// --- 8. Acciones UI ---
const addLine = () => {
    if (!canAddLine.value) return;
    const newLine = createNewLine(true);
    lines.value.push(newLine);
    addWatchersToLine(lines.value[lines.value.length - 1]);
};

const removeLine = (index) => { if (lines.value[index]?.is_extra) lines.value.splice(index, 1); };

const copyPreviousLine = (line, index) => {
    if (index <= 0 || !lines.value[index - 1]) return;
    const prev = lines.value[index - 1];
    Object.assign(line, {
        is_portability: prev.is_portability,
        source_operator: prev.source_operator,
        has_vap: prev.has_vap,
        o2o_discount_id: prev.o2o_discount_id,
        selected_brand: prev.selected_brand,
        selected_model_id: prev.selected_model_id,
        selected_duration: prev.selected_duration
    });
    assignTerminalPrices(line);
};

const addInternetLine = () => {
    const newLine = { id: Date.now(), addon_id: null, has_ip_fija: false, has_fibra_oro: false, selected_centralita_id: null };
    additionalInternetLines.value.push(newLine);
    addWatchersToAdditionalLine(additionalInternetLines.value[additionalInternetLines.value.length - 1]);
};
const removeInternetLine = (index) => additionalInternetLines.value.splice(index, 1);

const getDurationsForModel = (line) => [...new Set(availableTerminals.value.filter(t => t.id === line.selected_model_id).map(t => t.pivot.duration_months))].sort((a, b) => a - b);
const getO2oDiscountsForLine = (line, index) => {
    if (!mobileAddonInfo.value) return availableO2oDiscounts.value;
    const limit = mobileAddonInfo.value.pivot.line_limit ?? 0;
    const extraLinesBeforeThis = lines.value.slice(0, index).filter(l => l.is_extra).length;
    const isPromotionalExtra = line.is_extra && (extraLinesBeforeThis < limit);
    return isPromotionalExtra ? availableO2oDiscounts.value.filter(d => (parseFloat(d.total_discount_amount) / parseFloat(d.duration_months)) <= 1) : availableO2oDiscounts.value;
};

// --- 9. Watchers Globales ---
watch(selectedPackageId, (newId, oldId) => {
    if (isEditing.value && oldId === undefined) return;
    
    lines.value = []; selectedInternetAddonId.value = null; additionalInternetLines.value = [];
    selectedCentralitaId.value = null; centralitaExtensionQuantities.value = {}; 
    isOperadoraAutomaticaSelected.value = false; selectedTvAddonIds.value = [];
    form.is_fibra_oro_selected = false; form.ddi_quantity = 0;
    selectedBenefitIds.value = []; selectedDigitalAddonIds.value = [];

    if (!newId) {
        form.is_ip_fija_selected = false;
        return;
    }

    const newPkg = props.packages.find(p => p.id === newId);
    if (!newPkg) return;

    const pkgInternetAddons = newPkg.addons.filter(a => a.type === 'internet');
    const defaultNet = [...pkgInternetAddons].sort((a, b) => (a.pivot.price ?? 0) - (b.pivot.price ?? 0))[0];
    if (defaultNet) selectedInternetAddonId.value = defaultNet.id;
    
    const pkgMobileAddon = newPkg.addons.find(a => a.type === 'mobile_line');
    const qty = pkgMobileAddon?.pivot.included_quantity || 0;
    
    for (let i = 0; i < qty; i++) {
        const l = createNewLine(false);
        lines.value.push(l);
        addWatchersToLine(lines.value[lines.value.length - 1]);
    }

    const pkgHasCentralita = newPkg.addons.some(a => a.type === 'centralita' && a.pivot.is_included);
    form.is_ip_fija_selected = pkgHasCentralita;
});

watch(() => form.client_id, (id) => {
    selectedClient.value = props.clients.find(c => c.id === Number(id)) || null;
    if (id) isReassigningClient.value = false;
}, { immediate: true });

watch(isCentralitaActive, (isActive) => {
    if (isActive) {
        form.is_ip_fija_selected = true;
    } else {
        form.is_ip_fija_selected = false;
    }
}, { immediate: true });

onMounted(() => {
    lines.value.forEach(addWatchersToLine);
    additionalInternetLines.value.forEach(addWatchersToAdditionalLine);
});

// --- 10. Submit ---
const submitForm = () => {
    if (!form.client_id) return alert("Por favor, selecciona un cliente.");
    if (!selectedPackage.value) return alert("Por favor, selecciona un paquete.");

    try {
        let finalExtensions = [];
        for (const [id, qty] of Object.entries(centralitaExtensionQuantities.value)) {
            if (qty > 0) finalExtensions.push({ addon_id: parseInt(id), quantity: qty });
        }
        if (!includedCentralita.value && autoIncludedExtension.value) {
            const existing = finalExtensions.find(e => e.addon_id == autoIncludedExtension.value.id);
            if (existing) existing.quantity++; else finalExtensions.push({ addon_id: autoIncludedExtension.value.id, quantity: 1 });
        }

        form.package_id = selectedPackageId.value;
        form.lines = lines.value.map(l => ({
            is_extra: l.is_extra, is_portability: l.is_portability, phone_number: l.phone_number,
            source_operator: l.source_operator, has_vap: l.has_vap, o2o_discount_id: l.o2o_discount_id,
            terminal_pivot_id: l.package_terminal_id,
            initial_cost: l.initial_cost, monthly_cost: l.monthly_cost,
            initial_cost_discount: l.initial_cost_discount, monthly_cost_discount: l.monthly_cost_discount
        }));
        form.internet_addon_id = selectedInternetAddonId.value;
        form.additional_internet_lines = additionalInternetLines.value.filter(l => l.addon_id).map(l => ({
            addon_id: l.addon_id, has_ip_fija: l.has_ip_fija, has_fibra_oro: l.has_fibra_oro, selected_centralita_id: l.selected_centralita_id
        }));
        form.centralita = {
            id: selectedCentralitaId.value || includedCentralita.value?.id || null,
            operadora_automatica_selected: isOperadoraAutomaticaSelected.value,
            operadora_automatica_id: operadoraAutomaticaInfo.value?.id || null,
            extensions: finalExtensions
        };
        form.tv_addons = selectedTvAddonIds.value;
        form.digital_addons = selectedDigitalAddonIds.value;
        form.applied_benefit_ids = selectedBenefitIds.value;
        form.summary = calculationSummary.value;

        const options = {
            onSuccess: () => alert(isEditing.value ? '¡Oferta actualizada!' : '¡Oferta guardada!'),
            onError: (e) => { console.error(e); alert('Error al guardar.'); }
        };

        if (isEditing.value) {
            form.put(route('offers.update', props.offer.id), options);
        } else {
            form.post(route('offers.store'), options);
        }
    } catch (e) { console.error(e); alert("Error inesperado preparando la oferta."); }
};

const showReassignSelector = () => isReassigningClient.value = true;
const changeClient = () => form.client_id = null;
</script>

<template>
    <div class="flex flex-col md:flex-row">

        <div class="w-full md:w-4/5 p-4 sm:p-6 lg:p-8">
            <div class="space-y-8">

                <div class="bg-white shadow-sm sm:rounded-lg p-8">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold">
                            {{ isEditing ? `Editar Oferta #${offer.id}` : 'Crear Nueva Oferta' }}
                            <span v-if="isEditing && auth.user.id !== offer.user.id" class="text-sm font-normal text-gray-500 ml-2">
                                (Propietario: {{ offer.user.name }})
                            </span>
                        </h1>
                        <Link :href="route('offers.index')">
                            <SecondaryButton>Atrás</SecondaryButton>
                        </Link>
                    </div>

                    <div class="mb-8">
                        <div v-if="!form.client_id || isReassigningClient">
                            <label class="block text-sm font-medium text-gray-700 mb-2">1. Selecciona un Cliente</label>
                            <select v-model="form.client_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option :value="null" disabled>-- Elige un cliente --</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }} ({{ client.cif_nif }})</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.client_id" />
                            <p v-if="!isEditing" class="text-xs text-gray-500 mt-2">
                                ¿No encuentras al cliente? <Link :href="route('clients.create', { source: 'offers' })" class="underline text-indigo-600">Créalo aquí.</Link>
                            </p>
                        </div>
                        <div v-else>
                            <label class="block text-sm font-medium text-gray-700 mb-2">1. Cliente Seleccionado</label>
                            <div v-if="selectedClient" class="p-4 border rounded-md bg-gray-50 shadow-sm">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-1 text-sm">
                                    <p><strong>Nombre:</strong> {{ selectedClient.name }}</p>
                                    <p><strong>CIF/NIF:</strong> {{ selectedClient.cif_nif }}</p>
                                    <p><strong>Email:</strong> {{ selectedClient.email || 'No disponible' }}</p>
                                    <p><strong>Teléfono:</strong> {{ selectedClient.phone || 'No disponible' }}</p>
                                    <p class="col-span-2"><strong>Dirección:</strong> {{ selectedClient.address || 'No disponible' }}</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <SecondaryButton type="button" @click="isEditing ? showReassignSelector() : changeClient()">
                                    {{ isEditing ? 'Reasignar Cliente' : 'Cambiar de cliente' }}
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">2. Selecciona un Paquete Base</label>
                        <select v-model="selectedPackageId" :disabled="!form.client_id || isEditing" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100 disabled:cursor-not-allowed">
                            <option :value="null" disabled>-- Elige un paquete --</option>
                            <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.package_id" />
                    </div>
                </div>

                <div v-if="selectedPackage" class="bg-white shadow-sm sm:rounded-lg p-8 space-y-6">

                    <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Probabilidad (%)</label>
                            <select v-model="form.probability" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option :value="null">-- Selecciona --</option>
                                <option v-for="opt in probabilityOptions" :key="opt" :value="opt">{{ opt }}%</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.probability" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fecha Firma</label>
                            <input type="date" v-model="form.signing_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <InputError class="mt-2" :message="form.errors.signing_date" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fecha Tramitación</label>
                            <input type="date" v-model="form.processing_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <InputError class="mt-2" :message="form.errors.processing_date" />
                        </div>
                    </section>

                    <div v-if="internetAddonOptions.length > 0 || fiberFeatures?.length > 0" class="p-6 bg-slate-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">3. Fibra Principal</label>
                        <div v-if="internetAddonOptions.length > 0" class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4 mt-1">
                            <label v-for="addon in internetAddonOptions" :key="addon.id"
                                :class="['flex-1 text-center px-4 py-3 rounded-md border cursor-pointer transition', { 'bg-indigo-600 text-white border-indigo-600 shadow-lg': selectedInternetAddonId === addon.id, 'bg-white border-gray-300 hover:bg-gray-50': selectedInternetAddonId !== addon.id }]">
                                <input type="radio" :value="addon.id" v-model="selectedInternetAddonId" class="sr-only">
                                <span class="block font-semibold">{{ addon.name }}</span>
                                <span class="block text-xs mt-1" v-if="parseFloat(addon.pivot.price) > 0">+{{ parseFloat(addon.pivot.price).toFixed(2) }}€/mes</span>
                            </label>
                        </div>
                        
                        <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2">
                            <div v-if="ipFijaAddonInfo">
                                <label class="flex items-center">
                                    <Checkbox v-model:checked="form.is_ip_fija_selected" />
                                    <span class="ml-2 text-sm text-gray-600">Añadir IP Fija ({{ ipFijaAddonInfo.price }}€)</span>
                                </label>
                                <p class="text-xs text-gray-500 ml-6">Gratis si se incluye Centralita.</p>
                            </div>
                            <div v-if="fibraOroAddonInfo">
                                <label class="flex items-center">
                                    <Checkbox v-model:checked="form.is_fibra_oro_selected" />
                                    <span class="ml-2 text-sm text-gray-600">Añadir Fibra Oro ({{ fibraOroAddonInfo.price }}€)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div v-if="tvAddonOptions.length > 0" class="space-y-4 p-6 bg-slate-50 rounded-lg h-full">
                            <h3 class="text-lg font-semibold text-gray-800">4. Televisión</h3>
                            <div class="space-y-2">
                                <div v-for="addon in tvAddonOptions" :key="addon.id" class="flex items-center">
                                    <input :id="`tv_${addon.id}`" :value="addon.id" v-model="selectedTvAddonIds" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <label :for="`tv_${addon.id}`" class="ml-3 block text-sm text-gray-900">
                                        {{ addon.name }} <span v-if="getTvAddonPrice(addon) > 0" class="text-gray-600">(+{{ getTvAddonPrice(addon).toFixed(2) }}€)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div v-if="centralitaAddonOptions.length > 0 || includedCentralita" class="space-y-4 p-6 bg-slate-50 rounded-lg h-full">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">5. Centralita</h3>
                            
                            <div v-if="includedCentralita" class="p-4 bg-green-100 border border-green-300 rounded-md text-center">
                                <p class="font-semibold text-green-800">✅ {{ includedCentralita.name }} Incluida</p>
                            </div>
                            <div v-else-if="centralitaAddonOptions.length > 0">
                                <label class="block text-sm font-medium text-gray-700">Añadir Centralita</label>
                                <select v-model="selectedCentralitaId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option :value="null">No añadir</option>
                                    <option v-for="c in centralitaAddonOptions" :key="c.id" :value="c.id">{{ c.name }} (+{{ parseFloat(c.pivot.price).toFixed(2) }}€)</option>
                                </select>
                            </div>

                            <div v-if="isCentralitaActive" class="space-y-4 pt-4 border-t border-dashed">
                                <div v-if="operadoraAutomaticaInfo">
                                    <div v-if="operadoraAutomaticaInfo.pivot.is_included" class="p-2 bg-gray-100 rounded-md text-sm text-gray-800">✅ Op. Automática Incluida</div>
                                    <div v-else class="flex items-center">
                                        <Checkbox v-model:checked="isOperadoraAutomaticaSelected" id="op_auto" />
                                        <label for="op_auto" class="ml-2 block text-sm text-gray-900">Añadir Op. Automática (+{{ parseFloat(operadoraAutomaticaInfo.pivot.price).toFixed(2) }}€)</label>
                                    </div>
                                </div>
                                
                                <div v-if="ddiAddonInfo" class="pt-2 border-t border-dashed">
                                    <div class="flex items-center justify-between mt-2">
                                        <label class="text-sm text-gray-800">DDI (Marcación Directa)</label>
                                        <input type="number" min="0" v-model.number="form.ddi_quantity" class="w-20 rounded-md border-gray-300 shadow-sm text-center focus:border-indigo-500 focus:ring-indigo-500" placeholder="0">
                                    </div>
                                    <InputError :message="form.errors.ddi_quantity" />
                                </div>

                                <div class="pt-2">
                                    <div v-if="includedCentralitaExtensions.length" class="mb-4 space-y-2">
                                        <p class="text-sm font-medium text-gray-700">Ext. Incluidas:</p>
                                        <div v-for="ext in includedCentralitaExtensions" :key="`inc_${ext.id}`" class="p-2 bg-gray-100 rounded-md text-sm text-gray-800">✅ {{ ext.pivot.included_quantity }}x {{ ext.name }}</div>
                                    </div>
                                    <div v-if="autoIncludedExtension && !includedCentralita" class="mb-4">
                                        <p class="text-sm font-medium text-gray-700">Ext. Incluida:</p>
                                        <div class="p-2 bg-gray-100 rounded-md text-sm text-gray-800">✅ 1x {{ autoIncludedExtension.name }}</div>
                                    </div>
                                    <p class="text-sm font-medium text-gray-700">Ext. Adicionales:</p>
                                    <div v-for="ext in availableAdditionalExtensions" :key="ext.id" class="flex items-center justify-between mt-2">
                                        <label :for="`ext_${ext.id}`" class="text-sm text-gray-800">{{ ext.name }} (+{{ parseFloat(ext.price).toFixed(2) }}€)</label>
                                        <input :id="`ext_${ext.id}`" type="number" min="0" v-model.number="centralitaExtensionQuantities[ext.id]" class="w-20 rounded-md border-gray-300 shadow-sm text-center focus:border-indigo-500 focus:ring-indigo-500" placeholder="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 p-6 bg-slate-50 rounded-lg h-full">
                            <h3 class="text-lg font-semibold text-gray-800">6. Internet Adicional</h3>
                            <div v-for="(line, index) in additionalInternetLines" :key="line.id" class="p-3 border rounded-lg bg-blue-50 border-blue-200 space-y-2">
                                <div class="flex-1">
                                    <div class="flex justify-between items-center mb-1">
                                        <label class="block text-xs font-medium text-gray-500">Línea Adicional {{ index + 1 }}</label>
                                        <button @click="removeInternetLine(index)" type="button" class="text-red-500 hover:text-red-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                    <select v-model="line.addon_id" class="block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option :value="null" disabled>-- Selecciona --</option>
                                        <option v-for="a in additionalInternetAddons" :key="a.id" :value="a.id">{{ a.name }} (+{{ parseFloat(a.price).toFixed(2) }}€)</option>
                                    </select>
                                </div>

                                <div v-if="line.addon_id && centralitaAddonOptions.length > 0">
                                    <label :for="`multi_centralita_${line.id}`" class="block text-xs font-medium text-gray-500">Centralita Multisede</label>
                                    <select v-model="line.selected_centralita_id" :id="`multi_centralita_${line.id}`" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option :value="null">-- Sin Centralita Multisede --</option>
                                        <option v-for="c in centralitaAddonOptions" :key="c.id" :value="c.id">{{ c.name }} (+{{ parseFloat(c.pivot.price).toFixed(2) }}€)</option>
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

                    <div v-if="digitalSolutionAddons.length > 0" class="bg-white shadow-sm sm:rounded-lg p-8 space-y-6">
                        <h3 class="text-lg font-semibold text-gray-800 text-center">Soluciones Digitales</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div v-for="addon in digitalSolutionAddons" :key="addon.id" class="flex items-center p-3 border rounded-md hover:bg-gray-50">
                                <Checkbox :value="addon.id" v-model:checked="selectedDigitalAddonIds" :id="`dig_${addon.id}`" />
                                <label :for="`dig_${addon.id}`" class="ml-3 text-sm text-gray-700">
                                    {{ addon.name }} <span class="text-xs text-gray-500">({{ parseFloat(addon.price).toFixed(2) }}€)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div v-if="availableBenefits.length > 0" class="bg-white shadow-sm sm:rounded-lg p-8 border-y -my-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Beneficios ({{ totalSelectedCount }}/{{ benefitLimit }})</h3>
                        <p v-if="isTotalLimitReached" class="text-sm text-red-600">Límite alcanzado.</p>
                        
                        <div class="mt-4">
                            <h4 class="font-semibold text-gray-700 border-b pb-2">Empresa</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                                <label v-for="b in benefitsEmpresa" :key="b.id" class="flex items-center p-3 border rounded-md hover:bg-gray-50">
                                    <input type="checkbox" :value="b.id" v-model="selectedBenefitIds" :disabled="!selectedBenefitIds.includes(b.id) && isTotalLimitReached" class="rounded border-gray-300 text-indigo-600 shadow-sm">
                                    <span class="ml-3 text-sm">{{ b.description }}</span>
                                </label>
                            </div>
                        </div>
                        <div class="mt-6">
                            <h4 class="font-semibold text-gray-700 border-b pb-2">Hogar (Max 1)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                                <label v-for="b in benefitsHogar" :key="b.id" class="flex items-center p-3 border rounded-md hover:bg-gray-50">
                                    <input type="checkbox" :value="b.id" v-model="selectedBenefitIds" :disabled="(!selectedBenefitIds.includes(b.id) && isTotalLimitReached) || (!selectedBenefitIds.includes(b.id) && isHogarLimitReached)" class="rounded border-gray-300 text-indigo-600 shadow-sm">
                                    <span class="ml-3 text-sm">{{ b.description }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white shadow-sm sm:rounded-lg p-8 space-y-6">
                        <h3 class="text-lg font-semibold text-gray-800 text-center">7. Líneas Móviles</h3>
                        <div v-if="lines.length === 0" class="text-center text-gray-500 text-sm">No hay líneas móviles por defecto.</div>
                        
                        <div v-for="(line, index) in lines" :key="line.id" class="p-6 border rounded-lg" :class="{'bg-gray-50': !line.is_extra, 'bg-green-50': line.is_extra}">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center mb-4">
                                <div class="md:col-span-2 flex justify-between items-center">
                                    <span class="font-medium text-gray-700">
                                        {{ getLineLabel(index) }}
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
                                    <label class="text-xs font-medium text-gray-500">Nº Teléfono</label>
                                    <input v-model="line.phone_number" type="tel" class="block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                </div>
                                <div class="md:col-span-2 flex items-center h-full pt-4">
                                    <Checkbox v-model:checked="line.is_portability" :id="`port_${line.id}`" />
                                    <label :for="`port_${line.id}`" class="ml-2 text-sm text-gray-900">Portabilidad</label>
                                </div>
                            </div>

                            <div v-if="line.is_portability" class="border-t pt-4 mt-4 space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
                                    <div class="md:col-span-4">
                                        <label class="text-xs font-medium text-gray-500">Operador</label>
                                        <select v-model="line.source_operator" class="block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                            <option :value="null" disabled>-- Selecciona --</option>
                                            <option v-for="op in operators" :key="op" :value="op">{{ op }}</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2 flex items-center pt-4">
                                        <Checkbox v-model:checked="line.has_vap" :id="`vap_${line.id}`" />
                                        <label :for="`vap_${line.id}`" class="ml-2 text-sm text-gray-900">VAP</label>
                                    </div>
                                    <div class="md:col-span-4">
                                        <label class="text-xs font-medium text-gray-500">Descuento O2O</label>
                                        <select v-model="line.o2o_discount_id" class="block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                            <option :value="null">-- Sin subvención --</option>
                                            <option v-for="d in getO2oDiscountsForLine(line, index)" :key="d.id" :value="d.id">{{ d.name }}</option>
                                        </select>
                                    </div>
                                </div>

                                <div v-if="line.has_vap" class="pt-4 border-t border-dashed grid grid-cols-1 md:grid-cols-12 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="text-xs font-medium text-gray-500">Marca</label>
                                        <select v-model="line.selected_brand" @change="line.selected_model_id=null; assignTerminalPrices(line)" class="block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                            <option :value="null">-- Marca --</option>
                                            <option v-for="b in brandsForSelectedPackage" :key="b" :value="b">{{ b }}</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-3">
                                        <label class="text-xs font-medium text-gray-500">Modelo</label>
                                        <select v-model="line.selected_model_id" :disabled="!line.selected_brand" class="block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                            <option :value="null">-- Modelo --</option>
                                            <option v-for="t in modelsByBrand(line.selected_brand)" :key="t.id" :value="t.id">{{ t.model }}</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="text-xs font-medium text-gray-500">Meses</label>
                                        <select v-model="line.selected_duration" :disabled="!line.selected_model_id" class="block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                            <option :value="null">-- Meses --</option>
                                            <option v-for="d in getDurationsForModel(line)" :key="d" :value="d">{{ d }} meses</option>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="text-xs font-medium text-gray-500">Pago Inicial (€)</label>
                                        <input v-model.number="line.initial_cost" type="number" step="0.01" class="block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="text-xs font-medium text-gray-500">Cuota Mensual (€)</label>
                                        <input v-model.number="line.monthly_cost" type="number" step="0.01" class="block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-center pt-4">
                            <PrimaryButton @click="addLine" type="button">Añadir Línea Móvil Adicional</PrimaryButton>
                        </div>
                    </div>

                    <div class="mt-10 flex justify-center">
                        <PrimaryButton @click="submitForm" :disabled="form.processing">
                            {{ isEditing ? 'Actualizar Oferta' : 'Guardar Oferta' }}
                        </PrimaryButton>
                    </div>

                </div>
                <div v-else class="text-center text-gray-500 mt-10 p-8 bg-white rounded-lg shadow-sm">
                    {{ isEditing ? 'Cargando datos...' : 'Por favor, selecciona un cliente y un paquete para continuar.' }}
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
                            <span>Pago Inicial Total:</span><span>{{ calculationSummary.totalInitialPayment }}€</span>
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
                        <p class="text-xl font-bold text-emerald-600 text-center mt-2">
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
</template>