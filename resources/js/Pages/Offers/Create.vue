<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
// NO se importa AuthenticatedLayout
import SecondaryButton from '@/Components/SecondaryButton.vue';

// --- PROPS (Tu código) ---
const props = defineProps({
    packages: Array,
    discounts: Array,
    operators: Array,
});

// --- ESTADO DEL FORMULARIO (Tu código, con una adición) ---
const selectedPackageId = ref(null);
const lines = ref([]);

// --- LÓGICA COMPUTADA (Tu código) ---
const selectedPackage = computed(() => {
    return props.packages.find(p => p.id === selectedPackageId.value) || null;
});

const availableTerminals = computed(() => {
    if (!selectedPackage.value) return [];
    return selectedPackage.value.terminals || [];
});

const availableO2oDiscounts = computed(() => {
    if (!selectedPackage.value) return [];
    return selectedPackage.value.o2o_discounts || [];
});

// --- WATCHER (Tu código, con una adición) ---
watch(selectedPackageId, (newPackageId) => {
    lines.value = [];
    if (!newPackageId) return;
    const pkg = props.packages.find(p => p.id === newPackageId);
    if (!pkg?.addons) return;
    const mobileAddon = pkg.addons.find(a => a.type === 'mobile_line');
    const quantity = mobileAddon ? mobileAddon.pivot.included_quantity : 0;
    for (let i = 1; i <= quantity; i++) {
        lines.value.push({
            id: i,
            phone_number: '',
            source_operator: null,
            has_vap: false,
            o2o_discount_id: null,
            terminal_pivot_id: null,
            selected_brand: null,
            selected_model_id: null, // NUEVO: Para guardar el ID del modelo seleccionado
        });
    }
});

const appliedDiscount = computed(() => {
    if (lines.value.length === 0 || !lines.value[0].source_operator) return null;
    const principalLine = lines.value[0];
    return props.discounts.find(d => {
        const conditions = d.conditions;
        if (conditions.requires_vap !== principalLine.has_vap) return false;
        const isMovistar = principalLine.source_operator === 'Movistar';
        if (conditions.excluded_operators?.includes('Movistar') && isMovistar) return false;
        if (conditions.source_operators?.includes('Movistar') && !isMovistar) return false;
        return true;
    });
});

// --- LÓGICA DE CÁLCULO (Tu código, sin cambios) ---
const calculationSummary = computed(() => {
    if (!selectedPackage.value) {
        return { basePrice: 0, finalPrice: 0, appliedO2oList: [], totalTerminalFee: 0, totalInitialPayment: 0 };
    }
    let price = parseFloat(selectedPackage.value.base_price);
    const basePrice = price;
    if (appliedDiscount.value) {
        price -= (price * (parseFloat(appliedDiscount.value.percentage) / 100));
    }
    const appliedO2oList = [];
    let totalTerminalFee = 0;
    let totalInitialPayment = 0;
    lines.value.forEach((line, index) => {
        if (line.terminal_pivot_id) {
            const terminalInfo = availableTerminals.value.find(t => t.pivot.id === line.terminal_pivot_id);
            if (terminalInfo) {
                totalTerminalFee += parseFloat(terminalInfo.pivot.monthly_cost);
                totalInitialPayment += parseFloat(terminalInfo.pivot.initial_cost);
            }
        }
        if (line.o2o_discount_id) {
            const o2o = availableO2oDiscounts.value.find(d => d.id === line.o2o_discount_id);
            if (o2o) {
                const monthlyValue = parseFloat(o2o.total_discount_amount) / parseFloat(o2o.duration_months);
                price -= monthlyValue;
                appliedO2oList.push({
                    line: index === 0 ? 'Línea Principal' : `Linea ${index + 1} pack`,
                    name: o2o.name, value: monthlyValue.toFixed(2)
                });
            }
        }
    });
    price += totalTerminalFee;
    return {
        basePrice: basePrice.toFixed(2),
        finalPrice: Math.max(0, price).toFixed(2),
        appliedO2oList,
        totalTerminalFee: totalTerminalFee.toFixed(2),
        totalInitialPayment: totalInitialPayment.toFixed(2),
    };
});

// --- FUNCIONES AUXILIARES (Tu código, con las funciones para los 3 desplegables) ---
const brandsForSelectedPackage = computed(() => {
    return [...new Set(availableTerminals.value.map(t => t.brand))];
});

// Devuelve modelos únicos por marca
const modelsByBrand = (brand) => {
    if (!brand) return [];
    const terminalsOfBrand = availableTerminals.value.filter(t => t.brand === brand);
    return terminalsOfBrand.filter((terminal, index, self) =>
        index === self.findIndex((t) => t.id === terminal.id)
    );
};

// Devuelve las duraciones disponibles para un modelo
const durationsForModel = (line) => {
    if (!line.selected_model_id) return [];
    return availableTerminals.value.filter(t => t.id === line.selected_model_id);
};

const getTerminalPrices = (line) => {
    if (!selectedPackage.value || !line.terminal_pivot_id) return null;
    const terminalInfo = availableTerminals.value.find(t => t.pivot.id === line.terminal_pivot_id);
    return terminalInfo ? terminalInfo.pivot : null;
};
</script>

<template>
    <Head title="Crear Oferta" />

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 bg-white border-b border-gray-200">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold">Crear Nueva Oferta</h1>
                        <Link :href="route('terminals.import.create')">
                            <SecondaryButton>Importar Terminales</SecondaryButton>
                        </Link>
                    </div>

                    <div class="mb-8">
                        <label for="package" class="block text-sm font-medium text-gray-700 mb-2">Selecciona un Paquete Base</label>
                        <select v-model="selectedPackageId" id="package" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option :value="null" disabled>-- Elige un paquete --</option>
                            <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
                        </select>
                    </div>

                    <div v-if="selectedPackage" class="space-y-8">
                        <div v-if="lines.length > 0" class="space-y-4">
                            <h3 class="text-lg font-semibold text-gray-800">Líneas Móviles Incluidas</h3>
                            <div v-for="(line, index) in lines" :key="line.id" class="p-4 border rounded-lg" :class="{'bg-yellow-50 border-yellow-300': index === 0}">
                                
                                <div class="grid grid-cols-12 gap-4 items-center">
                                    <div class="col-span-12 md:col-span-2">
                                        <span class="font-medium text-gray-700">{{ index === 0 ? 'Línea Principal' : `Linea ${index + 1} pack` }}</span>
                                    </div>
                                    <div class="col-span-12 md:col-span-3">
                                        <label :for="`phone-${line.id}`" class="block text-xs font-medium text-gray-500">Nº Teléfono</label>
                                        <input v-model="line.phone_number" :id="`phone-${line.id}`" type="tel" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    </div>
                                    <div class="col-span-7 md:col-span-4">
                                        <label :for="`operator-${line.id}`" class="block text-xs font-medium text-gray-500">Operador Origen</label>
                                        <select v-model="line.source_operator" :id="`operator-${line.id}`" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                            <option :value="null" disabled>-- Selecciona --</option>
                                            <option v-for="op in operators" :key="op" :value="op">{{ op }}</option>
                                        </select>
                                    </div>
                                    <div class="col-span-5 md:col-span-3 flex items-end pb-1">
                                        <div class="flex items-center h-full">
                                            <input v-model="line.has_vap" :id="`vap-${line.id}`" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                                            <label :for="`vap-${line.id}`" class="ml-2 block text-sm text-gray-900">con VAP</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4 mt-4">
                                    <div v-if="availableO2oDiscounts.length > 0">
                                        <label :for="`o2o-${line.id}`" class="block text-sm font-medium text-gray-700">Descuento O2O</label>
                                        <select v-model="line.o2o_discount_id" :id="`o2o-${line.id}`" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                            <option :value="null">-- Sin subvención --</option>
                                            <option v-for="o2o in availableO2oDiscounts" :key="o2o.id" :value="o2o.id">{{ o2o.name }}</option>
                                        </select>
                                    </div>
                                    
                                    <div class="grid grid-cols-3 gap-2">
                                        <div>
                                            <label :for="`brand-${line.id}`" class="block text-sm font-medium text-gray-700">Marca</label>
                                            <select v-model="line.selected_brand" @change="line.selected_model_id = null; line.terminal_pivot_id = null" :id="`brand-${line.id}`" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                                <option :value="null">-- Marca --</option>
                                                <option v-for="brand in brandsForSelectedPackage" :key="brand" :value="brand">{{ brand }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label :for="`model-${line.id}`" class="block text-sm font-medium text-gray-700">Modelo</label>
                                            <select v-model="line.selected_model_id" @change="line.terminal_pivot_id = null" :id="`model-${line.id}`" :disabled="!line.selected_brand" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                                <option :value="null">-- Modelo --</option>
                                                <option v-for="terminal in modelsByBrand(line.selected_brand)" :key="terminal.id" :value="terminal.id">
                                                    {{ terminal.model }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label :for="`duration-${line.id}`" class="block text-sm font-medium text-gray-700">Meses</label>
                                            <select v-model="line.terminal_pivot_id" :id="`duration-${line.id}`" :disabled="!line.selected_model_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                                <option :value="null">-- Meses --</option>
                                                <option v-for="terminal in durationsForModel(line)" :key="terminal.pivot.id" :value="terminal.pivot.id">
                                                    {{ terminal.pivot.duration_months }} meses
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-if="getTerminalPrices(line)" class="mt-2 p-2 bg-blue-50 rounded-md text-sm">
                                    <span class="font-semibold">Precios del Terminal:</span>
                                    <span> Pago Inicial: {{ getTerminalPrices(line).initial_cost }}€, </span>
                                    <span> Cuota: {{ getTerminalPrices(line).monthly_cost }}€/mes</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="selectedPackage" class="mt-8 p-6 bg-gray-50 rounded-lg space-y-2">
                           <h2 class="text-xl font-semibold">{{ selectedPackage.name }}</h2>
                                <p class="text-gray-500">Precio Base: {{ calculationSummary.basePrice }}€</p>
                                
                                <div v-if="appliedDiscount" class="text-green-600 font-semibold">
                                    Descuento Tarifa (Línea Principal): -{{ (calculationSummary.basePrice * (appliedDiscount.percentage / 100)).toFixed(2) }}€ ({{ appliedDiscount.percentage }}%)
                                </div>
                                
                                <div v-if="calculationSummary.appliedO2oList.length > 0" class="border-t pt-2 mt-2">
                                    <h3 class="font-semibold text-blue-600">Subvenciones O2O Aplicadas:</h3>
                                    <div v-for="summary in calculationSummary.appliedO2oList" :key="summary.line" class="flex justify-between text-sm text-blue-600">
                                        <span>{{ summary.line }} ({{ summary.name }})</span>
                                        <span>-{{ summary.value }}€</span>
                                    </div>
                                </div>

                                 <div v-if="calculationSummary.totalTerminalFee > 0" class="border-t pt-2 mt-2">
                                    <h3 class="font-semibold text-purple-600">Cuotas de Terminales:</h3>
                                    <div class="flex justify-between text-sm text-purple-600">
                                        <span>Total cuotas mensuales de terminales</span>
                                        <span>+{{ calculationSummary.totalTerminalFee }}€</span>
                                    </div>
                                </div>
                                
                                <p class="mt-4 pt-2 border-t text-2xl font-bold text-gray-800">
                                    Pago Inicial Total: {{ calculationSummary.totalInitialPayment }}€
                                </p>
                                <p class="mt-4 text-4xl font-extrabold text-gray-800">
                                    Precio Final: {{ calculationSummary.finalPrice }}<span class="text-xl font-medium text-gray-500">€/mes</span>
                                </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>