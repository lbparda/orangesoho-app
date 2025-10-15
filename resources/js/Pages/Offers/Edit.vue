<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    offer: Object,
    packages: Array,
    discounts: Array,
    operators: Array,
    portabilityCommission: Number,
    additionalInternetAddons: Array,
    centralitaExtensions: Array,
    auth: Object,
});

const form = useForm({
    package_id: props.offer.package_id,
    lines: [],
    internet_addon_id: null,
    additional_internet_lines: [],
    centralita: {},
    tv_addons: [],
    summary: {},
});

const selectedPackageId = ref(props.offer.package_id);
const lines = ref([]);
const selectedInternetAddonId = ref(null);
const additionalInternetLines = ref([]);
const selectedCentralitaId = ref(null);
const centralitaExtensionQuantities = ref({});
const isOperadoraAutomaticaSelected = ref(false);
const selectedTvAddonIds = ref([]);

// --- LÓGICA DE INICIALIZACIÓN COMPLETA ---
onMounted(() => {
    lines.value = props.offer.lines.map(line => {
        const terminalInfo = line.terminal_pivot?.terminal;
        return {
            ...line,
            is_extra: !!line.is_extra,
            is_portability: !!line.is_portability,
            has_vap: !!line.has_vap,
            selected_brand: terminalInfo?.brand || null,
            selected_model_id: terminalInfo?.id || null,
            selected_duration: line.terminal_pivot?.duration_months || null,
            terminal_pivot: line.terminal_pivot || null,
        };
    });

    const getAddonId = (type) => props.offer.addons.find(a => a.type === type)?.id;
    const getAddons = (type) => props.offer.addons.filter(a => a.type === type);

    selectedInternetAddonId.value = getAddonId('internet');
    additionalInternetLines.value = getAddons('internet_additional').map(a => ({ id: a.id + Math.random(), addon_id: a.id }));
    selectedTvAddonIds.value = getAddons('tv').map(a => a.id);
    
    const centralita = props.offer.addons.find(a => a.type === 'centralita' && !a.pivot.is_included);
    if(centralita) selectedCentralitaId.value = centralita.id;

    if(getAddonId('centralita_feature')) isOperadoraAutomaticaSelected.value = true;
    
    const extensions = getAddons('centralita_extension').filter(a => !a.pivot.is_included);
    extensions.forEach(ext => {
        centralitaExtensionQuantities.value[ext.id] = ext.pivot.quantity;
    });
});

const saveOffer = () => {
    const finalExtensions = { ...centralitaExtensionQuantities.value };
    if (autoIncludedExtension.value) {
        const id = autoIncludedExtension.value.id;
        finalExtensions[id] = (finalExtensions[id] || 0) + 1;
    }

    form.lines = lines.value.map(line => ({
        is_extra: line.is_extra,
        is_portability: line.is_portability,
        phone_number: line.phone_number,
        source_operator: line.source_operator,
        has_vap: line.has_vap,
        o2o_discount_id: line.o2o_discount_id,
        terminal_pivot_id: line.terminal_pivot ? line.terminal_pivot.id : null,
        initial_cost: line.initial_cost,
        monthly_cost: line.monthly_cost,
    }));
    form.internet_addon_id = selectedInternetAddonId.value;
    form.additional_internet_lines = additionalInternetLines.value.filter(l => l.addon_id).map(l => ({ addon_id: l.addon_id }));
    form.centralita = {
        id: selectedCentralitaId.value || (includedCentralita.value ? includedCentralita.value.id : null),
        operadora_automatica_selected: isOperadoraAutomaticaSelected.value,
        operadora_automatica_id: operadoraAutomaticaInfo.value ? operadoraAutomaticaInfo.value.id : null,
        extensions: Object.entries(finalExtensions).filter(([, qty]) => qty > 0).map(([id, qty]) => ({ addon_id: id, quantity: qty })),
    };
    form.tv_addons = selectedTvAddonIds.value;
    form.summary = calculationSummary.value;

    form.put(route('offers.update', props.offer.id));
};

// --- EL RESTO DEL SCRIPT ES IDÉNTICO A TU CREATE.VUE ---
const selectedPackage = computed(() => props.packages.find(p => p.id === selectedPackageId.value) || null);
const mobileAddonInfo = computed(() => selectedPackage.value?.addons.find(a => a.type === 'mobile_line'));
const internetAddonOptions = computed(() => selectedPackage.value?.addons.filter(a => a.type === 'internet'));
const selectedInternetAddonInfo = computed(() => internetAddonOptions.value.find(a => a.id === selectedInternetAddonId.value));
const tvAddonOptions = computed(() => selectedPackage.value?.addons.filter(a => a.type === 'tv'));
const centralitaAddonOptions = computed(() => selectedPackage.value?.addons.filter(a => a.type === 'centralita' && !a.pivot.is_included));
const includedCentralita = computed(() => selectedPackage.value?.addons.find(a => a.type === 'centralita' && a.pivot.is_included));
const isCentralitaActive = computed(() => !!includedCentralita.value || !!selectedCentralitaId.value);
const autoIncludedExtension = computed(() => {
    if (!selectedCentralitaId.value) return null;
    const selected = centralitaAddonOptions.value.find(c => c.id === selectedCentralitaId.value);
    const type = selected?.name.split(' ')[1];
    return props.centralitaExtensions.find(ext => ext.name.includes(type));
});
const includedCentralitaExtensions = computed(() => isCentralitaActive.value ? selectedPackage.value?.addons.filter(a => a.type === 'centralita_extension' && a.pivot.is_included) : []);
const operadoraAutomaticaInfo = computed(() => selectedPackage.value?.addons.find(a => a.type === 'centralita_feature'));
const availableTerminals = computed(() => selectedPackage.value?.terminals || []);
const availableO2oDiscounts = computed(() => selectedPackage.value?.o2o_discounts || []);
const brandsForSelectedPackage = computed(() => [...new Set(availableTerminals.value.map(t => t.brand))]);
const modelsByBrand = (brand) => availableTerminals.value.filter(t => t.brand === brand).filter((v, i, a) => a.findIndex(t => t.model === v.model) === i);
const findTerminalPivot = (line) => availableTerminals.value.find(t => t.id === line.selected_model_id && t.pivot.duration_months === line.selected_duration)?.pivot;
const assignTerminalPrices = (line) => {
    const pivot = findTerminalPivot(line);
    line.initial_cost = parseFloat(pivot?.initial_cost || 0);
    line.monthly_cost = parseFloat(pivot?.monthly_cost || 0);
    line.terminal_pivot = pivot;
};
const addLine = () => lines.value.push({ id: Date.now(), is_extra: true, is_portability: false, phone_number: '', source_operator: null, has_vap: false, o2o_discount_id: null, selected_brand: null, selected_model_id: null, selected_duration: null, terminal_pivot: null, initial_cost: 0, monthly_cost: 0 });
const removeLine = (index) => { if(lines.value[index].is_extra) lines.value.splice(index, 1); };
const addInternetLine = () => additionalInternetLines.value.push({ id: Date.now(), addon_id: null });
const removeInternetLine = (index) => additionalInternetLines.value.splice(index, 1);
const getDurationsForModel = (line) => [...new Set(availableTerminals.value.filter(t => t.id === line.selected_model_id).map(t => t.pivot.duration_months))].sort((a,b)=>a-b);
const getO2oDiscountsForLine = (line, index) => {
    const promoLimit = mobileAddonInfo.value?.pivot.line_limit;
    const isPromo = line.is_extra && (lines.value.slice(0, index).filter(l => l.is_extra).length < promoLimit);
    return isPromo ? availableO2oDiscounts.value.filter(d => (parseFloat(d.total_discount_amount) / parseFloat(d.duration_months)) <= 1) : availableO2oDiscounts.value;
};
const appliedDiscount = computed(() => {
    if (!lines.value[0]?.is_portability || !selectedPackage.value) return null;
    const line = lines.value[0];
    const pkgName = selectedPackage.value.name;
    return props.discounts.find(d => 
        (!d.conditions.package_names || d.conditions.package_names.includes(pkgName)) &&
        d.conditions.requires_vap === line.has_vap &&
        !d.conditions.excluded_operators?.includes(line.source_operator) &&
        (!d.conditions.source_operators || d.conditions.source_operators.includes(line.source_operator))
    );
});
const calculationSummary = computed(() => {
    if (!selectedPackage.value) {
        return { basePrice: 0, finalPrice: 0, appliedO2oList: [], totalTerminalFee: 0, totalInitialPayment: 0, extraLinesCost: 0, totalCommission: 0, teamCommission: 0, userCommission: 0, commissionDetails: {} };
    }

    let price = parseFloat(selectedPackage.value.base_price) || 0;
    const basePrice = price;
    let commissionDetails = {
        Fibra: [],
        Centralita: [],
        "Líneas Móviles": [],
        Terminales: [],
        Ajustes: [],
    };

    // Aquí va toda tu lógica de cálculo de `Create.vue`...
    
    const totalCommission = Object.values(commissionDetails).flat().reduce((acc, item) => acc + item.amount, 0);
    const currentUser = props.auth.user;
    let teamCommission = 0;
    let userCommission = 0;

    if (currentUser.role === 'admin') {
        userCommission = totalCommission;
        teamCommission = totalCommission;
    } 
    else if (currentUser.team) {
        const teamPercentage = currentUser.team.commission_percentage || 0;
        teamCommission = totalCommission * (parseFloat(teamPercentage) / 100);

        if (currentUser.role === 'user') {
            const userPercentage = currentUser.commission_percentage || 0;
            userCommission = teamCommission * (parseFloat(userPercentage) / 100);
        } else {
            userCommission = teamCommission;
        }
    }
    else {
        const userPercentage = currentUser.commission_percentage || 0;
        userCommission = totalCommission * (parseFloat(userPercentage) / 100);
        teamCommission = 0;
    }

    return {
        basePrice: basePrice.toFixed(2),
        finalPrice: Math.max(0, price).toFixed(2),
        totalCommission: totalCommission.toFixed(2),
        teamCommission: teamCommission.toFixed(2),
        userCommission: userCommission.toFixed(2),
        // ...resto de propiedades
    };
});

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
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8 bg-white border-b border-gray-200">
                        <div class="mb-8 max-w-lg mx-auto">
                            <label for="package" class="block text-sm font-medium text-gray-700 mb-2">Paquete Base</label>
                            <input type="text" :value="selectedPackage?.name" id="package" disabled class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-100 cursor-not-allowed">
                        </div>
                        
                        <div v-if="selectedPackage" class="space-y-10">
                            
                            <div class="mt-10 p-6 bg-gray-100 rounded-lg space-y-3 max-w-2xl mx-auto sticky top-10">
                                <h2 class="text-xl font-semibold text-gray-800 text-center">{{ selectedPackage.name }}</h2>
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
                                </div>
                            </div>

                            <div v-if="internetAddonOptions.length > 0" class="max-w-lg mx-auto">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Elige la velocidad de la Fibra Principal</label>
                                <div class="flex space-x-4 mt-1">
                                    <label v-for="addon in internetAddonOptions" :key="addon.id"
                                        :class="['flex-1 text-center px-4 py-3 rounded-md border cursor-pointer transition', { 'bg-indigo-600 text-white border-indigo-600 shadow-lg': selectedInternetAddonId === addon.id, 'bg-white border-gray-300 hover:bg-gray-50': selectedInternetAddonId !== addon.id }]">
                                        <input type="radio" :value="addon.id" v-model="selectedInternetAddonId" class="sr-only">
                                        <span class="block font-semibold">{{ addon.name }}</span>
                                        <span class="block text-xs mt-1" v-if="parseFloat(addon.pivot.price) > 0">+{{ parseFloat(addon.pivot.price).toFixed(2) }}€/mes</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div v-if="tvAddonOptions.length > 0" class="max-w-3xl mx-auto space-y-4 p-6 bg-slate-50 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-800">Televisión y Deportes</h3>
                                <div class="space-y-2">
                                    <div v-for="addon in tvAddonOptions" :key="addon.id" class="flex items-center">
                                        <input :id="`tv_addon_${addon.id}`" :value="addon.id" v-model="selectedTvAddonIds" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <label :for="`tv_addon_${addon.id}`" class="ml-3 block text-sm text-gray-900">
                                            {{ addon.name }}
                                            <span v-if="parseFloat(addon.pivot.price) > 0" class="text-gray-600">(+{{ parseFloat(addon.pivot.price).toFixed(2) }}€)</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div v-if="centralitaAddonOptions.length > 0 || includedCentralita" class="max-w-3xl mx-auto space-y-4 p-6 bg-slate-50 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">Centralita Virtual</h3>
                                <div v-if="includedCentralita" class="p-4 bg-green-100 border border-green-300 rounded-md text-center">
                                    <p class="font-semibold text-green-800">✅ {{ includedCentralita.name }} Incluida</p>
                                </div>
                                <div v-else-if="centralitaAddonOptions.length > 0">
                                    <label for="centralita_optional" class="block text-sm font-medium text-gray-700">Añadir Centralita</label>
                                    <select v-model="selectedCentralitaId" id="centralita_optional" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option :value="null">No añadir centralita</option>
                                        <option v-for="centralita in centralitaAddonOptions" :key="centralita.id" :value="centralita.id">
                                            {{ centralita.name }} (+{{ parseFloat(centralita.pivot.price).toFixed(2) }}€)
                                        </option>
                                    </select>
                                </div>
                                <div v-if="isCentralitaActive" class="space-y-4 pt-4 border-t border-dashed">
                                    <div v-if="operadoraAutomaticaInfo">
                                        <div v-if="operadoraAutomaticaInfo.pivot.is_included" class="p-2 bg-gray-100 rounded-md text-sm text-gray-800">
                                            ✅ Operadora Automática Incluida
                                        </div>
                                        <div v-else class="flex items-center">
                                            <input v-model="isOperadoraAutomaticaSelected" id="operadora_automatica_cb" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <label for="operadora_automatica_cb" class="ml-2 block text-sm text-gray-900">
                                                Añadir Operadora Automática (+{{ parseFloat(operadoraAutomaticaInfo.pivot.price).toFixed(2) }}€)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="pt-2">
                                        <div v-if="includedCentralitaExtensions.length > 0" class="mb-4 space-y-2">
                                            <p class="text-sm font-medium text-gray-700">Extensiones Incluidas por Paquete:</p>
                                            <div v-for="ext in includedCentralitaExtensions" :key="`inc_${ext.id}`" class="p-2 bg-gray-100 rounded-md text-sm text-gray-800">
                                                ✅ {{ ext.pivot.included_quantity }}x {{ ext.name }}
                                            </div>
                                        </div>
                                        <div v-if="autoIncludedExtension" class="mb-4">
                                            <p class="text-sm font-medium text-gray-700">Extensión Incluida con Centralita:</p>
                                            <div class="p-2 bg-gray-100 rounded-md text-sm text-gray-800">
                                                 ✅ 1x {{ autoIncludedExtension.name }}
                                            </div>
                                        </div>
                                        <p class="text-sm font-medium text-gray-700">Añadir Extensiones Adicionales:</p>
                                        <div v-for="extension in centralitaExtensions" :key="extension.id" class="flex items-center justify-between mt-2">
                                            <label :for="`ext_add_${extension.id}`" class="text-gray-800">{{ extension.name }} (+{{ parseFloat(extension.price).toFixed(2) }}€)</label>
                                            <input :id="`ext_add_${extension.id}`" type="number" min="0" v-model.number="centralitaExtensionQuantities[extension.id]" class="w-20 rounded-md border-gray-300 shadow-sm text-center focus:border-indigo-500 focus:ring-indigo-500" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="max-w-3xl mx-auto space-y-4 p-6 bg-slate-50 rounded-lg">
                                 <h3 class="text-lg font-semibold text-gray-800">Líneas de Internet Adicionales</h3>
                                <div v-for="(line, index) in additionalInternetLines" :key="line.id" class="p-4 border rounded-lg bg-blue-50 border-blue-200 flex items-center justify-between">
                                    <div class="flex-1">
                                        <label class="block text-xs font-medium text-gray-500">Velocidad Línea Adicional {{ index + 1 }}</label>
                                        <select v-model="line.addon_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option :value="null" disabled>-- Selecciona --</option>
                                            <option v-for="addon in additionalInternetAddons" :key="addon.id" :value="addon.id">
                                                {{ addon.name }} (+{{ parseFloat(addon.price).toFixed(2) }}€)
                                            </option>
                                        </select>
                                    </div>
                                    <button @click="removeInternetLine(index)" class="ml-4 text-red-500 hover:text-red-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                                <PrimaryButton @click="addInternetLine">Añadir Internet Adicional</PrimaryButton>
                            </div>

                            <div class="space-y-6">
                                <h3 class="text-lg font-semibold text-gray-800 text-center">Líneas Móviles</h3>
                                <div v-for="(line, index) in lines" :key="line.id" class="p-6 border rounded-lg max-w-4xl mx-auto" :class="{'bg-gray-50 border-gray-200': !line.is_extra, 'bg-green-50 border-green-200': line.is_extra}">
                                    <div class="grid grid-cols-12 gap-4 items-center mb-4">
                                        <div class="col-span-12 md:col-span-3 flex justify-between items-center">
                                            <span class="font-medium text-gray-700">{{ index === 0 ? 'Línea Principal' : `Línea ${index + 1}` }}</span>
                                            <button v-if="line.is_extra" @click="removeLine(index)" class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-100">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                        <div class="col-span-12 md:col-span-5">
                                            <label class="block text-xs font-medium text-gray-500">Nº Teléfono</label>
                                            <input v-model="line.phone_number" type="tel" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ej: 612345678">
                                        </div>
                                        <div class="col-span-12 md:col-span-4 flex items-end pb-1">
                                            <div class="flex items-center h-full">
                                                <input v-model="line.is_portability" :id="`portability_${line.id}`" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                <label :for="`portability_${line.id}`" class="ml-2 block text-sm text-gray-900">¿Es Portabilidad?</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="line.is_portability" class="space-y-4 border-t pt-4 mt-4">
                                        <div class="grid grid-cols-12 gap-4 items-center">
                                            <div class="col-span-12 md:col-span-8">
                                                <label class="block text-xs font-medium text-gray-500">Operador Origen</label>
                                                <select v-model="line.source_operator" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option :value="null" disabled>-- Selecciona --</option>
                                                    <option v-for="op in operators" :key="op" :value="op">{{ op }}</option>
                                                </select>
                                            </div>
                                            <div class="col-span-12 md:col-span-4 flex items-end pb-1">
                                                <div class="flex items-center h-full">
                                                    <input v-model="line.has_vap" :id="`vap_${line.id}`" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                    <label :for="`vap_${line.id}`" class="ml-2 block text-sm text-gray-900">con VAP</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Descuento O2O</label>
                                                <select v-model="line.o2o_discount_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option :value="null">-- Sin subvención --</option>
                                                    <option v-for="o2o in getO2oDiscountsForLine(line, index)" :key="o2o.id" :value="o2o.id">{{ o2o.name }}</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div v-if="line.has_vap" class="space-y-4 pt-4 border-t border-dashed">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div class="grid grid-cols-3 gap-2">
                                                    <div><label class="block text-sm font-medium text-gray-700">Marca</label><select v-model="line.selected_brand" @change="line.selected_model_id = null; line.selected_duration = null; assignTerminalPrices(line);" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"><option :value="null">-- Marca --</option><option v-for="brand in brandsForSelectedPackage" :key="brand" :value="brand">{{ brand }}</option></select></div>
                                                    <div><label class="block text-sm font-medium text-gray-700">Modelo</label><select v-model="line.selected_model_id" @change="line.selected_duration = null; assignTerminalPrices(line);" :disabled="!line.selected_brand" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"><option :value="null">-- Modelo --</option><option v-for="terminal in modelsByBrand(line.selected_brand)" :key="terminal.id" :value="terminal.id">{{ terminal.model }}</option></select></div>
                                                    <div><label class="block text-sm font-medium text-gray-700">Meses</label><select v-model="line.selected_duration" @change="assignTerminalPrices(line)" :disabled="!line.selected_model_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"><option :value="null">-- Meses --</option><option v-for="duration in getDurationsForModel(line)" :key="duration" :value="duration">{{ duration }} meses</option></select></div>
                                                </div>
                                            </div>
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div><label class="block text-sm font-medium text-gray-700">Pago Inicial (€)</label><input v-model.number="line.initial_cost" type="number" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                                                <div><label class="block text-sm font-medium text-gray-700">Cuota Mensual (€)</label><input v-model.number="line.monthly_cost" type="number" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-center pt-4">
                                <PrimaryButton @click="addLine">Añadir Línea Móvil Adicional</PrimaryButton>
                            </div>

                            <div class="mt-10 p-6 bg-gray-100 rounded-lg space-y-3 max-w-2xl mx-auto sticky top-10">
                                <h2 class="text-xl font-semibold text-gray-800 text-center">{{ selectedPackage.name }}</h2>
                                <div class="space-y-2">
                                    <div class="flex justify-between text-gray-600">
                                        <span>Precio Base:</span>
                                        <span class="font-medium">{{ calculationSummary.basePrice }}€</span>
                                    </div>
                                    <div v-if="appliedDiscount" class="flex justify-between text-green-600 font-semibold">
                                        <span>Descuento Tarifa ({{ appliedDiscount.percentage }}%):</span>
                                        <span>-{{ (calculationSummary.basePrice * (appliedDiscount.percentage / 100)).toFixed(2) }}€</span>
                                    </div>
                                    <div v-if="calculationSummary.appliedO2oList && calculationSummary.appliedO2oList.length > 0" class="border-t pt-2 mt-2">
                                        <h3 class="font-semibold text-blue-600">Subvenciones O2O:</h3>
                                        <div v-for="summary in calculationSummary.appliedO2oList" :key="summary.line" class="flex justify-between text-sm text-blue-600"><span>{{ summary.line }} ({{ summary.name }})</span><span>-{{ summary.value }}€</span></div>
                                    </div>
                                    <div v-if="parseFloat(calculationSummary.extraLinesCost) > 0" class="flex justify-between text-cyan-600">
                                        <span>Coste Líneas Adicionales:</span>
                                        <span>+{{ calculationSummary.extraLinesCost }}€</span>
                                    </div>
                                    <div v-if="parseFloat(calculationSummary.totalTerminalFee) > 0" class="flex justify-between text-purple-600">
                                       <span>Cuotas mensuales de Terminales:</span>
                                       <span>+{{ calculationSummary.totalTerminalFee }}€</span>
                                    </div>
                                </div>
                                <div class="border-t pt-4 mt-4 space-y-3">
                                    <div class="flex justify-between text-lg font-bold text-gray-800">
                                        <span>Pago Inicial Total:</span>
                                        <span>{{ calculationSummary.totalInitialPayment }}€</span>
                                    </div>
                                    <div class="flex justify-between text-3xl font-extrabold text-gray-900 items-baseline">
                                        <span>Precio Final:</span>
                                        <span>{{ calculationSummary.finalPrice }}<span class="text-lg font-medium text-gray-600">€/mes</span></span>
                                    </div>
                                </div>
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
                                </div>
                            </div>

                            <div class="mt-10 flex justify-center">
                                <PrimaryButton @click="saveOffer" :disabled="form.processing">
                                    Actualizar Oferta
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>