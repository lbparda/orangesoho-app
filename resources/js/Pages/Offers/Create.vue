<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import OfferForm from './Partials/OfferForm.vue';

defineProps({
    // Recibe todas las props del controller (clients, packages, etc.)
    clients: Array,
    packages: Array,
    allAddons: Array,
    discounts: Array,
    operators: Array,
    portabilityCommission: Number,
    additionalInternetAddons: Array,
    centralitaExtensions: Array,
    auth: Object,
    initialClientId: [Number, String, null],
    probabilityOptions: Array,
    portabilityExceptions: Array,
    fiberFeatures: Array,
});
</script>

<template>
    <Head title="Crear Oferta" />
    <AuthenticatedLayout>
        <OfferForm v-bind="$props" />
    </AuthenticatedLayout>
</template>