<script setup>
import { ref, computed, watch } from 'vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { usePymeOfferCalculations } from '@/composables/PymeuseOfferCalculations';

// Props recibidas
const props = defineProps({
    clients: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] }, // Aquí vienen todos los paquetes (móvil y fija)
    discounts: { type: Array, default: () => [] }, // Aquí vienen los O2O con sus penalizaciones
    
    allAddons: Array,
    operators: Array,
    portabilityCommission: Number,
    additionalInternetAddons: Array,
    centralitaExtensions: Array,
    auth: Object,
    initialClientId: [Number, String, null],
    probabilityOptions: Array,
    portabilityExceptions: Array,
    fiberFeatures: Array,
    offer: { type: Object, default: null }, 
});

const emit = defineEmits(['update:offerType']);

const tariffType = ref('OPTIMA'); 

// --- LÓGICA DE FILTRADO DE PAQUETES ---
const mobilePackages = computed(() => {
    // Filtra para mostrar solo paquetes de tipo 'movil'
    return props.packages.filter(pkg => pkg.type === 'movil');
});

const fixedPackages = computed(() => {
    // Filtra para mostrar solo paquetes de tipo 'fija'
    return props.packages.filter(pkg => pkg.type === 'fija');
});
// ---------------------------------------


const mobileLines = ref([createNewLine()]);
const fixedLines = ref([createFixedLine()]);

// Pasamos 'props.discounts' al composable para que pueda calcular las penalizaciones
const { getPackagePrice, calculateLinePrice, calculateLineCommission, totalCommission } = usePymeOfferCalculations(
    props.packages, 
    mobileLines, 
    tariffType, 
    props.discounts
);

// --- LÓGICA DE AUTOCOMPLETADO DE PRECIOS ---

// 1. Obtener Marcas Disponibles según Paquete y Tipo (VAP/SUB)
const getAvailableBrands = (line) => {
    if (!line.package_id || !props.packages) return [];
    const pkg = props.packages.find(p => p.id === line.package_id);
    if (!pkg) return [];

    const terminals = line.terminal_type === 'VAP' ? pkg.terminals_vap : pkg.terminals_sub;
    
    if (!terminals) return [];
    return [...new Set(terminals.map(t => t.brand))].sort();
};

// 2. Obtener Modelos Disponibles según Marca
const getAvailableModels = (line) => {
    if (!line.package_id || !line.brand || !props.packages) return [];
    const pkg = props.packages.find(p => p.id === line.package_id);
    if (!pkg) return [];

    const terminals = line.terminal_type === 'VAP' ? pkg.terminals_vap : pkg.terminals_sub;
    
    if (!terminals) return [];
    return terminals.filter(t => t.brand === line.brand);
};

// 3. Actualizar Precios al seleccionar Modelo
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
    line.brand = '';
    line.model = '';
    line.terminal_id = null;
    line.vap_initial_payment = 0;
    line.vap_monthly_payment = 0;
    line.sub_cession_price = 0;
    line.sub_subsidy_price = 0;
};

function createNewLine() {
    // Usar el primer paquete móvil como valor por defecto
    const defaultPackageId = (mobilePackages.value && mobilePackages.value.length > 0) ? mobilePackages.value[0].id : null;
    
    // Buscar el descuento del 0% para ponerlo por defecto
    const zeroDiscount = props.discounts ? props.discounts.find(d => parseFloat(d.percentage) === 0) : null;
    const defaultDiscountId = zeroDiscount ? zeroDiscount.id : (props.discounts && props.discounts.length > 0 ? props.discounts[0].id : null);

    return {
        id: Date.now() + Math.random(),
        quantity: 1,
        type: 'portabilidad',
        package_id: defaultPackageId, // <-- Usamos el paquete móvil por defecto
        cp_duration: 0, // Por defecto 0, el usuario puede cambiar a 24 o 36
        o2o_discount_id: defaultDiscountId,
        has_terminal: 'no',
        terminal_type: 'VAP',
        brand: '', 
        model: '', 
        terminal_id: null, 
        vap_initial_payment: 0,
        vap_monthly_payment: 0,
        sub_cession_price: 0,
        sub_subsidy_price: 0,
    };
}

function createFixedLine() {
    // Usar el primer paquete fijo como valor por defecto y usar package_id
    const defaultFixedPackageId = (fixedPackages.value && fixedPackages.value.length > 0) ? fixedPackages.value[0].id : null;
    return { 
        id: Date.now(), 
        quantity: 1, 
        package_id: defaultFixedPackageId, // <-- Usamos el paquete fijo por defecto
        discount: 0 
    };
}

const addMobileLine = () => mobileLines.value.push(createNewLine());
const removeMobileLine = (index) => mobileLines.value.splice(index, 1);
const addFixedLine = () => fixedLines.value.push(createFixedLine());
const removeFixedLine = (index) => fixedLines.value.splice(index, 1);
const goBack = () => emit('update:offerType', null);
</script>

<template>
    <div class="py-12 bg-gray-100 min-h-screen">
        <div class="max-w-[98%] mx-auto space-y-6">
            
            <div class="flex justify-between items-center mb-4 bg-white p-4 rounded shadow-sm">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <span class="w-3 h-8 bg-orange-500 rounded-sm inline-block"></span>
                        Oferta PYME / Empresas
                    </h2>
                </div>
                <SecondaryButton @click="goBack">&larr; Volver al selector</SecondaryButton>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-center mb-2">
                    <div class="flex space-x-1 bg-gray-100 p-1 rounded-lg">
                        <button @click="tariffType = 'OPTIMA'" class="px-6 py-2 rounded-md text-sm font-bold transition-all duration-200" :class="tariffType === 'OPTIMA' ? 'bg-white text-orange-600 shadow' : 'text-gray-500 hover:text-gray-700'">TARIFA ÓPTIMA</button>
                        <button @click="tariffType = 'PERSONALIZADA'" class="px-6 py-2 rounded-md text-sm font-bold transition-all duration-200" :class="tariffType === 'PERSONALIZADA' ? 'bg-white text-blue-600 shadow' : 'text-gray-500 hover:text-gray-700'">SOLUCIÓN PERSONALIZADA</button>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-orange-500">
                <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">Líneas Móviles</h3>
                    <div class="bg-blue-50 text-blue-800 px-4 py-2 rounded-md border border-blue-200 shadow-sm flex flex-col items-end">
                        <span class="text-[10px] uppercase font-bold tracking-wider text-blue-600">Comisión Estimada Total</span>
                        <span class="text-xl font-bold">{{ totalCommission.toFixed(2) }} €</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1300px] text-xs border-collapse">
                        <thead>
                            <tr class="bg-yellow-300 text-gray-800 font-bold text-center uppercase border-b-2 border-orange-400">
                                <th class="p-2 border border-gray-300 w-14 bg-yellow-200">Nº</th>
                                <th class="p-2 border border-gray-300 w-28">Tipo Alta</th>
                                <th class="p-2 border border-gray-300 w-40">Tarifa</th>
                                <th class="p-2 border border-gray-300 w-24">CP (Meses)</th>
                                <th class="p-2 border border-gray-300 w-16">Term?</th>
                                <th class="p-2 border border-gray-300 w-24">Modalidad</th>
                                <th class="p-2 border border-gray-300 w-40">Terminal</th>
                                <th class="p-2 border border-gray-300 w-28">O2O</th>
                                <th class="p-2 border border-gray-300 w-20 bg-green-100 text-green-900">PVP Unit.</th>
                                <th class="p-2 border border-gray-300 w-20 bg-blue-100 text-blue-900">Comisión U.</th>
                                <th class="p-2 border border-gray-300 w-24 bg-blue-200 text-blue-900 font-extrabold">TOTAL COM.</th>
                                <th class="p-2 border border-gray-300 w-8"></th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700">
                            <tr v-for="(line, index) in mobileLines" :key="line.id" class="hover:bg-gray-50 text-center group">
                                <td class="p-1 border border-gray-200"><input type="number" v-model="line.quantity" min="1" class="w-full text-center border-0 bg-transparent focus:ring-1 focus:ring-orange-500 font-bold text-lg p-0" /></td>
                                <td class="p-1 border border-gray-200">
                                    <select v-model="line.type" class="w-full text-xs border-gray-200 rounded focus:border-orange-500 bg-yellow-50 p-1">
                                        <option value="portabilidad">Portabilidad</option>
                                        <option value="alta_nueva">Alta Nueva</option>
                                        <option value="migracion">Migración</option>
                                    </select>
                                </td>
                                <td class="p-1 border border-gray-200">
                                    <select v-model="line.package_id" @change="resetTerminalSelection(line)" class="w-full text-xs border-gray-200 rounded font-bold text-gray-800 focus:border-orange-500 p-1">
                                        <option v-for="pkg in mobilePackages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
                                    </select>
                                </td>
                                <td class="p-1 border border-gray-200">
                                    <select v-model="line.cp_duration" class="w-full text-xs border-gray-200 rounded text-center focus:border-orange-500 p-1 font-medium">
                                        <option :value="12">12 Meses</option> <option :value="24">24 Meses</option>
                                        <option :value="36">36 Meses</option>
                                    </select>
                                </td>
                                <td class="p-1 border border-gray-200">
                                    <select v-model="line.has_terminal" class="w-full text-xs border-gray-200 rounded focus:border-orange-500 p-1">
                                        <option value="no">NO</option>
                                        <option value="si">SI</option>
                                    </select>
                                </td>
                                <td class="p-1 border border-gray-200 bg-gray-50">
                                    <select v-if="line.has_terminal === 'si'" v-model="line.terminal_type" @change="resetTerminalSelection(line)" class="w-full text-[10px] border-transparent bg-transparent focus:ring-0 text-center font-medium p-0">
                                        <option value="VAP">VAP</option>
                                        <option value="SUBVENCIONADO">Subvención</option>
                                    </select>
                                    <span v-else class="text-gray-300">-</span>
                                </td>
                                <td class="p-1 border border-gray-200 text-left bg-white">
                                    <div v-if="line.has_terminal === 'si'" class="space-y-1">
                                        <div class="flex gap-1">
                                            <select v-model="line.brand" class="w-1/2 text-[9px] py-0 px-1 border-gray-200 h-6 rounded-sm bg-gray-50 focus:ring-1">
                                                <option value="" disabled>Marca</option>
                                                <option v-for="b in getAvailableBrands(line)" :key="b" :value="b">{{ b }}</option>
                                            </select>
                                            <select v-model="line.terminal_id" @change="updateTerminalPrices(line)" :disabled="!line.brand" class="w-1/2 text-[9px] py-0 px-1 border-gray-200 h-6 rounded-sm bg-gray-50 focus:ring-1">
                                                <option :value="null" disabled>Modelo</option>
                                                <option v-for="t in getAvailableModels(line)" :key="t.id" :value="t.id">{{ t.model }}</option>
                                            </select>
                                        </div>
                                        <div v-if="line.terminal_type === 'VAP'" class="flex gap-1">
                                            <div class="relative w-1/2"><input v-model="line.vap_initial_payment" type="number" step="0.01" class="w-full text-[9px] border-blue-200 rounded px-1 h-6 bg-blue-50 text-right" /><span class="absolute left-1 top-1 text-[8px] text-blue-400">Ini</span></div>
                                            <div class="relative w-1/2"><input v-model="line.vap_monthly_payment" type="number" step="0.01" class="w-full text-[9px] border-blue-200 rounded px-1 h-6 bg-blue-50 text-right" /><span class="absolute left-1 top-1 text-[8px] text-blue-400">Mes</span></div>
                                        </div>
                                        <div v-else class="flex gap-1">
                                            <div class="relative w-1/2"><input v-model="line.sub_cession_price" type="number" step="0.01" class="w-full text-[9px] border-green-200 rounded px-1 h-6 bg-green-50 text-right" /><span class="absolute left-1 top-1 text-[8px] text-green-600">Ces</span></div>
                                            <div class="relative w-1/2"><input v-model="line.sub_subsidy_price" type="number" step="0.01" class="w-full text-[9px] border-green-200 rounded px-1 h-6 bg-green-50 text-right" /><span class="absolute left-1 top-1 text-[8px] text-green-600">Sub</span></div>
                                        </div>
                                    </div>
                                    <div v-else class="text-center text-gray-400 italic text-[10px] p-1">Sin terminal</div>
                                </td>
                                <td class="p-1 border border-gray-200">
                                    <select v-model="line.o2o_discount_id" class="w-full text-[10px] font-bold text-center border-2 border-yellow-400 rounded bg-white focus:border-orange-500 p-1">
                                        <option v-for="disc in discounts" :key="disc.id" :value="disc.id">{{ disc.name }}</option>
                                    </select>
                                </td>
                                <td class="p-1 border border-gray-200 bg-green-50 font-mono text-right pr-2 text-green-800 font-medium">
                                    {{ calculateLinePrice(line).toFixed(2) }}€
                                    <div v-if="getPackagePrice(line.package_id) > calculateLinePrice(line)" class="text-[9px] text-gray-400 line-through">
                                        {{ getPackagePrice(line.package_id).toFixed(2) }}€
                                    </div>
                                </td>
                                <td class="p-1 border border-gray-200 bg-blue-50 font-mono text-right pr-2 text-blue-700 font-bold">{{ calculateLineCommission(line).toFixed(2) }}€</td>
                                <td class="p-1 border border-gray-200 bg-blue-100 font-mono text-right pr-2 text-blue-900 font-extrabold text-sm border-l-2 border-blue-300">{{ (calculateLineCommission(line) * line.quantity).toFixed(2) }}€</td>
                                <td class="p-1 border border-gray-200 text-center"><button @click="removeMobileLine(index)" class="text-red-400 hover:text-red-600">&times;</button></td>
                            </tr>
                            <tr class="bg-gray-50 border-t-2 border-orange-200">
                                <td colspan="12" class="p-2 text-center"><button @click="addMobileLine" class="text-orange-600 font-bold text-xs uppercase">+ Añadir Línea</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-purple-500 mt-8">
                <div class="p-4 border-b bg-gray-50"><h3 class="text-lg font-bold text-gray-800 uppercase">Oferta Fija</h3></div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] text-xs border-collapse">
                        <thead>
                            <tr class="bg-purple-100 text-gray-800 font-bold text-center uppercase border-b-2 border-purple-300">
                                <th class="p-2 border border-gray-300 w-16">Cant.</th>
                                <th class="p-2 border border-gray-300">Paquete / Tarifa Fija</th> <th class="p-2 border border-gray-300 w-32">Descuento</th>
                                <th class="p-2 border border-gray-300 w-24">PVP Unit.</th> <th class="p-2 border border-gray-300 w-10"></th>
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

                                <td class="p-1 border border-gray-200">
                                    <select v-model="line.discount" class="w-full text-xs border-gray-200 rounded text-center">
                                        <option :value="0">Sin Dto</option>
                                        <option :value="10">10%</option>
                                    </select>
                                </td>
                                
                                <td class="p-1 border border-gray-200 text-right pr-2 font-mono bg-purple-50">
                                    {{ getPackagePrice(line.package_id).toFixed(2) }} €
                                </td>
                                
                                <td class="p-1 border border-gray-200"><button @click="removeFixedLine(index)" class="text-red-400 hover:text-red-600">&times;</button></td>
                            </tr>
                            <tr class="bg-gray-50 border-t-2 border-purple-200">
                                <td colspan="5" class="p-2 text-center"><button @click="addFixedLine" class="text-purple-600 font-bold text-xs uppercase">+ Añadir Fijo</button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="flex justify-center mt-8 pb-12">
                <PrimaryButton class="px-10 py-4 text-lg">💾 Guardar Oferta PYME</PrimaryButton>
            </div>
        </div>
    </div>
</template>