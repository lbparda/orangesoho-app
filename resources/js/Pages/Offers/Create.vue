<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
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
});

const selectedPackageId = ref(null);
const lines = ref([]);
const selectedInternetAddonId = ref(null);
const additionalInternetLines = ref([]);
const selectedCentralitaId = ref(null); // Guarda el ID de la centralita opcional seleccionada
const centralitaExtensionQuantities = ref({});
const isOperadoraAutomaticaSelected = ref(false); 

const form = useForm({
    package_id: null,
    lines: [],
    internet_addon_id: null,
    additional_internet_lines: [],
    centralita: null,
    summary: null,
});


// --- Computeds ---
const selectedPackage = computed(() => {
    return props.packages.find(p => p.id === selectedPackageId.value) || null;
});

const mobileAddonInfo = computed(() => {
    if (!selectedPackage.value?.addons) return null;
    return selectedPackage.value.addons.find(a => a.type === 'mobile_line');
});

const internetAddonOptions = computed(() => {
    if (!selectedPackage.value?.addons) return [];
    return selectedPackage.value.addons.filter(a => a.type === 'internet');
});

const selectedInternetAddonInfo = computed(() => {
    if (!selectedInternetAddonId.value || !internetAddonOptions.value.length) return null;
    return internetAddonOptions.value.find(a => a.id === selectedInternetAddonId.value);
});

const centralitaAddonOptions = computed(() => {
    if (!selectedPackage.value?.addons) return [];
    return selectedPackage.value.addons.filter(a => a.type === 'centralita' && !a.pivot.is_included);
});

const includedCentralita = computed(() => {
    if (!selectedPackage.value?.addons) return null;
    return selectedPackage.value.addons.find(a => a.type === 'centralita' && a.pivot.is_included);
});

const isCentralitaActive = computed(() => {
    return !!includedCentralita.value || !!selectedCentralitaId.value;
});

const includedCentralitaExtensions = computed(() => {
    if (!isCentralitaActive.value || !selectedPackage.value?.addons) return [];
    return selectedPackage.value.addons.filter(addon =>
        addon.type === 'centralita_extension' && addon.pivot.is_included
    );
});

const operadoraAutomaticaInfo = computed(() => {
    if (!selectedPackage.value?.addons) return null;
    return selectedPackage.value.addons.find(a => a.type === 'centralita_feature');
});

const canAddLine = computed(() => !!selectedPackage.value);

const availableTerminals = computed(() => {
    if (!selectedPackage.value) return [];
    return selectedPackage.value.terminals || [];
});

const availableO2oDiscounts = computed(() => {
    if (!selectedPackage.value) return [];
    return selectedPackage.value.o2o_discounts || [];
});

const brandsForSelectedPackage = computed(() => {
    return [...new Set(availableTerminals.value.map(t => t.brand))];
});

const modelsByBrand = (brand) => {
    if (!brand) return [];
    const terminalsOfBrand = availableTerminals.value.filter(t => t.brand === brand);
    return terminalsOfBrand.filter((terminal, index, self) =>
        index === self.findIndex(t => t.model === terminal.model)
    );
};

const findTerminalPivot = (line) => {
    if (!line.selected_model_id || !line.selected_duration) return null;
    const terminal = availableTerminals.value.find(t =>
        t.id === line.selected_model_id &&
        t.pivot.duration_months === line.selected_duration
    );
    return terminal ? terminal.pivot : null;
};

const assignTerminalPrices = (line) => {
    const pivot = findTerminalPivot(line);
    if (pivot) {
        line.initial_cost = parseFloat(pivot.initial_cost) || 0;
        line.monthly_cost = parseFloat(pivot.monthly_cost) || 0;
        line.terminal_pivot = pivot;
    } else {
        line.initial_cost = 0;
        line.monthly_cost = 0;
        line.terminal_pivot = null;
    }
};

const addLine = () => {
    if (!canAddLine.value) return;
    lines.value.push({
        id: Date.now(),
        is_extra: true,
        is_portability: false,
        phone_number: '', source_operator: null, has_vap: false,
        o2o_discount_id: null, selected_brand: null, selected_model_id: null,
        selected_duration: null, terminal_pivot: null,
        initial_cost: 0, monthly_cost: 0,
    });
};

const removeLine = (index) => {
    if (lines.value[index] && lines.value[index].is_extra) {
        lines.value.splice(index, 1);
    }
};

const addInternetLine = () => {
    additionalInternetLines.value.push({
        id: Date.now(),
        addon_id: null,
    });
};

const removeInternetLine = (index) => {
    additionalInternetLines.value.splice(index, 1);
};

const getDurationsForModel = (line) => {
    if (!line.selected_model_id) return [];
    const terminals = availableTerminals.value.filter(t => t.id === line.selected_model_id);
    return [...new Set(terminals.map(t => t.pivot.duration_months))].sort((a, b) => a - b);
};

const getO2oDiscountsForLine = (line, index) => {
    if (!mobileAddonInfo.value) return availableO2oDiscounts.value;
    const promoLimit = mobileAddonInfo.value.pivot.line_limit;
    const extraLinesBeforeThis = lines.value.slice(0, index).filter(l => l.is_extra).length;
    const isPromotionalExtra = line.is_extra && (extraLinesBeforeThis + 1) <= promoLimit;

    if (isPromotionalExtra) {
        return availableO2oDiscounts.value.filter(d => {
            const monthlyValue = parseFloat(d.total_discount_amount) / parseFloat(d.duration_months);
            return monthlyValue <= 1;
        });
    }
    return availableO2oDiscounts.value;
};

const saveOffer = () => {
    if (!selectedPackage.value) {
        alert("Por favor, selecciona un paquete antes de guardar.");
        return;
    }
    
    form.package_id = selectedPackageId.value;
    form.lines = lines.value.map(line => ({
        is_extra: line.is_extra,
        is_portability: line.is_portability,
        phone_number: line.phone_number,
        source_operator: line.source_operator,
        has_vap: line.has_vap,
        o2o_discount_id: line.o2o_discount_id,
        terminal_pivot_id: line.terminal_pivot ? line.terminal_pivot.id : null, // Asegúrate de que tu backend espera esto
        initial_cost: line.initial_cost,
        monthly_cost: line.monthly_cost,
    }));
    form.internet_addon_id = selectedInternetAddonId.value;
    form.additional_internet_lines = additionalInternetLines.value.filter(l => l.addon_id).map(l => ({ addon_id: l.addon_id }));
    form.centralita = {
        id: selectedCentralitaId.value || (includedCentralita.value ? includedCentralita.value.id : null),
        operadora_automatica_selected: isOperadoraAutomaticaSelected.value,
        operadora_automatica_id: operadoraAutomaticaInfo.value ? operadoraAutomaticaInfo.value.id : null,
        extensions: Object.entries(centralitaExtensionQuantities.value)
            .filter(([, quantity]) => quantity > 0)
            .map(([addonId, quantity]) => ({ addon_id: addonId, quantity: quantity })),
    };
    form.summary = calculationSummary.value;

    form.post(route('offers.store'), {
        onSuccess: () => {
            alert('¡Oferta guardada con éxito!');
        },
        onError: (errors) => {
            console.error('Error al guardar la oferta:', errors);
            alert('Hubo un error al guardar la oferta. Revisa la consola para más detalles.');
        }
    });
};


watch(selectedPackageId, (newPackageId) => {
    lines.value = [];
    selectedInternetAddonId.value = null;
    additionalInternetLines.value = [];
    selectedCentralitaId.value = null;
    centralitaExtensionQuantities.value = {};
    isOperadoraAutomaticaSelected.value = false;

    if (!newPackageId) return;

    if (internetAddonOptions.value.length > 0) {
        const defaultOption = internetAddonOptions.value.sort((a, b) => a.pivot.price - b.pivot.price)[0];
        selectedInternetAddonId.value = defaultOption.id;
    }

    const pkg = props.packages.find(p => p.id === newPackageId);
    if (!pkg?.addons) return;
    const mobileAddon = pkg.addons.find(a => a.type === 'mobile_line');
    const quantity = mobileAddon ? mobileAddon.pivot.included_quantity : 0;
    for (let i = 1; i <= quantity; i++) {
        lines.value.push({
            id: i, is_extra: false, is_portability: false,
            phone_number: '', source_operator: null, has_vap: false,
            o2o_discount_id: null, selected_brand: null, selected_model_id: null,
            selected_duration: null, terminal_pivot: null,
            initial_cost: 0, monthly_cost: 0,
        });
    }
});

const appliedDiscount = computed(() => {
    if (lines.value.length === 0 || !lines.value[0].is_portability || !selectedPackage.value) {
        return null;
    }
    const principalLine = lines.value[0];
    const packageName = selectedPackage.value.name;
    return props.discounts.find(d => {
        const conditions = d.conditions;
        if (conditions.package_names && !conditions.package_names.includes(packageName)) return false;
        if (conditions.requires_vap !== principalLine.has_vap) return false;
        if (conditions.excluded_operators?.includes(principalLine.source_operator)) return false;
        if (conditions.source_operators && !conditions.source_operators.includes(principalLine.source_operator)) return false;
        return true;
    });
});

watch(() => lines.value.map(line => line.has_vap), (newVapStates, oldVapStates) => {
    newVapStates.forEach((isVap, index) => {
        if (oldVapStates && oldVapStates[index] && !isVap) {
            const line = lines.value[index];
            line.selected_brand = null;
            line.selected_model_id = null;
            line.selected_duration = null;
            assignTerminalPrices(line);
        }
    });
}, { deep: true });

const calculationSummary = computed(() => {
    if (!selectedPackage.value) {
        return { basePrice: 0, finalPrice: 0, appliedO2oList: [], totalTerminalFee: 0, totalInitialPayment: 0, extraLinesCost: 0, totalCommission: 0 };
    }

    let price = parseFloat(selectedPackage.value.base_price) || 0;
    const basePrice = price;
    let totalCommission = 0;

    // Fibra
    if (selectedInternetAddonInfo.value) {
        price += parseFloat(selectedInternetAddonInfo.value.pivot.price) || 0;
        totalCommission += parseFloat(selectedInternetAddonInfo.value.pivot.included_line_commission) || 0;
    }
    additionalInternetLines.value.forEach(line => {
        if (line.addon_id) {
            const addonInfo = props.additionalInternetAddons.find(a => a.id === line.addon_id);
            if (addonInfo) {
                price += parseFloat(addonInfo.price) || 0;
                totalCommission += parseFloat(addonInfo.commission) || 0;
            }
        }
    });

    // Centralita
    if (includedCentralita.value) {
        totalCommission += parseFloat(includedCentralita.value.pivot.included_line_commission) || 0;
    }
    else if (selectedCentralitaId.value) {
        const selectedCentralita = centralitaAddonOptions.value.find(c => c.id === selectedCentralitaId.value);
        if (selectedCentralita) {
            price += parseFloat(selectedCentralita.pivot.price) || 0;
            totalCommission += parseFloat(selectedCentralita.commission) || 0;
        }
    }

    // Operadora Automática
    if (isCentralitaActive.value && operadoraAutomaticaInfo.value) {
        if (operadoraAutomaticaInfo.value.pivot.is_included) {
            totalCommission += parseFloat(operadoraAutomaticaInfo.value.pivot.included_line_commission) || 0;
        } else if (isOperadoraAutomaticaSelected.value) {
            price += parseFloat(operadoraAutomaticaInfo.value.pivot.price) || 0;
            totalCommission += parseFloat(operadoraAutomaticaInfo.value.pivot.included_line_commission) || 0;
        }
    }

    // Extensiones
    if (isCentralitaActive.value) {
        includedCentralitaExtensions.value.forEach(ext => {
            const commissionPerUnit = parseFloat(ext.pivot.included_line_commission) || 0;
            const quantity = ext.pivot.included_quantity || 0;
            totalCommission += quantity * commissionPerUnit;
        });

        for (const addonId in centralitaExtensionQuantities.value) {
            const quantity = centralitaExtensionQuantities.value[addonId];
            if (quantity > 0) {
                const addonInfo = props.centralitaExtensions.find(ext => ext.id == addonId);
                if (addonInfo) {
                    price += quantity * (parseFloat(addonInfo.price) || 0);
                    totalCommission += quantity * (parseFloat(addonInfo.commission) || 0);
                }
            }
        }
    }

    // Líneas Móviles y Terminales
    const appliedO2oList = [];
    let totalTerminalFee = 0;
    let totalInitialPayment = 0;
    let extraLinesCost = 0;
    let extraLinesCounter = 0;

    if (mobileAddonInfo.value) {
        const promoLimit = mobileAddonInfo.value.pivot.line_limit;
        const promoPrice = 8.22;
        const standardPrice = mobileAddonInfo.value.pivot.price;
        const includedCommission = parseFloat(mobileAddonInfo.value.pivot.included_line_commission) || 0;
        const additionalCommission = parseFloat(mobileAddonInfo.value.pivot.additional_line_commission) || 0;

        lines.value.forEach((line, index) => {
            totalTerminalFee += parseFloat(line.monthly_cost || 0);
            totalInitialPayment += parseFloat(line.initial_cost || 0);

            if (line.is_extra) {
                extraLinesCounter++;
                totalCommission += additionalCommission;
                extraLinesCost += (extraLinesCounter <= promoLimit) ? promoPrice : parseFloat(standardPrice);
            } else {
                totalCommission += includedCommission;
            }

            if (line.is_portability) {
                totalCommission += props.portabilityCommission;
            }

            if (line.terminal_pivot && line.selected_duration) {
                const terminalTotalPrice = (parseFloat(line.initial_cost) || 0) + (parseFloat(line.monthly_cost || 0) * parseInt(line.selected_duration, 10));
                
                if (terminalTotalPrice < 40) {
                    totalCommission += 15;
                } else if (terminalTotalPrice >= 40 && terminalTotalPrice < 350) {
                    totalCommission += 45;
                } else if (terminalTotalPrice >= 350) {
                    totalCommission += 75;
                }
            }

            if (line.o2o_discount_id) {
                const o2o = availableO2oDiscounts.value.find(d => d.id === line.o2o_discount_id);
                if (o2o) {
                    const monthlyValue = parseFloat(o2o.total_discount_amount) / parseFloat(o2o.duration_months);
                    price -= monthlyValue;
                    appliedO2oList.push({ line: index === 0 ? 'Línea Principal' : `Línea ${index + 1}`, name: o2o.name, value: monthlyValue.toFixed(2) });
                    
                    if (o2o.pivot && o2o.pivot.dho_payment) {
                        totalCommission -= parseFloat(o2o.pivot.dho_payment);
                    }
                }
            }
        });
    }

    price += totalTerminalFee;
    price += extraLinesCost;

    if (appliedDiscount.value) {
        price -= (price * (parseFloat(appliedDiscount.value.percentage) / 100));
    }

    return {
        basePrice: basePrice.toFixed(2),
        finalPrice: Math.max(0, price).toFixed(2),
        appliedO2oList,
        totalTerminalFee: totalTerminalFee.toFixed(2),
        totalInitialPayment: totalInitialPayment.toFixed(2),
        extraLinesCost: extraLinesCost.toFixed(2),
        totalCommission: totalCommission.toFixed(2),
    };
});
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

                    <div class="mb-8 max-w-lg mx-auto">
                        <label for="package" class="block text-sm font-medium text-gray-700 mb-2">1. Selecciona un Paquete Base</label>
                        <select v-model="selectedPackageId" id="package" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option :value="null" disabled>-- Elige un paquete --</option>
                            <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
                        </select>
                    </div>

                    <div v-if="selectedPackage" class="space-y-10">

                        <!-- SECCIÓN FIBRA -->
                        <div v-if="internetAddonOptions.length > 0" class="max-w-lg mx-auto">
                            <label class="block text-sm font-medium text-gray-700 mb-2">2. Elige la velocidad de la Fibra Principal</label>
                            <div class="flex space-x-4 mt-1">
                                <label v-for="addon in internetAddonOptions" :key="addon.id"
                                    :class="['flex-1 text-center px-4 py-3 rounded-md border cursor-pointer transition', { 'bg-indigo-600 text-white border-indigo-600 shadow-lg': selectedInternetAddonId === addon.id, 'bg-white border-gray-300 hover:bg-gray-50': selectedInternetAddonId !== addon.id }]">
                                    <input type="radio" :value="addon.id" v-model="selectedInternetAddonId" class="sr-only">
                                    <span class="block font-semibold">{{ addon.name }}</span>
                                    <span class="block text-xs mt-1" v-if="parseFloat(addon.pivot.price) > 0">+{{ parseFloat(addon.pivot.price).toFixed(2) }}€/mes</span>
                                </label>
                            </div>
                        </div>

                        <!-- SECCIÓN CENTRALITA -->
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
                                    <p class="text-sm font-medium text-gray-700">Añadir Extensiones Adicionales:</p>
                                    <div v-for="extension in centralitaExtensions" :key="extension.id" class="flex items-center justify-between mt-2">
                                        <label :for="`ext_add_${extension.id}`" class="text-gray-800">{{ extension.name }} (+{{ parseFloat(extension.price).toFixed(2) }}€)</label>
                                        <input
                                            :id="`ext_add_${extension.id}`"
                                            type="number" min="0"
                                            v-model.number="centralitaExtensionQuantities[extension.id]"
                                            class="w-20 rounded-md border-gray-300 shadow-sm text-center focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="0"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- SECCIÓN INTERNET ADICIONAL -->
                        <div class="max-w-3xl mx-auto space-y-4 p-6 bg-slate-50 rounded-lg">
                             <h3 class="text-lg font-semibold text-gray-800">Líneas de Internet Adicionales</h3>
                            <div v-for="(line, index) in additionalInternetLines" :key="line.id" class="p-4 border rounded-lg bg-blue-50 border-blue-200 flex items-center justify-between">
                                <div class="flex-1">
                                    <label class="block text-xs font-medium text-gray-500">Velocidad Línea Adicional {{ index + 1 }}</label>
                                    <select v-model="line.addon_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option :value="null" disabled>-- Selecciona una velocidad --</option>
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


                        <!-- SECCIÓN LÍNEAS MÓVILES -->
                        <div class="space-y-6">
                            <h3 class="text-lg font-semibold text-gray-800 text-center">Líneas Móviles</h3>
                            <div v-for="(line, index) in lines" :key="line.id" class="p-6 border rounded-lg max-w-4xl mx-auto" :class="{'bg-gray-50 border-gray-200': !line.is_extra, 'bg-green-50 border-green-200': line.is_extra}">

                                <div class="grid grid-cols-12 gap-4 items-center mb-4">
                                    <div class="col-span-12 md:col-span-3 flex justify-between items-center">
                                        <span class="font-medium text-gray-700">{{ index === 0 ? 'Línea Principal' : `Línea ${index + 1}` }}</span>
                                        <button v-if="line.is_extra" @click="removeLine(index)" class="text-red-500 hover:text-red-700 p-1 rounded-full hover:bg-red-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
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

                        <!-- SECCIÓN RESUMEN -->
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
                                <div v-if="calculationSummary.appliedO2oList.length > 0" class="border-t pt-2 mt-2">
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
                            <div class="border-t pt-4 mt-4">
                                <p class="text-xl font-bold text-emerald-600 text-center">
                                    Comisión Total: {{ calculationSummary.totalCommission }}€
                                </p>
                            </div>
                        </div>

                        <!-- MODIFICACIÓN: Botón de guardar oferta -->
                        <div class="mt-10 flex justify-center">
                            <PrimaryButton @click="saveOffer" :disabled="form.processing">
                                Guardar Oferta
                            </PrimaryButton>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>