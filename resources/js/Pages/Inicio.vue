<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Bienvenida" />

    <div class="flex justify-center items-center min-h-screen bg-gray-100">
        <div class="text-center">
            <h1 class="text-4xl font-bold mb-4">¡Bienvenido a Ofertas Soho Orange!</h1>
            
            <div class="space-x-4">
                <Link 
                    :href="route('Crear Oferta')"
                    class="px-6 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700"
                >
                    Crear Oferta
                </Link>

                <Link 
                    :href="route('Consultar Ofertas')"
                    class="px-6 py-2 text-white bg-gray-800 rounded-md hover:bg-gray-900"
                >
                    Consultar Ofertas
                </Link>
            </div>
        </div>
    </div>
</template>