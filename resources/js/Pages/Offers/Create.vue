<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'; // Asumo que existe esta importación
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue'; // <-- AÑADIDO: Necesario para mostrar errores del campo client_id

const props = defineProps({
    clients: Array, // <-- AÑADIDO: Recibimos la lista de clientes
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
    auth: Object, // <-- Prop añadida para recibir los datos del usuario
    initialClientId: [Number, String, null], // <-- AÑADIDO: La nueva prop con el ID a precargar
});

// --- VARIABLES DE ESTADO ---
const selectedPackageId = ref(null);
const lines = ref([]);
const selectedInternetAddonId = ref(null);
const additionalInternetLines = ref([]);
const selectedCentralitaId = ref(null);
const centralitaExtensionQuantities = ref({});
const isOperadoraAutomaticaSelected = ref(false);
const selectedTvAddonIds = ref([]);

// --- FORMULARIO ---
const form = useForm({
    // MODIFICACIÓN CLAVE: Inicializa client_id con el valor de la prop, si existe.
    client_id: props.initialClientId || null,
    package_id: null,
    lines: [],
    internet_addon_id: null,
    additional_internet_lines: [],
    centralita: null,
    tv_addons: [],
    summary: null,
});

// --- COMPUTED PROPERTIES ---

const selectedPackage = computed(() => {
    return props.packages.find(p => p.id === selectedPackageId.value) || null;
});

const tvAddonOptions = computed(() => {
    if (!selectedPackage.value?.addons) return [];
    return selectedPackage.value.addons.filter(a => a.type === 'tv');
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

const autoIncludedExtension = computed(() => {
    if (!selectedCentralitaId.value) return null;
    const selectedCentralita = centralitaAddonOptions.value.find(c => c.id === selectedCentralitaId.value);
    if (!selectedCentralita) return null;
    const centralitaType = selectedCentralita.name.split(' ')[1];
    if (!centralitaType) return null;
    return props.centralitaExtensions.find(ext => ext.name.includes(centralitaType));
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

// --- MÉTODOS ---

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
    if (!form.client_id) { // <-- AÑADIDO: Validación de cliente
        alert("Por favor, selecciona un cliente antes de guardar.");
        return;
    }
    if (!selectedPackage.value) {
        alert("Por favor, selecciona un paquete antes de guardar.");
        return;
    }

    const finalExtensions = { ...centralitaExtensionQuantities.value };
    if (autoIncludedExtension.value) {
        const id = autoIncludedExtension.value.id;
        finalExtensions[id] = (finalExtensions[id] || 0) + 1;
    }

    form.package_id = selectedPackageId.value;
    // form.client_id ya está enlazado con v-model en el template

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
        extensions: Object.entries(finalExtensions)
            .filter(([, quantity]) => quantity > 0)
            .map(([addonId, quantity]) => ({ addon_id: addonId, quantity: quantity })),
    };
    form.tv_addons = selectedTvAddonIds.value;
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

// --- WATCHERS ---

watch(selectedPackageId, (newPackageId) => {
    lines.value = [];
    selectedInternetAddonId.value = null;
    additionalInternetLines.value = [];
    selectedCentralitaId.value = null;
    centralitaExtensionQuantities.value = {};
    isOperadoraAutomaticaSelected.value = false;
    selectedTvAddonIds.value = [];

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

// ✨ --- CÁLCULO DEL RESUMEN Y COMISIONES ACTUALIZADO --- ✨
const calculationSummary = computed(() => {
    if (!selectedPackage.value) {
        return { basePrice: 0, finalPrice: 0, summaryBreakdown: [], totalInitialPayment: 0, totalCommission: 0, teamCommission: 0, userCommission: 0, commissionDetails: {} };
    }

    let price = parseFloat(selectedPackage.value.base_price) || 0;
    const basePrice = price;
    let summaryBreakdown = [{ description: `Paquete Base: ${selectedPackage.value.name}`, price: basePrice }];
    let commissionDetails = { Fibra: [], Televisión: [], Centralita: [], "Líneas Móviles": [], Terminales: [], Ajustes: [] };

    if (selectedInternetAddonInfo.value) {
        const itemPrice = parseFloat(selectedInternetAddonInfo.value.pivot.price) || 0;
        price += itemPrice;
        if (itemPrice > 0) summaryBreakdown.push({ description: `Mejora Fibra (${selectedInternetAddonInfo.value.name})`, price: itemPrice });
        commissionDetails.Fibra.push({ description: `Fibra Principal (${selectedInternetAddonInfo.value.name})`, amount: parseFloat(selectedInternetAddonInfo.value.pivot.included_line_commission) || 0 });
    }

    additionalInternetLines.value.forEach((line, index) => {
        if (line.addon_id) {
            const addonInfo = props.additionalInternetAddons.find(a => a.id === line.addon_id);
            if (addonInfo) {
                const itemPrice = parseFloat(addonInfo.price) || 0;
                price += itemPrice;
                summaryBreakdown.push({ description: `Internet Adicional ${index + 1} (${addonInfo.name})`, price: itemPrice });
                commissionDetails.Fibra.push({ description: `Internet Adicional ${index + 1} (${addonInfo.name})`, amount: parseFloat(addonInfo.commission) || 0 });
            }
        }
    });

    selectedTvAddonIds.value.forEach(tvId => {
        const addon = tvAddonOptions.value.find(a => a.id === tvId);
        if (addon) {
            const itemPrice = parseFloat(addon.pivot.price) || 0;
            price += itemPrice;
            if (itemPrice > 0) summaryBreakdown.push({ description: `TV: ${addon.name}`, price: itemPrice });
            commissionDetails.Televisión.push({ description: addon.name, amount: parseFloat(addon.pivot.included_line_commission) || 0 });
        }
    });

    if (includedCentralita.value) {
        commissionDetails.Centralita.push({ description: `Centralita Incluida (${includedCentralita.value.name})`, amount: parseFloat(includedCentralita.value.pivot.included_line_commission) || 0 });
    } else if (selectedCentralitaId.value) {
        const selectedCentralita = centralitaAddonOptions.value.find(c => c.id === selectedCentralitaId.value);
        if (selectedCentralita) {
            const itemPrice = parseFloat(selectedCentralita.pivot.price) || 0;
            price += itemPrice;
            summaryBreakdown.push({ description: `Centralita: ${selectedCentralita.name}`, price: itemPrice });
            commissionDetails.Centralita.push({ description: `Centralita Contratada (${selectedCentralita.name})`, amount: parseFloat(selectedCentralita.commission) || 0 });
        }
    }

    if (isCentralitaActive.value && operadoraAutomaticaInfo.value) {
        const commission = parseFloat(operadoraAutomaticaInfo.value.pivot.included_line_commission) || 0;
        if (operadoraAutomaticaInfo.value.pivot.is_included) {
            commissionDetails.Centralita.push({ description: 'Operadora Automática (Incluida)', amount: commission });
        } else if (isOperadoraAutomaticaSelected.value) {
            const itemPrice = parseFloat(operadoraAutomaticaInfo.value.pivot.price) || 0;
            price += itemPrice;
            summaryBreakdown.push({ description: 'Operadora Automática', price: itemPrice });
            commissionDetails.Centralita.push({ description: 'Operadora Automática (Contratada)', amount: commission });
        }
    }

    if (isCentralitaActive.value) {
        includedCentralitaExtensions.value.forEach(ext => {
            const commissionPerUnit = parseFloat(ext.pivot.included_line_commission) || 0;
            const quantity = ext.pivot.included_quantity || 0;
            if (quantity > 0) {
                commissionDetails.Centralita.push({ description: `${quantity}x ${ext.name} (Incluidas)`, amount: quantity * commissionPerUnit });
            }
        });

        if (autoIncludedExtension.value) {
            commissionDetails.Centralita.push({ description: `1x ${autoIncludedExtension.value.name} (Por Centralita)`, amount: 0 });
        }

        for (const addonId in centralitaExtensionQuantities.value) {
            const quantity = centralitaExtensionQuantities.value[addonId];
            if (quantity > 0) {
                const addonInfo = props.centralitaExtensions.find(ext => ext.id == addonId);
                if (addonInfo) {
                    const itemPrice = quantity * (parseFloat(addonInfo.price) || 0);
                    price += itemPrice;
                    summaryBreakdown.push({ description: `${quantity}x ${addonInfo.name} (Adicional)`, price: itemPrice });
                    commissionDetails.Centralita.push({ description: `${quantity}x ${addonInfo.name} (Adicional)`, amount: quantity * (parseFloat(addonInfo.commission) || 0) });
                }
            }
        }
    }

    const appliedO2oList = [];
    let totalTerminalFee = 0;
    let totalInitialPayment = 0;
    let extraLinesCost = 0;
    
    if (mobileAddonInfo.value) {
        const promoLimit = mobileAddonInfo.value.pivot.line_limit;
        const promoPrice = 8.22;
        const standardPrice = mobileAddonInfo.value.pivot.price;
        const includedCommission = parseFloat(mobileAddonInfo.value.pivot.included_line_commission) || 0;
        const additionalCommission = parseFloat(mobileAddonInfo.value.pivot.additional_line_commission) || 0;
        let extraLinesCounter = 0;

        lines.value.forEach((line, index) => {
            const lineName = index === 0 ? 'Línea Principal' : `Línea Adicional ${index+1}`;
            totalTerminalFee += parseFloat(line.monthly_cost || 0);
            totalInitialPayment += parseFloat(line.initial_cost || 0);

            if (line.is_extra) {
                extraLinesCounter++;
                const itemPrice = (extraLinesCounter <= promoLimit) ? promoPrice : parseFloat(standardPrice);
                extraLinesCost += itemPrice;
                summaryBreakdown.push({ description: `Línea Móvil Adicional ${extraLinesCounter}`, price: itemPrice });
                commissionDetails["Líneas Móviles"].push({ description: `Comisión ${lineName}`, amount: additionalCommission });
            } else {
                commissionDetails["Líneas Móviles"].push({ description: `Comisión ${lineName}`, amount: includedCommission });
            }

            if (line.is_portability) {
                commissionDetails["Líneas Móviles"].push({ description: `Portabilidad ${lineName}`, amount: props.portabilityCommission });
            }

            if (line.terminal_pivot && line.selected_duration) {
                const terminalTotalPrice = (parseFloat(line.initial_cost) || 0) + (parseFloat(line.monthly_cost || 0) * parseInt(line.selected_duration, 10));
                let terminalCommission = 0;
                if (terminalTotalPrice < 40) terminalCommission = 15;
                else if (terminalTotalPrice >= 40 && terminalTotalPrice < 350) terminalCommission = 45;
                else if (terminalTotalPrice >= 350) terminalCommission = 75;
                commissionDetails.Terminales.push({ description: `Terminal ${lineName}`, amount: terminalCommission });
            }

            if (line.o2o_discount_id) {
                const o2o = availableO2oDiscounts.value.find(d => d.id === line.o2o_discount_id);
                if (o2o) {
                    const monthlyValue = parseFloat(o2o.total_discount_amount) / parseFloat(o2o.duration_months);
                    price -= monthlyValue;
                    appliedO2oList.push({ line: index === 0 ? 'Línea Principal' : `Línea ${index + 1}`, name: o2o.name, value: monthlyValue.toFixed(2) });
                    summaryBreakdown.push({ description: `Subvención O2O (${o2o.name})`, price: -monthlyValue });
                    if (o2o.pivot && o2o.pivot.dho_payment) {
                        commissionDetails.Ajustes.push({ description: `Ajuste DHO ${lineName}`, amount: -parseFloat(o2o.pivot.dho_payment) });
                    }
                }
            }
        });
    }

    price += totalTerminalFee;
    if(totalTerminalFee > 0) {
        summaryBreakdown.push({ description: 'Cuotas mensuales de Terminales', price: totalTerminalFee });
    }
    price += extraLinesCost;

    if (appliedDiscount.value) {
        const discountAmount = price * (parseFloat(appliedDiscount.value.percentage) / 100);
        price -= discountAmount;
        summaryBreakdown.push({ description: `Descuento Tarifa (${appliedDiscount.value.percentage}%)`, price: -discountAmount });
    }

    Object.keys(commissionDetails).forEach(key => {
        if (commissionDetails[key].length === 0) {
            delete commissionDetails[key];
        }
    });

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
        appliedO2oList: appliedO2oList,
        totalTerminalFee: totalTerminalFee.toFixed(2),
        totalInitialPayment: totalInitialPayment.toFixed(2),
        extraLinesCost: extraLinesCost.toFixed(2),
        totalCommission: totalCommission.toFixed(2),
        teamCommission: teamCommission.toFixed(2),
        userCommission: userCommission.toFixed(2),
        commissionDetails,
        summaryBreakdown,
    };
});
</script>

<template>
    <Head title="Crear Oferta" />

    <AuthenticatedLayout>
        <template #header>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-8 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-center mb-6">
                            <h1 class="text-2xl font-bold">Crear Nueva Oferta</h1>
                            <Link :href="route('offers.index')">
                                <SecondaryButton>Atrás</SecondaryButton>
                            </Link>
                        </div>

                        <div class="mb-8 max-w-lg mx-auto">
                            <label for="client" class="block text-sm font-medium text-gray-700 mb-2">1. Selecciona un Cliente</label>
                            <select v-model="form.client_id" id="client" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option :value="null" disabled>-- Elige un cliente --</option>
                                <option v-for="client in clients" :key="client.id" :value="client.id">{{ client.name }} ({{ client.cif_nif }})</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.client_id" />
                            <p class="text-xs text-gray-500 mt-2">
                                ¿No encuentras al cliente? <Link :href="route('clients.create')" class="underline text-indigo-600">Puedes crearlo aquí.</Link>
                            </p>
                        </div>
                        <div class="mb-8 max-w-lg mx-auto">
                            <label for="package" class="block text-sm font-medium text-gray-700 mb-2">2. Selecciona un Paquete Base</label>
                            <select v-model="selectedPackageId" id="package" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option :value="null" disabled>-- Elige un paquete --</option>
                                <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.package_id" />
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
                                <label class="block text-sm font-medium text-gray-700 mb-2">3. Elige la velocidad de la Fibra Principal</label>
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
                                <h3 class="text-lg font-semibold text-gray-800">4. Televisión y Deportes</h3>
                                <div class="space-y-2">
                                    <div v-for="addon in tvAddonOptions" :key="addon.id" class="flex items-center">
                                        <input
                                            :id="`tv_addon_${addon.id}`"
                                            :value="addon.id"
                                            v-model="selectedTvAddonIds"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        >
                                        <label :for="`tv_addon_${addon.id}`" class="ml-3 block text-sm text-gray-900">
                                            {{ addon.name }}
                                            <span v-if="parseFloat(addon.pivot.price) > 0" class="text-gray-600">
                                                (+{{ parseFloat(addon.pivot.price).toFixed(2) }}€)
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div v-if="centralitaAddonOptions.length > 0 || includedCentralita" class="max-w-3xl mx-auto space-y-4 p-6 bg-slate-50 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">5. Centralita Virtual</h3>
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
                            <div class="max-w-3xl mx-auto space-y-4 p-6 bg-slate-50 rounded-lg">
                                <h3 class="text-lg font-semibold text-gray-800">6. Líneas de Internet Adicionales</h3>
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
                            <div class="space-y-6">
                                <h3 class="text-lg font-semibold text-gray-800 text-center">7. Líneas Móviles</h3>
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

                            <div class="mt-10 p-6 bg-gray-100 rounded-lg space-y-3 max-w-2xl mx-auto sticky top-10">
                                <h2 class="text-xl font-semibold text-gray-800 text-center">Resumen de la Oferta</h2>
                                
                                <div class="space-y-2 border-t pt-4 mt-4">
                                    <div v-for="(item, index) in calculationSummary.summaryBreakdown" :key="index" class="flex justify-between text-sm" :class="{'text-gray-700': item.price >= 0, 'text-red-600': item.price < 0}">
                                        <span>{{ item.description }}</span>
                                        <span class="font-medium">
                                            {{ item.price >= 0 ? '+' : '' }}{{ item.price.toFixed(2) }}€
                                        </span>
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
                                    Guardar Oferta
                                </PrimaryButton>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>