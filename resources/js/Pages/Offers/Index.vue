<script setup>
import { Head } from '@inertiajs/vue3';

// Recibimos la variable 'packages' que nos manda el controlador
defineProps({
    packages: Array,
});
</script>

<template>
    <Head title="Consultar Ofertas" />

    <div class="py-12 bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-8">Nuestras Ofertas</h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="pkg in packages" :key="pkg.id" class="bg-white overflow-hidden shadow-sm rounded-lg p-6">
                    <h2 class="text-2xl font-semibold text-gray-900">{{ pkg.name }}</h2>
                    <p class="mt-4 text-4xl font-extrabold text-gray-800">
                        {{ pkg.base_price }}<span class="text-xl font-medium text-gray-500">€/mes</span>
                    </p>
                    <p class="mt-2 text-sm text-gray-500">
                        Precio base sin descuentos
                    </p>
                </div>
            </div>

        </div>
    </div>
</template>