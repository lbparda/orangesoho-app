<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue'; // Asegúrate que la ruta sea correcta
import { useOfferCalculations } from '@/composables/useOfferCalculations.js';

const props = defineProps({
    clients: Array,
    packages: Array,
    discounts: Array,
    operators: Array,
    portabilityCommission: {
        type: Number,
        required: true,
        default: 0,
    },
    additionalInternetAddons: Array,
    centralitaExtensions: Array,
    auth: Object,
    initialClientId: [Number, String, null],
    probabilityOptions: Array,
    portabilityExceptions: Array,
    fiberFeatures: Array, // <-- AÑADIDO: Prop para IP Fija
});

const selectedClient = ref(null);

const changeClient = () => {
    form.client_id = null;
};

const createNewLine = (isExtra = false) => ({
    id: Date.now() + Math.random(),
    is_extra: isExtra,
    is_portability: false,
    phone_number: '', source_operator: null, has_vap: false,
    o2o_discount_id: null, selected_brand: null, selected_model_id: null,
    selected_duration: null, terminal_pivot: null, package_terminal_id: null, // Añadido package_terminal_id
    initial_cost: 0, monthly_cost: 0,
});

const form = useForm({
    client_id: props.initialClientId || null,
    package_id: null,
    lines: [],
    internet_addon_id: null,
    additional_internet_lines: [],
    centralita: null,
    tv_addons: [],
    is_ip_fija_selected: false, // <-- AÑADIDO: Estado IP Fija en el form
    summary: null,
    probability: null,
    signing_date: '',
    processing_date: '',
});

const selectedPackageId = ref(null);
const lines = ref([]);
const selectedInternetAddonId = ref(null);
const additionalInternetLines = ref([]);
const selectedCentralitaId = ref(null);
const centralitaExtensionQuantities = ref({});
const isOperadoraAutomaticaSelected = ref(false);
const selectedTvAddonIds = ref([]);
// const isIpFijaSelected = ref(false); // <-- ELIMINADO: Se mueve al 'form'
const showCommissionDetails = ref(false);

const selectedPackage = computed(() => props.packages.find(p => p.id === selectedPackageId.value) || null);
const tvAddonOptions = computed(() => selectedPackage.value?.addons.filter(a => a.type === 'tv') || []);
const mobileAddonInfo = computed(() => selectedPackage.value?.addons.find(a => a.type === 'mobile_line'));
const internetAddonOptions = computed(() => selectedPackage.value?.addons.filter(a => a.type === 'internet') || []);
const centralitaAddonOptions = computed(() => selectedPackage.value?.addons.filter(a => a.type === 'centralita' && !a.pivot.is_included) || []);
const includedCentralita = computed(() => selectedPackage.value?.addons.find(a => a.type === 'centralita' && a.pivot.is_included));
const isCentralitaActive = computed(() => !!includedCentralita.value || !!selectedCentralitaId.value);
const autoIncludedExtension = computed(() => {
    if (!selectedCentralitaId.value) return null;
    const selected = centralitaAddonOptions.value.find(c => c.id === selectedCentralitaId.value);
    if (!selected) return null;
    const type = selected.name.split(' ')[1];
    return props.centralitaExtensions.find(ext => ext.name.includes(type));
});
const includedCentralitaExtensions = computed(() => isCentralitaActive.value ? selectedPackage.value?.addons.filter(a => a.type === 'centralita_extension' && a.pivot.is_included) : []);
const operadoraAutomaticaInfo = computed(() => selectedPackage.value?.addons.find(a => a.type === 'centralita_feature'));
const canAddLine = computed(() => !!selectedPackage.value);
const availableTerminals = computed(() => selectedPackage.value?.terminals || []);
const availableO2oDiscounts = computed(() => selectedPackage.value?.o2o_discounts || []);
const brandsForSelectedPackage = computed(() => [...new Set(availableTerminals.value.map(t => t.brand))]);
const availableAdditionalExtensions = computed(() => props.centralitaExtensions);

// Pasamos el objeto 'form' completo al composable
const { calculationSummary } = useOfferCalculations(
    props, selectedPackageId, lines, selectedInternetAddonId, additionalInternetLines,
    selectedCentralitaId, centralitaExtensionQuantities, isOperadoraAutomaticaSelected, selectedTvAddonIds,
    form // <-- MODIFICADO: Pasar el objeto form completo
);

const modelsByBrand = (brand) => availableTerminals.value.filter(t => t.brand === brand).filter((v, i, a) => a.findIndex(t => t.model === v.model) === i);
// Ahora usamos pivot.id (que viene de package_terminal.id)
const findTerminalPivot = (line) => availableTerminals.value.find(t => t.id === line.selected_model_id && t.pivot.duration_months === line.selected_duration)?.pivot;
const assignTerminalPrices = (line) => {
    const pivot = findTerminalPivot(line);
    line.initial_cost = parseFloat(pivot?.initial_cost || 0);
    line.monthly_cost = parseFloat(pivot?.monthly_cost || 0);
    line.terminal_pivot = pivot;
    line.package_terminal_id = pivot?.id || null; // Guardamos el ID de la tabla pivote
};
const addLine = () => {
    if (!canAddLine.value) return;
    const newLine = createNewLine(true);
    lines.value.push(newLine);
    addWatchersToLine(newLine);
};
const removeLine = (index) => { if (lines.value[index]?.is_extra) lines.value.splice(index, 1); };

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
    // initial_cost y monthly_cost se asignan ahora con assignTerminalPrices
    assignTerminalPrices(line); // Asigna pivot, costs y package_terminal_id
};

const addInternetLine = () => additionalInternetLines.value.push({ id: Date.now(), addon_id: null });
const removeInternetLine = (index) => additionalInternetLines.value.splice(index, 1);
const getDurationsForModel = (line) => [...new Set(availableTerminals.value.filter(t => t.id === line.selected_model_id).map(t => t.pivot.duration_months))].sort((a, b) => a - b);
const getO2oDiscountsForLine = (line, index) => {
    if (!mobileAddonInfo.value) return availableO2oDiscounts.value;
    const promoLimit = mobileAddonInfo.value.pivot.line_limit ?? 0; // Usar ?? 0 por si no viene
    const extraLinesBeforeThis = lines.value.slice(0, index).filter(l => l.is_extra).length;
    const isPromotionalExtra = line.is_extra && (extraLinesBeforeThis < promoLimit);
    if (isPromotionalExtra) {
        return availableO2oDiscounts.value.filter(d => (parseFloat(d.total_discount_amount) / parseFloat(d.duration_months)) <= 1);
    }
    return availableO2oDiscounts.value;
};
const saveOffer = () => {
    if (!form.client_id) { alert("Por favor, selecciona un cliente."); return; }
    if (!selectedPackage.value) { alert("Por favor, selecciona un paquete."); return; }
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
        form.lines = lines.value.map(line => ({
            is_extra: line.is_extra, is_portability: line.is_portability, phone_number: line.phone_number,
            source_operator: line.source_operator, has_vap: line.has_vap, o2o_discount_id: line.o2o_discount_id,
            terminal_pivot_id: line.package_terminal_id, // Enviamos el ID del pivot
            initial_cost: line.initial_cost, monthly_cost: line.monthly_cost,
        }));
        form.internet_addon_id = selectedInternetAddonId.value;
        form.additional_internet_lines = additionalInternetLines.value.filter(l => l.addon_id).map(l => ({ addon_id: l.addon_id }));
        form.centralita = {
            id: selectedCentralitaId.value || includedCentralita.value?.id || null,
            operadora_automatica_selected: isOperadoraAutomaticaSelected.value,
            operadora_automatica_id: operadoraAutomaticaInfo.value?.id || null, extensions: finalExtensions,
        };
        form.tv_addons = selectedTvAddonIds.value;
        // is_ip_fija_selected ya está en el form
        form.summary = calculationSummary.value;
        // probability, signing_date y processing_date ya están en el form.
        form.post(route('offers.store'), { onSuccess: () => alert('¡Oferta guardada!'), onError: (e) => { console.error(e); alert('Error al guardar.'); } });
    } catch (e) { console.error("Error preparing offer:", e); alert("Error inesperado."); }
};

const addWatchersToLine = (line) => {
    watch(() => line.is_portability, (isPortability, old) => { if (old && !isPortability) { line.has_vap = false; line.selected_brand = null; line.selected_model_id = null; line.selected_duration = null; assignTerminalPrices(line); line.source_operator = null; } });
    watch(() => line.has_vap, (hasVap, old) => { if (old && !hasVap) { line.selected_brand = null; line.selected_model_id = null; line.selected_duration = null; assignTerminalPrices(line); } });
    watch(() => [line.selected_model_id, line.selected_duration], () => assignTerminalPrices(line));
};

watch(selectedPackageId, (newPackageId) => {
    lines.value = []; selectedInternetAddonId.value = null; additionalInternetLines.value = []; selectedCentralitaId.value = null;
    centralitaExtensionQuantities.value = {}; isOperadoraAutomaticaSelected.value = false; selectedTvAddonIds.value = [];
    form.is_ip_fija_selected = false; // Resetea IP fija al cambiar paquete
    if (!newPackageId) return;
    const defaultOption = [...internetAddonOptions.value].sort((a, b) => (a.pivot.price ?? 0) - (b.pivot.price ?? 0))[0];
    if (defaultOption) selectedInternetAddonId.value = defaultOption.id;
    const mobileAddon = mobileAddonInfo.value;
    const quantity = mobileAddon?.pivot.included_quantity || 0;
    for (let i = 0; i < quantity; i++) {
        const newLine = createNewLine(false);
        lines.value.push(newLine);
        addWatchersToLine(newLine);
    }
});

watch(
    () => form.client_id,
    (newClientId) => {
        if (newClientId) {
            selectedClient.value = props.clients.find(c => c.id === Number(newClientId)) || null;
        } else {
            selectedClient.value = null;
        }
    },
    { immediate: true }
);

</script>

<template>
    <Head title="Crear Oferta" />
    <AuthenticatedLayout>
        <div class="flex flex-col md:flex-row">

            <div class="w-full md:w-4/5 p-4 sm:p-6 lg:p-8">
                <div class="space-y-8">

                    <div class="bg-white shadow-sm sm:rounded-lg p-8">
                        <div class="flex justify-between items-center mb-6">
                            <h1 class="text-2xl font-bold">Crear Nueva Oferta</h1>
                            <Link :href="route('offers.index')">
                                <SecondaryButton>Atrás</SecondaryButton>
                            </Link>
                        </div>

                        <div class="mb-8">
                            <div v-if="!form.client_id">
                                <label for="client" class="block text-sm font-medium text-gray-700 mb-2">1. Selecciona un Cliente</label>
                                <select v-model="form.client_id" id="client" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option :value="null" disabled>-- Elige un cliente --</option>
                                    <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }} ({{ client.cif_nif }})</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.client_id" />
                                <p class="text-xs text-gray-500 mt-2">
                                    ¿No encuentras al cliente?
                                    <Link :href="route('clients.create', { source: 'offers' })" class="underline text-indigo-600">Puedes crearlo aquí.</Link>
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
                                    <SecondaryButton type="button" @click="changeClient">
                                        Cambiar de cliente
                                    </SecondaryButton>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="package" class="block text-sm font-medium text-gray-700 mb-2">2. Selecciona un Paquete Base</label>
                            <select v-model="selectedPackageId" id="package" :disabled="!form.client_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100">
                                <option :value="null" disabled>-- Elige un paquete --</option>
                                <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.package_id" />
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
                            <label class="block text-sm font-medium text-gray-700 mb-2">3. Fibra Principal</label>
                            <div v-if="internetAddonOptions.length > 0" class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4 mt-1">
                                <label v-for="addon in internetAddonOptions" :key="addon.id"
                                    :class="['flex-1 text-center px-4 py-3 rounded-md border cursor-pointer transition', { 'bg-indigo-600 text-white border-indigo-600 shadow-lg': selectedInternetAddonId === addon.id, 'bg-white border-gray-300 hover:bg-gray-50': selectedInternetAddonId !== addon.id }]">
                                    <input type="radio" :value="addon.id" v-model="selectedInternetAddonId" class="sr-only">
                                    <span class="block font-semibold">{{ addon.name }}</span>
                                    <span class="block text-xs mt-1" v-if="parseFloat(addon.pivot.price) > 0">+{{ parseFloat(addon.pivot.price).toFixed(2) }}€/mes</span>
                                </label>
                            </div>
                            <div v-if="props.fiberFeatures && props.fiberFeatures.length > 0" class="mt-4">
                                <label class="flex items-center">
                                    <Checkbox v-model:checked="form.is_ip_fija_selected" />
                                    <span class="ml-2 text-sm text-gray-600">
                                        Añadir IP Fija ({{ props.fiberFeatures[0].price }}€)
                                    </span>
                                </label>
                                <p class="text-xs text-gray-500 ml-6">
                                    Gratis si se incluye Centralita.
                                </p>
                            </div>
                           </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div v-if="tvAddonOptions.length > 0" class="space-y-4 p-6 bg-slate-50 rounded-lg h-full">
                                <h3 class="text-lg font-semibold text-gray-800">4. Televisión</h3>
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
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">5. Centralita</h3>
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
                                <h3 class="text-lg font-semibold text-gray-800">6. Internet Adicional</h3>
                                <div v-for="(line, index) in additionalInternetLines" :key="line.id" class="p-3 border rounded-lg bg-blue-50 border-blue-200">
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
                                </div>
                                <PrimaryButton @click="addInternetLine" type="button" class="w-full justify-center">Añadir Internet</PrimaryButton>
                            </div>
                        </div>
                    </div>

                    <div v-if="selectedPackage" class="bg-white shadow-sm sm:rounded-lg p-8 space-y-6">
                        <h3 class="text-lg font-semibold text-gray-800 text-center">7. Líneas Móviles</h3>
                        <div v-if="lines.length === 0" class="text-gray-500 text-sm text-center">
                            Este paquete no incluye líneas móviles de base. Puedes añadirlas manualmente.
                        </div>
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
                            Guardar Oferta
                        </PrimaryButton>
                    </div>

                    <div v-if="!selectedPackage" class="text-center text-gray-500 mt-10 p-8 bg-white rounded-lg shadow-sm">
                        Por favor, selecciona un cliente y un paquete para continuar.
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
    </AuthenticatedLayout>
</template>