<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import OfferForm from './Partials/OfferForm.vue';

defineProps({
    // Props básicas
    clients: Array,
    packages: Array,
    allAddons: Array,
    discounts: Array,
    operators: Array,
    portabilityCommission: Number,
    additionalInternetAddons: Array,
    centralitaExtensions: Array,
    auth: Object,
    probabilityOptions: Array,
    fiberFeatures: Array,
    
    // Props específicas de Edit que pasa el Controller
    offer: Object,
    initialSelectedBenefitIds: Array,
    initialSelectedDigitalAddonIds: Array,
    initialAdditionalInternetLines: Array,
    initialMainIpFijaSelected: Boolean,
    initialMainFibraOroSelected: Boolean,
    initialDdiQuantity: Number
});
</script>

<template>
    <Head title="Editar Oferta" />
    <AuthenticatedLayout>
        <OfferForm v-bind="$props" />
    </AuthenticatedLayout>
</template>