<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const form = useForm({
    csv_file: null, // El campo para el archivo
});

const submit = () => {
    form.post(route('admin.discounts.storeCsv'), {
        preserveScroll: true,
        onSuccess: () => {
            // El controlador redirigirá al Index con el mensaje de éxito
            form.reset();
        },
        onError: () => {
            // El controlador nos enviará el error si algo falla
        }
    });
};
</script>

<template>
    <Head title="Importar Descuentos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">
                    Importar Descuentos desde CSV
                </h1>
                <Link :href="route('admin.discounts.index')" class="text-sm text-blue-600 hover:underline">
                    &larr; Volver al listado
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

                <!-- Mensajes Flash de Error/Éxito -->
                <div v-if="$page.props.flash.success" class="p-4 mb-4 bg-green-100 border border-green-300 text-green-800 rounded-md shadow-sm">
                    {{ $page.props.flash.success }}
                </div>
                <div v-if="$page.props.flash.error" class="p-4 mb-4 bg-red-100 border border-red-300 text-red-800 rounded-md shadow-sm">
                    {{ $page.props.flash.error }}
                </div>
                 <div v-if="form.errors.csv_file" class="p-4 mb-4 bg-red-100 border border-red-300 text-red-800 rounded-md shadow-sm">
                    {{ form.errors.csv_file }}
                </div>


                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 md:p-8 space-y-6">
                        
                        <div>
                            <InputLabel for="csv_file" value="Archivo CSV de Descuentos" />
                            <input 
                                id="csv_file"
                                type="file"
                                class="mt-1 block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-s-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                @input="form.csv_file = $event.target.files[0]"
                                accept=".csv,text/csv"
                                required
                            />
                            
                            <p class="mt-3 text-sm text-gray-600">
                                Sube el archivo CSV que has modificado. El sistema usará la columna 'id' para actualizar los descuentos existentes o crear nuevos si el 'id' está vacío.
                            </p>
                            <p class="mt-2 text-xs text-gray-500">
                                Columnas esperadas: <strong>id, name, percentage, duration_months, conditions</strong>
                            </p>
                            
                            <InputError class="mt-2" :message="form.errors.csv_file" />
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">
                                Importar y Actualizar
                            </PrimaryButton>
                            
                            <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="w-32">
                                {{ form.progress.percentage }}%
                            </progress>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="form.recentlySuccessful" class="text-sm text-green-600">¡Subido! Procesando...</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>