<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { ref } from 'vue';

const props = defineProps({
    client: Object,
});

const form = useForm({
    name: props.client.name,
    cif_nif: props.client.cif_nif,
    contact_person: props.client.contact_person,
    email: props.client.email,
    phone: props.client.phone,
    address: props.client.address,
});

const submit = () => {
    form.put(route('clients.update', props.client.id));
};

const confirmDelete = ref(false);

const deleteClient = () => {
    useForm({}).delete(route('clients.destroy', props.client.id), {
        onFinish: () => {
            confirmDelete.value = false;
        }
    });
};
</script>

<template>
    <Head :title="'Editar Cliente: ' + form.name" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Cliente</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="name" value="Nombre / Razón Social" />
                                    <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus />
                                    <InputError class="mt-2" :message="form.errors.name" />
                                </div>
                                <div>
                                    <InputLabel for="cif_nif" value="CIF / NIF" />
                                    <TextInput id="cif_nif" type="text" class="mt-1 block w-full" v-model="form.cif_nif" required />
                                    <InputError class="mt-2" :message="form.errors.cif_nif" />
                                </div>
                                <div>
                                    <InputLabel for="contact_person" value="Persona de Contacto" />
                                    <TextInput id="contact_person" type="text" class="mt-1 block w-full" v-model="form.contact_person" />
                                    <InputError class="mt-2" :message="form.errors.contact_person" />
                                </div>
                                <div>
                                    <InputLabel for="email" value="Email" />
                                    <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" />
                                    <InputError class="mt-2" :message="form.errors.email" />
                                </div>
                                <div>
                                    <InputLabel for="phone" value="Teléfono" />
                                    <TextInput id="phone" type="tel" class="mt-1 block w-full" v-model="form.phone" />
                                    <InputError class="mt-2" :message="form.errors.phone" />
                                </div>
                                <div>
                                    <InputLabel for="address" value="Dirección" />
                                    <TextInput id="address" type="text" class="mt-1 block w-full" v-model="form.address" />
                                    <InputError class="mt-2" :message="form.errors.address" />
                                </div>
                            </div>

                            <div class="flex items-center justify-between mt-6">
                                <DangerButton @click="confirmDelete = true">Eliminar Cliente</DangerButton>
                                <div class="flex items-center">
                                    <Link :href="route('clients.index')" class="text-sm text-gray-600 hover:text-gray-900 underline mr-4">
                                        Cancelar
                                    </Link>
                                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                        Actualizar Cliente
                                    </PrimaryButton>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div v-if="confirmDelete" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl">
                <h3 class="text-lg font-bold">¿Seguro que quieres eliminar este cliente?</h3>
                <p class="mt-2 text-sm text-gray-600">Esta acción no se puede deshacer.</p>
                <div class="mt-6 flex justify-end space-x-4">
                    <SecondaryButton @click="confirmDelete = false">Cancelar</SecondaryButton>
                    <DangerButton @click="deleteClient">Sí, Eliminar</DangerButton>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>