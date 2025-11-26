<script setup>
import { ref } from 'vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

// IMPORTANTE: Importamos desde el archivo con el nombre correcto
import { usePymeOfferCalculations } from '@/composables/PymeuseOfferCalculations';

// Props recibidas
const props = defineProps({
    clients: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] }, 
    discounts: { type: Array, default: () => [] }, 
    
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

// --- 1. Estado del Selector de Tarifa ---
const tariffType = ref('OPTIMA'); 

// --- 2. Datos Maestros ---
const o2oOptions = [
    { label: 'Sin O2O (0%)', value: 0 },
    { label: '5%', value: 5 },
    { label: '10%', value: 10 },
    { label: '15%', value: 15 },
    { label: '20%', value: 20 },
    { label: '25%', value: 25 },
    { label: '30%', value: 30 },
    { label: '35%', value: 35 },
    { label: '40%', value: 40 },
];

// --- 3. Estado de las Líneas ---
const mobileLines = ref([createNewLine()]);
const fixedLines = ref([createFixedLine()]);

// --- 4. Integración del Composable ---
const { 
    getPackagePrice, 
    calculateLineCommission, 
    totalCommission 
} = usePymeOfferCalculations(props.packages, mobileLines, tariffType);

// --- 5. Funciones Auxiliares ---

function createNewLine() {
    const defaultPackageId = (props.packages && props.packages.length > 0) ? props.packages[0].id : null;
    
    // Buscar el descuento del 0% para ponerlo por defecto
    const zeroDiscount = props.discounts ? props.discounts.find(d => parseFloat(d.percentage) === 0) : null;
    const defaultDiscountId = zeroDiscount ? zeroDiscount.id : (props.discounts && props.discounts.length > 0 ? props.discounts[0].id : null);

    return {
        id: Date.now() + Math.random(),
        quantity: 1,
        type: 'portabilidad',
        package_id: defaultPackageId,
        cp_duration: 0,
        o2o_discount_id: defaultDiscountId,
        has_terminal: 'no',
        terminal_type: 'VAP',
        brand: '',
        model: '',
        initial_payment: 0,
        monthly_payment: 0,
        sub_cession_price: 0,
        sub_subsidy_price: 0,
    };
}

function createFixedLine() {
    return {
        id: Date.now() + Math.random(),
        quantity: 1,
        name: '',
        discount: 0,
    };
}

const addMobileLine = () => mobileLines.value.push(createNewLine());
const removeMobileLine = (index) => mobileLines.value.splice(index, 1);
const addFixedLine = () => fixedLines.value.push(createFixedLine());
const removeFixedLine = (index) => fixedLines.value.splice(index, 1);

const goBack = () => {
    emit('update:offerType', null);
};
</script>

<template>
    <div class="py-12 bg-gray-100 min-h-screen">
        <div class="max-w-[98%] mx-auto space-y-6">
            
            <!-- Encabezado -->
            <div class="flex justify-between items-center mb-4 bg-white p-4 rounded shadow-sm">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                        <span class="w-3 h-8 bg-orange-500 rounded-sm inline-block"></span>
                        Oferta PYME / Empresas
                    </h2>
                    <p class="text-sm text-gray-500 ml-5">Calculadora de Rentabilidad y Oferta</p>
                </div>
                <SecondaryButton @click="goBack">
                    &larr; Volver al selector
                </SecondaryButton>
            </div>

            <!-- 1. SELECTOR DE TARIFA -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-center mb-2">
                    <div class="flex space-x-1 bg-gray-100 p-1 rounded-lg">
                        <button 
                            @click="tariffType = 'OPTIMA'"
                            class="px-6 py-2 rounded-md text-sm font-bold transition-all duration-200"
                            :class="tariffType === 'OPTIMA' ? 'bg-white text-orange-600 shadow' : 'text-gray-500 hover:text-gray-700'"
                        >
                            TARIFA ÓPTIMA
                        </button>
                        <button 
                            @click="tariffType = 'PERSONALIZADA'"
                            class="px-6 py-2 rounded-md text-sm font-bold transition-all duration-200"
                            :class="tariffType === 'PERSONALIZADA' ? 'bg-white text-blue-600 shadow' : 'text-gray-500 hover:text-gray-700'"
                        >
                            SOLUCIÓN PERSONALIZADA
                        </button>
                    </div>
                </div>
                <p class="text-center text-xs text-gray-500 italic">
                    * La selección determina la comisión base aplicada a cada línea.
                </p>
            </div>

            <!-- 2. PARTE MÓVIL -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-orange-500">
                <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">Líneas Móviles</h3>
                    
                    <!-- Widget de Comisión Total (Usando el valor del composable) -->
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
                                <th class="p-2 border border-gray-300 w-32">Datos Terminal</th>
                                <th class="p-2 border border-gray-300 w-28">O2O</th>
                                
                                <th class="p-2 border border-gray-300 w-20 bg-green-100 text-green-900">PVP Unit.</th>
                                <th class="p-2 border border-gray-300 w-20 bg-blue-100 text-blue-900">Comisión U.</th>
                                <th class="p-2 border border-gray-300 w-24 bg-blue-200 text-blue-900 font-extrabold">TOTAL COM.</th>
                                <th class="p-2 border border-gray-300 w-8"></th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700">
                            <tr v-for="(line, index) in mobileLines" :key="line.id" class="hover:bg-gray-50 text-center group">
                                <!-- Cantidad -->
                                <td class="p-1 border border-gray-200">
                                    <input type="number" v-model="line.quantity" min="1" class="w-full text-center border-0 bg-transparent focus:ring-1 focus:ring-orange-500 font-bold text-lg p-0" />
                                </td>

                                <!-- Tipo de Alta -->
                                <td class="p-1 border border-gray-200">
                                    <select v-model="line.type" class="w-full text-xs border-gray-200 rounded focus:border-orange-500 bg-yellow-50 p-1">
                                        <option value="portabilidad">Portabilidad</option>
                                        <option value="alta_nueva">Alta Nueva</option>
                                        <option value="migracion">Migración</option>
                                    </select>
                                </td>

                                <!-- Tarifa -->
                                <td class="p-1 border border-gray-200">
                                    <select v-model="line.package_id" class="w-full text-xs border-gray-200 rounded font-bold text-gray-800 focus:border-orange-500 p-1">
                                        <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                            {{ pkg.name }}
                                        </option>
                                    </select>
                                </td>

                                <!-- CP -->
                                <td class="p-1 border border-gray-200">
                                    <select v-model="line.cp_duration" class="w-full text-xs border-gray-200 rounded text-center focus:border-orange-500 p-1 font-medium">
                                        <option :value="0">Sin CP</option>
                                        <option :value="24">24 Meses</option>
                                        <option :value="36">36 Meses</option>
                                    </select>
                                </td>

                                <!-- ¿Terminal? -->
                                <td class="p-1 border border-gray-200">
                                    <select v-model="line.has_terminal" class="w-full text-xs border-gray-200 rounded focus:border-orange-500 p-1">
                                        <option value="no">NO</option>
                                        <option value="si">SI</option>
                                    </select>
                                </td>

                                <!-- Tipo Venta -->
                                <td class="p-1 border border-gray-200 bg-gray-50">
                                    <select v-if="line.has_terminal === 'si'" v-model="line.terminal_type" class="w-full text-[10px] border-transparent bg-transparent focus:ring-0 text-center font-medium p-0">
                                        <option value="VAP">VAP</option>
                                        <option value="SUBVENCIONADO">Subvención</option>
                                    </select>
                                    <span v-else class="text-gray-300">-</span>
                                </td>

                                <!-- Datos Terminal -->
                                <td class="p-1 border border-gray-200 text-left">
                                    <div v-if="line.has_terminal === 'si'" class="space-y-1">
                                        <div class="flex gap-1">
                                            <TextInput v-model="line.brand" placeholder="Marca" class="w-1/2 text-[9px] py-0 px-1 border-gray-200 h-6 rounded-sm" />
                                            <TextInput v-model="line.model" placeholder="Modelo" class="w-1/2 text-[9px] py-0 px-1 border-gray-200 h-6 rounded-sm" />
                                        </div>
                                        <div v-if="line.terminal_type === 'VAP'" class="flex gap-1">
                                            <input v-model="line.vap_initial_payment" type="number" placeholder="Ini" class="w-1/2 text-[10px] border-gray-200 rounded px-1 h-6 bg-blue-50" />
                                            <input v-model="line.vap_monthly_payment" type="number" placeholder="Mes" class="w-1/2 text-[10px] border-gray-200 rounded px-1 h-6 bg-blue-50" />
                                        </div>
                                        <div v-else class="flex gap-1">
                                            <input v-model="line.sub_cession_price" type="number" placeholder="Ces." class="w-1/2 text-[10px] border-gray-200 rounded px-1 h-6 bg-green-50" />
                                            <input v-model="line.sub_subsidy_price" type="number" placeholder="Sub." class="w-1/2 text-[10px] border-gray-200 rounded px-1 h-6 bg-green-50" />
                                        </div>
                                    </div>
                                    <div v-else class="text-center text-gray-400 italic text-[10px] p-1">
                                        Sin terminal
                                    </div>
                                </td>

                                <!-- O2O -->
                                <td class="p-1 border border-gray-200">
                                    <select v-model="line.o2o_discount_id" class="w-full text-[10px] font-bold text-center border-2 border-yellow-400 rounded bg-white focus:border-orange-500 p-1">
                                        <option v-for="disc in discounts" :key="disc.id" :value="disc.id">
                                            {{ disc.name }}
                                        </option>
                                    </select>
                                </td>

                                <!-- PVP Unitario (Usando helper del composable) -->
                                <td class="p-1 border border-gray-200 bg-green-50 font-mono text-right pr-2 text-green-800 font-medium">
                                    {{ getPackagePrice(line.package_id).toFixed(2) }}€
                                </td>

                                <!-- Comisión Unitaria (Usando helper del composable) -->
                                <td class="p-1 border border-gray-200 bg-blue-50 font-mono text-right pr-2 text-blue-700 font-bold">
                                    {{ calculateLineCommission(line).toFixed(2) }}€
                                </td>

                                <!-- Total Comisión -->
                                <td class="p-1 border border-gray-200 bg-blue-100 font-mono text-right pr-2 text-blue-900 font-extrabold text-sm border-l-2 border-blue-300">
                                    {{ (calculateLineCommission(line) * line.quantity).toFixed(2) }}€
                                </td>

                                <!-- Eliminar -->
                                <td class="p-1 border border-gray-200 text-center">
                                    <button @click="removeMobileLine(index)" class="text-red-400 hover:text-red-600 p-1 rounded hover:bg-red-50 transition">
                                        &times;
                                    </button>
                                </td>
                            </tr>
                            
                            <tr class="bg-gray-50 border-t-2 border-orange-200">
                                <td colspan="12" class="p-2 text-center">
                                    <button @click="addMobileLine" class="inline-flex items-center gap-2 text-orange-600 font-bold hover:text-orange-700 transition-colors text-xs uppercase tracking-wide">
                                        <span class="text-lg font-bold">+</span> Añadir Grupo de Líneas
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. PARTE FIJA -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 border-purple-500 mt-8">
                <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wide">Oferta Fija</h3>
                    <span class="text-xs text-gray-500 bg-white px-2 py-1 rounded border">Multisede / Fibra</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[800px] text-xs border-collapse">
                        <thead>
                            <tr class="bg-purple-100 text-gray-800 font-bold text-center uppercase border-b-2 border-purple-300">
                                <th class="p-2 border border-gray-300 w-16">Cant.</th>
                                <th class="p-2 border border-gray-300">Producto / Paquete Fijo</th>
                                <th class="p-2 border border-gray-300 w-32">Descuento</th>
                                <th class="p-2 border border-gray-300 w-24">Importe</th>
                                <th class="p-2 border border-gray-300 w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-700">
                            <tr v-for="(line, index) in fixedLines" :key="line.id" class="hover:bg-gray-50 text-center">
                                <td class="p-1 border border-gray-200">
                                    <input type="number" v-model="line.quantity" min="1" class="w-full text-center border-0 bg-transparent focus:ring-1 focus:ring-purple-500 font-bold" />
                                </td>
                                <td class="p-1 border border-gray-200 text-left">
                                    <TextInput v-model="line.name" placeholder="Ej. Fibra 1Gb + Centralita" class="w-full text-xs border-transparent bg-transparent focus:bg-white" />
                                </td>
                                <td class="p-1 border border-gray-200">
                                    <select class="w-full text-xs border-gray-200 rounded text-center">
                                        <option value="0">Sin Dto</option>
                                        <option value="10">10%</option>
                                    </select>
                                </td>
                                <td class="p-1 border border-gray-200 font-mono text-right pr-2">
                                    0,00 €
                                </td>
                                <td class="p-1 border border-gray-200">
                                    <button @click="removeFixedLine(index)" class="text-red-400 hover:text-red-600">
                                        &times;
                                    </button>
                                </td>
                            </tr>
                            <tr class="bg-gray-50 border-t-2 border-purple-200">
                                <td colspan="5" class="p-2 text-center">
                                    <button @click="addFixedLine" class="inline-flex items-center gap-2 text-purple-600 font-bold hover:text-purple-700 transition-colors text-xs uppercase tracking-wide">
                                        <span class="text-lg font-bold">+</span> Añadir Línea Fija
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Botón Guardar -->
            <div class="flex justify-center mt-8 pb-12">
                <PrimaryButton class="px-10 py-4 text-lg bg-gray-800 hover:bg-gray-700 shadow-lg transform hover:scale-105 transition-all">
                    💾 Guardar Oferta PYME
                </PrimaryButton>
            </div>

        </div>
    </div>
</template>