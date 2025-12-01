<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const success = computed(() => page.props.flash.success);
const error = computed(() => page.props.flash.error);

const form = useForm({
    file: null, // Usamos 'file' porque el controlador PymeImportController espera 'file'
});

const submit = () => {
    // CAMBIO CLAVE: Ruta apuntando al controlador de PYME
    form.post(route('pyme.terminals.import.store'), {
        onFinish: () => form.reset('file'),
    });
};
</script>

<template>
    <Head title="Importar Terminales PYME" />

    <AuthenticatedLayout> 
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Importar Terminales PYME</h2>
                <Link :href="route('dashboard')">
                    <SecondaryButton>Atrás</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        
                        <div v-if="!success">
                            <div v-if="error" class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                                {{ error }}
                            </div>

                            <p class="mb-4">
                                Selecciona el archivo Excel (.xlsx, .xls) para importar los terminales. <br>
                                Recuerda que cada hoja del documento debe tener el nombre exacto de una de las tarifas existentes.
                            </p>

                            <form @submit.prevent="submit">
                                <div>
                                    <input 
                                        type="file" 
                                        @input="form.file = $event.target.files[0]"
                                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100"
                                        accept=".xlsx,.xls,.csv"
                                    />
                                    <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="w-full mt-2">
                                        {{ form.progress.percentage }}%
                                    </progress>
                                    <InputError class="mt-2" :message="form.errors.file" />
                                </div>

                                <div class="flex items-center justify-end mt-4">
                                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                        Importar Archivo PYME
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                        
                        <div v-if="success" class="text-center">
                            <div class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
                                {{ success }}
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">¿Qué quieres hacer ahora?</h3>
                            
                            <div class="mt-6 flex justify-center space-x-4">
                                 <Link :href="route('pyme.terminals.import.create')">
                                    <SecondaryButton>Importar Otro</SecondaryButton>
                                </Link>

                                <Link :href="route('offers.create')">
                                    <PrimaryButton>Ir a Crear Oferta</PrimaryButton>
                                </Link>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>