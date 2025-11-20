<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3'; 
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue'; 
import ConfirmationModal from '@/Components/ConfirmationModal.vue'; 
import TextInput from '@/Components/TextInput.vue'; // <--- Importante para el buscador
import { ref, watch } from 'vue'; 

// --- FUNCIÓN HELPER PARA DEBOUNCE (Sin Lodash) ---
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}
// -------------------------------------------------

const props = defineProps({
    clients: Object, // Laravel's paginated object
    filters: Object, // <--- Recibimos los filtros para el buscador
});

const successMessage = usePage().props.flash.success;

// --- Lógica de Búsqueda ---
const search = ref(props.filters?.search || '');

watch(search, debounce((value) => {
    router.get(route('clients.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));
// --------------------------

// --- Lógica para el borrado ---
const confirmingClientDeletion = ref(false);
const clientToDelete = ref(null);
const deleteForm = useForm({});

const confirmClientDeletion = (client) => {
    clientToDelete.value = client;
    confirmingClientDeletion.value = true;
};

const deleteClient = () => {
    if (!clientToDelete.value) return;
    deleteForm.delete(route('clients.destroy', clientToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => closeModal(), 
    });
};

const closeModal = () => {
    confirmingClientDeletion.value = false;
    clientToDelete.value = null;
    deleteForm.reset(); 
};
// --- Fin Lógica para el borrado ---

</script>

<template>
    <Head title="Clientes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Clientes</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Mensajes de éxito/error -->
                <div v-if="successMessage" class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
                    {{ successMessage }}
                </div>
                 <div v-if="$page.props.flash.error" class="mb-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                     {{ $page.props.flash.error }}
                 </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        
                        <!-- Cabecera con Botón de Crear y Buscador -->
                        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                            <Link :href="route('clients.create')">
                                <PrimaryButton>Crear Cliente</PrimaryButton>
                            </Link>
                            
                            <!-- Campo del Buscador -->
                            <div class="w-full sm:w-1/3">
                                <TextInput
                                    v-model="search"
                                    type="text"
                                    placeholder="Buscar cliente..."
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <!-- TABLA CON SCROLL -->
                        <!-- overflow-y-auto y max-h-[70vh] permiten el scroll vertical -->
                        <div class="overflow-x-auto overflow-y-auto max-h-[70vh]">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0 z-10"> <!-- sticky top-0 fija la cabecera -->
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CIF/NIF</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asignado a</th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Acciones</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="client in clients.data" :key="client.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ client.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ client.cif_nif }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ client.email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ client.phone }}</td>
                                         <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ client.user?.name || 'No asignado' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <Link :href="route('clients.offers', client.id)" class="text-blue-600 hover:text-blue-900 mr-2">Ofertas</Link>
                                            <Link :href="route('clients.edit', client.id)" class="text-indigo-600 hover:text-indigo-900 mr-2">Editar</Link>
                                            <DangerButton @click="confirmClientDeletion(client)" class="text-xs px-2 py-1">Eliminar</DangerButton>
                                        </td>
                                    </tr>
                                    <tr v-if="clients.data.length === 0">
                                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-gray-500">No se encontraron clientes.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginación -->
                         <div v-if="clients.links.length > 3" class="mt-4 px-6 pb-4 flex flex-col sm:flex-row justify-between items-center gap-4">
                             <div class="text-sm text-gray-700">
                                Mostrando {{ clients.from }} a {{ clients.to }} de {{ clients.total }} resultados
                            </div>
                            <div class="flex flex-wrap justify-center gap-1">
                                <template v-for="(link, key) in clients.links" :key="key">
                                    <div v-if="link.url === null" class="px-3 py-1 text-sm leading-4 text-gray-400 border rounded" v-html="link.label" />
                                    <Link v-else class="px-3 py-1 text-sm leading-4 border rounded hover:bg-gray-100 focus:border-indigo-500 focus:text-indigo-500 transition ease-in-out duration-150" :class="{ 'bg-indigo-50 border-indigo-500 text-indigo-600': link.active }" :href="link.url" v-html="link.label" preserve-scroll />
                                </template>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal
            :show="confirmingClientDeletion"
            @close="closeModal"
            @confirm="deleteClient"
            title="Eliminar Cliente"
            :message="`¿Estás seguro de que quieres eliminar el cliente '${clientToDelete?.name}'? Se borrarán sus datos permanentemente. Esta acción no se puede deshacer.`"
        />

    </AuthenticatedLayout>
</template>