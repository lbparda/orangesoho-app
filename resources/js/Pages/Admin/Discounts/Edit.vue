<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    discount: Object,
});

const formatJson = (jsonObj) => {
    if (!jsonObj || Object.keys(jsonObj).length === 0) {
        return ''; 
    }
    try {
        return JSON.stringify(jsonObj, null, 2); 
    } catch (e) {
        return 'Error al parsear JSON';
    }
};

const form = useForm({
    name: props.discount.name,
    percentage: props.discount.percentage,
    duration_months: props.discount.duration_months,
    conditions: formatJson(props.discount.conditions), 
    is_active: props.discount.is_active, // <-- AÑADIDO
});

const submit = () => {
    const dataToSubmit = {
        ...form.data(),
    };

    try {
        if (form.conditions && form.conditions.trim() !== '') {
            dataToSubmit.conditions = JSON.parse(form.conditions);
        } else {
            dataToSubmit.conditions = null;
        }
        form.clearErrors('conditions'); 
    } catch (e) {
        form.setError('conditions', 'El formato JSON no es válido. Revisa las comillas y las comas.');
        return; 
    }

    form.transform(() => dataToSubmit).put(route('admin.discounts.update', props.discount.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Editar Descuento - ${form.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">
                    Editando Descuento: <span class="text-indigo-600">{{ discount.name }}</span>
                </h1>
                <Link :href="route('admin.discounts.index')" class="text-sm text-blue-600 hover:underline">
                    &larr; Volver al listado
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 md:p-8 space-y-6">
                        
                        <!-- Nombre -->
                        <div>
                            <InputLabel for="name" value="Nombre del Descuento" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <!-- Porcentaje (%) -->
                        <div>
                            <InputLabel for="percentage" value="Porcentaje (%)" />
                            <TextInput
                                id="percentage"
                                type="number"
                                step="0.01"
                                min="0"
                                max="100"
                                class="mt-1 block w-full"
                                v-model.number="form.percentage"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.percentage" />
                        </div>

                        <!-- Duración (Meses) -->
                        <div>
                            <InputLabel for="duration_months" value="Duración (meses)" />
                            <TextInput
                                id="duration_months"
                                type="number"
                                step="1"
                                min="0"
                                class="mt-1 block w-full"
                                v-model.number="form.duration_months"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.duration_months" />
                        </div>

                        <!-- Condiciones (JSON) -->
                        <div>
                            <InputLabel for="conditions" value="Condiciones (formato JSON)" />
                            <textarea
                                id="conditions"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm font-mono text-sm"
                                v-model="form.conditions"
                                rows="4"
                                placeholder='Ej: {"min_lines": 3, "requires_fiber": true}'
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.conditions" />
                        </div>
                        
                        <!-- AÑADIDO: Campo Estado -->
                        <div>
                            <InputLabel for="is_active" value="Estado" />
                            <select
                                id="is_active"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                v-model="form.is_active"
                                required
                            >
                                <option :value="true">Activo</option>
                                <option :value="false">Inactivo</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.is_active" />
                        </div>

                        <!-- Botón Guardar -->
                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">
                                Guardar Cambios
                            </PrimaryButton>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="form.recentlySuccessful" class="text-sm text-green-600">Guardado.</p>
                            </Transition>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>