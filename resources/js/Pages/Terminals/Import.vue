<script setup>
// 👇 CAMBIO IMPORTANTE: Importamos el nuevo Layout y quitamos el antiguo 👇
import PublicLayout from '@/Layouts/PublicLayout.vue'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

defineProps({
    success: String,
    error: String,
});

const form = useForm({
    terminals_file: null,
});

const submit = () => {
    form.post(route('terminals.import.store'), {
        onFinish: () => form.reset('terminals_file'),
    });
};
</script>

<template>
    <Head title="Importar Terminales" />

    <PublicLayout> 
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Importar Terminales</h2>
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
                                        @input="form.terminals_file = $event.target.files[0]"
                                        class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100"
                                    />
                                    <progress v-if="form.progress" :value="form.progress.percentage" max="100" class="w-full mt-2">
                                        {{ form.progress.percentage }}%
                                    </progress>
                                    <InputError class="mt-2" :message="form.errors.terminals_file" />
                                </div>

                                <div class="flex items-center justify-end mt-4">
                                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                        Importar Archivo
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
                                 <Link :href="route('terminals.import.create')">
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
    </PublicLayout>
</template>