<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    package: Object,
    // 'typeOptions' y 'statusOptions' ya no se reciben en esta versión simple
});

// --- INICIO: SECCIÓN CORREGIDA Y SIMPLIFICADA ---
// Ahora SÓLO cargamos los campos que vamos a editar
const form = useForm({
    name: props.package.name ?? '',
    base_price: props.package.base_price ?? 0, // <-- Usamos base_price
});
// --- FIN: SECCIÓN CORREGIDA ---

const submit = () => {
    form.put(route('admin.packages.update', props.package.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Editar Paquete - ${form.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">
                    Editando Paquete: <span class="text-indigo-600">{{ package.name }}</span>
                </h1>
                <Link :href="route('admin.packages.index')" class="text-sm text-blue-600 hover:underline">
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
                            <InputLabel for="name" value="Nombre del Paquete" />
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

                        <!-- Precio (CORREGIDO) -->
                        <div>
                            <!-- La etiqueta 'for' y el 'id' deben coincidir -->
                            <InputLabel for="base_price" value="Precio Base (€)" />
                            <TextInput
                                id="base_price"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full"
                                v-model.number="form.base_price"
                                required
                            />
                            <!-- El error debe coincidir con el nombre del campo en el form -->
                            <InputError class="mt-2" :message="form.errors.base_price" />
                        </div>

                        <!-- LOS OTROS CAMPOS (Comisión, Tipo, Estado) SE HAN ELIMINADO -->

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

