<script setup>
import { ref, computed, watch } from 'vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import { usePymeOfferCalculations } from '@/composables/PymeuseOfferCalculations';

// Props recibidas del Controlador (PymeOfferController.php)
const props = defineProps({
    clients: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
    discounts: { type: Array, default: () => [] },
    
    // Props de Addons
    allAddons: { type: Array, default: () => [] },
    centralitaMobileAddons: { type: Array, default: () => [] }, // MFO, Agente
    centralitaExtensions: { type: Array, default: () => [] },   // Básica, Inalámbrica...
    centralitaFeatures: { type: Array, default: () => [] },     // Operadora Automática
    fiberFeatures: { type: Array, default: () => [] },          // IP Fija, Fibra Oro

    operators: Array,
    auth: Object,
    offer: { type: Object, default: null }, 
});

const emit = defineEmits(['update:offerType']);

const tariffType = ref('OPTIMA'); 

// --- ESTADO PARA ADDONS SELECCIONADOS ---
const selectedCentralitaFeatures = ref({}); // { id: boolean }
const centralitaExtensionsQty = ref({}); // { id: cantidad }

// --- LÓGICA DE FILTRADO DE PAQUETES ---
const mobilePackages = computed(() => props.packages.filter(pkg => pkg.type === 'movil'));
const fixedPackages = computed(() => props.packages.filter(pkg => pkg.type === 'fija'));

// --- INICIALIZACIÓN DE LÍNEAS ---
const mobileLines = ref([createNewLine()]);
const fixedLines = ref([createFixedLine()]);

// --- COMPOSABLE DE CÁLCULOS (INTEGRADO) ---
// Pasamos todos los datos necesarios para que el composable haga los cálculos completos
const { 
    getPackagePrice, 
    calculateMobileLinePrice,      // <-- NOMBRE ACTUALIZADO
    calculateMobileLineCommission, // <-- NOMBRE ACTUALIZADO
    calculateFixedLinePrice,       // <-- NOMBRE ACTUALIZADO
    calculateFixedLineCommission,  // <-- NOMBRE ACTUALIZADO
    totalCommission 
} = usePymeOfferCalculations(
    props.packages, 
    mobileLines, 
    fixedLines, 
    tariffType, 
    props.discounts,
    { // Objeto addonsData
        mobileAddons: props.centralitaMobileAddons,
        fiberFeatures: props.fiberFeatures,
        extensions: props.centralitaExtensions,
        extensionsQty: centralitaExtensionsQty,
        features: props.centralitaFeatures,
        selectedFeatures: selectedCentralitaFeatures
    }
);

// --- FUNCIONES AUXILIARES (UI y Terminales) ---
const getAvailableBrands = (line) => {
    if (!line.package_id || !props.packages) return [];
    const pkg = props.packages.find(p => p.id === line.package_id);
    if (!pkg) return [];
    const terminals = line.terminal_type === 'VAP' ? pkg.terminals_vap : pkg.terminals_sub;
    if (!terminals) return [];
    return [...new Set(terminals.map(t => t.brand))].sort();
};

const getAvailableModels = (line) => {
    if (!line.package_id || !line.brand || !props.packages) return [];
    const pkg = props.packages.find(p => p.id === line.package_id);
    if (!pkg) return [];
    const terminals = line.terminal_type === 'VAP' ? pkg.terminals_vap : pkg.terminals_sub;
    if (!terminals) return [];
    return terminals.filter(t => t.brand === line.brand);
};

const updateTerminalPrices = (line) => {
    if (!line.package_id || !line.terminal_id) return;
    const pkg = props.packages.find(p => p.id === line.package_id);
    if (!pkg) return;
    const collection = line.terminal_type === 'VAP' ? pkg.terminals_vap : pkg.terminals_sub;
    const terminalData = collection.find(t => t.id === line.terminal_id);

    if (terminalData && terminalData.pivot) {
        line.model = terminalData.model; 
        if (line.terminal_type === 'VAP') {
            line.vap_initial_payment = parseFloat(terminalData.pivot.initial_cost || 0);
            line.vap_monthly_payment = parseFloat(terminalData.pivot.monthly_cost || 0);
        } else {
            line.sub_cession_price = parseFloat(terminalData.pivot.cession_price || 0);
            line.sub_subsidy_price = parseFloat(terminalData.pivot.subsidy_price || 0);
        }
    }
};

const resetTerminalSelection = (line) => {
    line.brand = ''; line.model = ''; line.terminal_id = null;
    line.vap_initial_payment = 0; line.vap_monthly_payment = 0;
    line.sub_cession_price = 0; line.sub_subsidy_price = 0;
};

function createNewLine() {
    const defaultPackageId = (mobilePackages.value && mobilePackages.value.length > 0) ? mobilePackages.value[0].id : null;
    const zeroDiscount = props.discounts ? props.discounts.find(d => parseFloat(d.percentage) === 0) : null;
    const defaultDiscountId = zeroDiscount ? zeroDiscount.id : (props.discounts && props.discounts.length > 0 ? props.discounts[0].id : null);

    return {
        id: Date.now() + Math.random(), quantity: 1, type: 'portabilidad',
        package_id: defaultPackageId, cp_duration: 0, o2o_discount_id: defaultDiscountId,
        has_terminal: 'no', terminal_type: 'VAP', brand: '', model: '', terminal_id: null, 
        vap_initial_payment: 0, vap_monthly_payment: 0, sub_cession_price: 0, sub_subsidy_price: 0,
        has_mfo: false, has_agente: false
    };
}

function createFixedLine() {
    const defaultFixedPackageId = (fixedPackages.value && fixedPackages.value.length > 0) ? fixedPackages.value[0].id : null;
    return { 
        id: Date.now(), quantity: 1, package_id: defaultFixedPackageId, discount: 0,
        has_ip_fija: false, has_fibra_oro: false
    };
}

const addMobileLine = () => mobileLines.value.push(createNewLine());
const removeMobileLine = (index) => mobileLines.value.splice(index, 1);
const addFixedLine = () => fixedLines.value.push(createFixedLine());
const removeFixedLine = (index) => fixedLines.value.splice(index, 1);
const goBack = () => emit('update:offerType', null);

const saveOffer = () => {
    console.log("Guardando Oferta PYME...", {
        mobileLines: mobileLines.value,
        fixedLines: fixedLines.value,
        centralitaFeatures: selectedCentralitaFeatures.value,
        extensions: centralitaExtensionsQty.value
    });
    // Aquí iría la llamada al backend para guardar
};
</script>

<template>
    <div class="py-12 bg-gray-100 min-h-screen">
        <div class="max-w-[98%] mx-auto space-y-6">
            
            <!-- CABECERA -->
            <div class="flex justify-between items-center mb-4 bg-white p-4 rounded shadow-sm">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <span class="w-3 h-8 bg-orange-500 rounded-sm inline-block"></span>
                        Oferta PYME / Empresas
                    </h2>
                </div>
                <SecondaryButton @click="goBack">&larr; Volver al selector</SecondaryButton>
            </div>

            <!-- SELECTOR TIPO TARIFA -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-center mb-2">
                    <div class="flex space-x-1 bg-gray-100 p-1 rounded-lg">
                        <button @click="tariffType = 'OPTIMA'" class="px-6 py-2 rounded-md text-sm font-bold transition-all duration-200" :class="tariffType === 'OPTIMA' ? 'bg-white text-orange-600 shadow' : 'text-gray-500 hover:text-gray-700'">TARIFA ÓPTIMA</button>
                        <button @click="tariffType = 'PERSONALIZADA'" class="px-6 py-2 rounded-md text-sm font-bold transition-all duration-200" :class="tariffType === 'PERSONALIZADA' ? 'bg-white text-blue-600 shadow' : 'text-gray-500 hover:text-gray-700'">SOLUCIÓN PERSONALIZADA</button>
                    </div>
                </div>
            </div>

            <!-- 1. LÍNEAS MÓVILES -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-orange-500">
                <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">Líneas Móviles</h3>
                    <div class="bg-blue-50 text-blue-800 px-4 py-2 rounded-md border border-blue-200 shadow-sm flex flex-col items-end">
                        <span class="text-[10px] uppercase font-bold tracking-wider text-blue-600">Comisión Estimada Total</span>
                        <span class="text-xl font-bold">{{ totalCommission.toFixed(2) }} €</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1400px] text-xs border-collapse">
                        <thead>
                            <tr class="bg-yellow-300 text-gray-800 font-bold text-center uppercase border-b-2 border-orange-400">
                                <th class="p-2 border border-gray-300 w-14 bg-yellow-200">Nº</th>
                                <th class="p-2 border border-gray-300 w-24">Tipo Alta</th>
                                <th class="p-2 border border-gray-300 w-36">Tarifa</th>
                                <th class="p-2 border border-gray-300 w-16">CP</th>
                                <th class="p-2 border border-gray-300 w-12 bg-green-100">MFO</th>
                                <th class="p-2 border border-gray-300 w-12 bg-green-100">Agente</th>
                                <th class="p-2 border border-gray-300 w-16">Term?</th>
                                <th class="p-2 border border-gray-300 w-20">Mod.</th>
                                <th class="p-2 border border-gray-300 w-40">Terminal</th>
                                <th class="p-2 border border-gray-300 w-24">O2O</th>
                                <th class="p-2 border border-gray-300 w-20 bg-green-50 text-green-900">PVP U.</th>
                                <th class="p-2 border border-gray-300 w-20 bg-blue-50 text-blue-900">Com. U.</th>
                                <th class="p-2 border border-gray-300 w-24 bg-blue-100 text-blue-900 font-extrabold">TOTAL</th>
                                <th class="p-2 border border-gray-300 w-8"></th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700">
                            <tr v-for="(line, index) in mobileLines" :key="line.id" class="hover:bg-gray-50 text-center group">
                                <td class="p-1 border border-gray-200"><input type="number" v-model="line.quantity" min="1" class="w-full text-center border-0 bg-transparent focus:ring-1 focus:ring-orange-500 font-bold text-lg p-0" /></td>
                                <td class="p-1 border border-gray-200">
                                    <select v-model="line.type" class="w-full text-xs border-gray-200 rounded p-1"><option value="portabilidad">Porta</option><option value="alta_nueva">Alta</option><option value="migracion">Migra</option></select>
                                </td>
                                <td class="p-1 border border-gray-200">
                                    <select v-model="line.package_id" @change="resetTerminalSelection(line)" class="w-full text-xs border-gray-200 rounded font-bold p-1">
                                        <option v-for="pkg in mobilePackages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
                                    </select>
                                </td>
                                <td class="p-1 border border-gray-200">
                                    <select v-model="line.cp_duration" class="w-full text-xs border-gray-200 rounded text-center p-1"><option :value="12">12m</option><option :value="24">24m</option><option :value="36">36m</option></select>
                                </td>
                                <td class="p-1 border border-gray-200 bg-green-50 text-center"><input type="checkbox" v-model="line.has_mfo" class="rounded text-green-600 h-4 w-4"></td>
                                <td class="p-1 border border-gray-200 bg-green-50 text-center"><input type="checkbox" v-model="line.has_agente" class="rounded text-green-600 h-4 w-4"></td>
                                <td class="p-1 border border-gray-200"><select v-model="line.has_terminal" class="w-full text-xs border-gray-200 rounded p-1"><option value="no">NO</option><option value="si">SI</option></select></td>
                                <td class="p-1 border border-gray-200 bg-gray-50">
                                    <select v-if="line.has_terminal === 'si'" v-model="line.terminal_type" @change="resetTerminalSelection(line)" class="w-full text-[10px] bg-transparent border-0 text-center p-0"><option value="VAP">VAP</option><option value="SUBVENCIONADO">Sub</option></select>
                                    <span v-else class="text-gray-300">-</span>
                                </td>
                                <td class="p-1 border border-gray-200 text-left bg-white">
                                    <div v-if="line.has_terminal === 'si'" class="flex flex-col gap-1">
                                        <div class="flex gap-1">
                                            <select v-model="line.brand" class="w-1/2 text-[9px] py-0 px-1 border-gray-200 h-6 rounded-sm bg-gray-50"><option value="" disabled>Marca</option><option v-for="b in getAvailableBrands(line)" :key="b" :value="b">{{ b }}</option></select>
                                            <select v-model="line.terminal_id" @change="updateTerminalPrices(line)" :disabled="!line.brand" class="w-1/2 text-[9px] py-0 px-1 border-gray-200 h-6 rounded-sm bg-gray-50"><option :value="null" disabled>Modelo</option><option v-for="t in getAvailableModels(line)" :key="t.id" :value="t.id">{{ t.model }}</option></select>
                                        </div>
                                    </div>
                                    <div v-else class="text-center text-gray-400 italic text-[10px]">Sin terminal</div>
                                </td>
                                <td class="p-1 border border-gray-200">
                                    <select v-model="line.o2o_discount_id" class="w-full text-[10px] font-bold text-center border-2 border-yellow-400 rounded bg-white p-1"><option v-for="disc in discounts" :key="disc.id" :value="disc.id">{{ disc.name }}</option></select>
                                </td>
                                <td class="p-1 border border-gray-200 bg-green-50 font-mono text-right pr-2 text-green-800 font-medium">
                                    <!-- CAMBIO: Usamos calculateMobileLinePrice -->
                                    {{ calculateMobileLinePrice(line).toFixed(2) }}€
                                    <div v-if="getPackagePrice(line.package_id) > calculateMobileLinePrice(line)" class="text-[9px] text-gray-400 line-through">{{ getPackagePrice(line.package_id).toFixed(2) }}€</div>
                                </td>
                                <td class="p-1 border border-gray-200 bg-blue-50 font-mono text-right pr-2 text-blue-700 font-bold">
                                    <!-- CAMBIO: Usamos calculateMobileLineCommission -->
                                    {{ calculateMobileLineCommission(line).toFixed(2) }}€
                                </td>
                                <td class="p-1 border border-gray-200 bg-blue-100 font-mono text-right pr-2 text-blue-900 font-extrabold text-sm border-l-2 border-blue-300">
                                    {{ (calculateMobileLineCommission(line) * line.quantity).toFixed(2) }}€
                                </td>
                                <td class="p-1 border border-gray-200 text-center"><button @click="removeMobileLine(index)" class="text-red-400 hover:text-red-600">&times;</button></td>
                            </tr>
                            <tr class="bg-gray-50 border-t-2 border-orange-200"><td colspan="14" class="p-2 text-center"><button @click="addMobileLine" class="text-orange-600 font-bold text-xs uppercase">+ Añadir Línea</button></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- 2. OFERTA FIJA -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-purple-500 mt-8">
                <div class="p-4 border-b bg-gray-50"><h3 class="text-lg font-bold text-gray-800 uppercase">Oferta Fija</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] text-xs border-collapse">
                        <thead>
                            <tr class="bg-purple-100 text-gray-800 font-bold text-center uppercase border-b-2 border-purple-300">
                                <th class="p-2 border border-gray-300 w-16">Cant.</th>
                                <th class="p-2 border border-gray-300">Paquete / Tarifa Fija</th> 
                                <th class="p-2 border border-gray-300 w-24">Descuento</th>
                                <th class="p-2 border border-gray-300 w-16 bg-blue-100">IP Fija</th>
                                <th class="p-2 border border-gray-300 w-16 bg-yellow-100">Oro</th>
                                <th class="p-2 border border-gray-300 w-24">PVP Unit.</th>
                                <th class="p-2 border border-gray-300 w-24 bg-blue-50 text-blue-900">Comisión</th>
                                <th class="p-2 border border-gray-300 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700">
                            <tr v-for="(line, index) in fixedLines" :key="line.id" class="hover:bg-gray-50 text-center">
                                <td class="p-1 border border-gray-200"><input type="number" v-model="line.quantity" min="1" class="w-full text-center border-0 bg-transparent font-bold" /></td>
                                <td class="p-1 border border-gray-200 text-left">
                                    <select v-model="line.package_id" class="w-full text-xs border-gray-200 rounded font-bold text-gray-800 focus:border-purple-500 p-1">
                                        <option v-for="pkg in fixedPackages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
                                    </select>
                                </td>
                                <td class="p-1 border border-gray-200"><select v-model="line.discount" class="w-full text-xs border-gray-200 rounded text-center"><option :value="0">Sin Dto</option><option :value="10">10%</option></select></td>
                                <td class="p-1 border border-gray-200 bg-blue-50 text-center"><input type="checkbox" v-model="line.has_ip_fija" class="rounded text-blue-600 h-4 w-4"></td>
                                <td class="p-1 border border-gray-200 bg-yellow-50 text-center"><input type="checkbox" v-model="line.has_fibra_oro" class="rounded text-yellow-600 h-4 w-4"></td>
                                <td class="p-1 border border-gray-200 text-right pr-2 font-mono bg-purple-50">
                                    <!-- CAMBIO: Usamos calculateFixedLinePrice -->
                                    {{ calculateFixedLinePrice(line).toFixed(2) }} €
                                </td>
                                <td class="p-1 border border-gray-200 bg-blue-50 font-mono text-right pr-2 text-blue-700 font-bold">
                                    <!-- CAMBIO: Usamos calculateFixedLineCommission -->
                                    {{ calculateFixedLineCommission(line).toFixed(2) }}€
                                </td>
                                <td class="p-1 border border-gray-200"><button @click="removeFixedLine(index)" class="text-red-400 hover:text-red-600">&times;</button></td>
                            </tr>
                            <tr class="bg-gray-50 border-t-2 border-purple-200"><td colspan="8" class="p-2 text-center"><button @click="addFixedLine" class="text-purple-600 font-bold text-xs uppercase">+ Añadir Fijo</button></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. SECCIÓN: EXTRAS CENTRALITA -->
            <div class="bg-white p-6 rounded-lg shadow-sm border-t-4 border-green-500 mt-8">
                <h3 class="font-bold text-lg text-gray-800 mb-4">Centralita y Puesto de Voz</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Extensiones</h4>
                        <div v-if="centralitaExtensions.length > 0">
                            <div v-for="ext in centralitaExtensions" :key="ext.id" class="flex items-center justify-between mb-2">
                                <span class="text-sm">{{ ext.name }}</span>
                                <div class="flex items-center gap-2"><span class="text-xs text-gray-500">{{ ext.price }}€</span><input type="number" v-model="centralitaExtensionsQty[ext.id]" min="0" class="w-16 h-8 text-sm border-gray-300 rounded text-center" placeholder="0"></div>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-400 italic mb-2">No hay extensiones disponibles.</div>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-gray-500 uppercase mb-2">Funcionalidades</h4>
                        <div v-if="centralitaFeatures.length > 0">
                            <div v-for="feat in centralitaFeatures" :key="feat.id" class="flex items-center justify-between p-2 bg-green-50 rounded mb-1">
                                <label class="flex items-center space-x-2"><input type="checkbox" v-model="selectedCentralitaFeatures[feat.id]" class="rounded text-green-600"><span class="text-sm font-medium">{{ feat.name }}</span></label>
                                <span class="text-xs font-mono text-green-700">{{ feat.price }} €/mes</span>
                            </div>
                        </div>
                         <div v-else class="text-sm text-gray-400 italic mb-2">No hay funcionalidades extra disponibles.</div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-center mt-8 pb-12">
                <PrimaryButton @click="saveOffer" class="px-10 py-4 text-lg">💾 Guardar Oferta PYME</PrimaryButton>
            </div>
        </div>
    </div>
</template>