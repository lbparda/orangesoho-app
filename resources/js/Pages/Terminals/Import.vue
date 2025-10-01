<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    terminals_file: null,
});

function submit() {
    form.post(route('terminals.import.run'), {
        onFinish: () => form.reset('terminals_file'),
    });
}
</script>

<template>
    <Head title="Importar Terminales" />

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 bg-white border-b border-gray-200">
                    <h1 class="text-2xl font-bold mb-6">Importar Catálogo de Terminales</h1>

                    <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded">
                        {{ $page.props.flash.success }}
                    </div>

                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <label for="terminals_file" class="block text-sm font-medium text-gray-700">Archivo Excel (.xlsx, .xls)</label>
                            <input 
                                type="file" 
                                @input="form.terminals_file = $event.target.files[0]" 
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                            />
                            <div v-if="form.errors.terminals_file" class="text-red-600 text-sm mt-1">
                                {{ form.errors.terminals_file }}
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <button type="submit" :disabled="form.processing" class="px-6 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50">
                                <span v-if="form.processing">Importando...</span>
                                <span v-else>Importar Archivo</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>