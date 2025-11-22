<script setup>
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import Modal from '@/Components/Modal.vue'; // <--- Importado Modal
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue'; // <--- Importado InputLabel
import InputError from '@/Components/InputError.vue'; // <--- Importado InputError
import { ref, computed, watch } from 'vue';

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
    offers: Object,
    filters: Object,
    assignableUsers: Array, // <--- Nueva prop recibida desde el controlador
});

const page = usePage();

const isAdmin = computed(() => page.props.auth.user?.role === 'admin');

// --- Lógica de Búsqueda ---
const search = ref(props.filters?.search || '');

watch(search, debounce((value) => {
    router.get(route('offers.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));
// --------------------------

// --- LÓGICA DE REASIGNACIÓN (NUEVO) ---
const showReassignModal = ref(false);
const offerToReassign = ref(null);
const reassignForm = useForm({
    user_id: '',
});

const openReassignModal = (offer) => {
    offerToReassign.value = offer;
    reassignForm.user_id = offer.user_id; // Preseleccionar el dueño actual
    showReassignModal.value = true;
};

const closeReassignModal = () => {
    showReassignModal.value = false;
    offerToReassign.value = null;
    reassignForm.reset();
};

const submitReassign = () => {
    if (!offerToReassign.value) return;
    
    reassignForm.put(route('offers.reassign', offerToReassign.value.id), {
        preserveScroll: true,
        onSuccess: () => closeReassignModal(),
    });
};

// Solo mostramos el botón si hay usuarios a quien asignar (es decir, si es Admin o Manager con equipo)
const canReassign = computed(() => {
    return props.assignableUsers && props.assignableUsers.length > 0;
});
// --------------------------------------

// --- LÓGICA PARA ELIMINAR ---
const confirmingOfferDeletion = ref(false);
const offerToDelete = ref(null);
const deleteForm = useForm({});

const confirmDeletion = (offer) => {
    offerToDelete.value = offer;
    confirmingOfferDeletion.value = true;
};

const closeModal = () => {
    confirmingOfferDeletion.value = false;
    offerToDelete.value = null;
};

const deleteOffer = () => {
    if (!offerToDelete.value) return;

    deleteForm.delete(route('offers.destroy', offerToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: (errors) => {
            console.error("Error al eliminar la oferta:", errors);
            closeModal();
        },
    });
};
// ----------------------------

// --- Funciones de formato ---
const formatDate = (dateString) => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
         if (isNaN(date.getTime())) return 'Inválida';
        return date.toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' });
    } catch (e) { return 'Error Fecha'; }
};

const formatSimpleDate = (dateString) => {
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
        if (isNaN(date.getTime())) {
             if (typeof dateString === 'string' && dateString.length >= 10) {
                 const parts = dateString.substring(0, 10).split('-');
                 if (parts.length === 3) {
                     return `${parts[2]}/${parts[1]}/${parts[0]}`;
                 }
             }
            return 'Inválida';
        }
        const day = String(date.getUTCDate()).padStart(2, '0');
        const month = String(date.getUTCMonth() + 1).padStart(2, '0');
        const year = date.getUTCFullYear();
        if (year < 1000) return 'Inválida';
        return `${day}/${month}/${year}`;
    } catch (e) {
        return 'Error Fecha';
    }
};

const formatCurrency = (summary) => {
    const finalPrice = summary?.finalPrice;
    if (finalPrice === null || finalPrice === undefined || isNaN(parseFloat(finalPrice))) return '-';
     try {
         return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(parseFloat(finalPrice));
    } catch (e) { return 'Error €'; }
};

const deletionMessage = computed(() => {
    if (!offerToDelete.value) return '';
    return `¿Estás seguro de que quieres eliminar la oferta #${offerToDelete.value.id} para el cliente ${offerToDelete.value.client?.name || 'N/A'}? Esta acción no se puede deshacer.`;
});

const canExport = computed(() => {
    const userRole = page.props.auth?.user?.role?.toLowerCase();
    const allowedRoles = ['admin', 'jefe de ventas', 'team_lead'];
    return allowedRoles.includes(userRole);
});

</script>

<template>
    <Head title="Listado de Ofertas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                 <h1 class="text-2xl font-bold text-gray-800">Ofertas Guardadas</h1>

                 <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                     
                     <!-- Buscador -->
                     <div class="w-full sm:w-64">
                        <TextInput
                            v-model="search"
                            type="text"
                            placeholder="Buscar oferta..."
                            class="w-full text-sm"
                        />
                     </div>

                     <a v-if="canExport" :href="route('offers.exportFunnel')"
                         class="inline-flex justify-center items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-500 active:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition ease-in-out duration-150">
                         <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                             <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                         </svg>
                         Exportar
                     </a>

                     <Link :href="route('offers.create')">
                         <PrimaryButton class="w-full justify-center">Crear Oferta</PrimaryButton>
                     </Link>
                 </div>
             </div>
        </template>

        <div class="max-auto mx-auto sm:px-6 lg:px-8 mt-6">
            <div v-if="$page.props.flash.success" class="p-4 mb-4 bg-green-100 border border-green-300 text-green-800 rounded-md shadow-sm transition duration-300 ease-in-out">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash.error" class="p-4 mb-4 bg-red-100 border border-red-300 text-red-800 rounded-md shadow-sm transition duration-300 ease-in-out">
                {{ $page.props.flash.error }}
            </div>
        </div>

        <div class="py-6">
            <div class="max-auto mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    
                    <!-- TABLA CON SCROLL -->
                    <div class="p-6 bg-white border-b border-gray-200 overflow-x-auto overflow-y-auto max-h-[70vh]">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-100 sticky top-0 z-10">
                                <tr>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendedor</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paquete</th>
                                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Precio Final</th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Prob (%)</th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">F. Firma</th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">F. Tramit.</th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">F. Creación</th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="offer in offers.data" :key="offer.id" class="hover:bg-gray-50 transition duration-150 ease-in-out">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#{{ offer.id }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ offer.client?.name || 'N/A' }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ offer.user?.name || 'N/A' }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ offer.package_name || 'N/A' }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ formatCurrency(offer.summary) }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ offer.probability ?? '-' }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ formatSimpleDate(offer.signing_date) }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ formatSimpleDate(offer.processing_date) }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ formatDate(offer.created_at) }}</td>
                                    
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize"
                                            :class="{
                                                'bg-blue-100 text-blue-800': offer.status === 'borrador' || !offer.status,
                                                'bg-green-100 text-green-800': offer.status === 'finalizada',
                                            }">
                                            {{ offer.status || 'borrador' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2">
                                        <Link :href="route('offers.show', offer.id)" class="text-indigo-600 hover:text-indigo-800">Ver</Link>
                                        
                                        <Link v-if="offer.status === 'borrador' || isAdmin" :href="route('offers.edit', offer.id)" class="text-green-600 hover:text-green-800">Editar</Link>
                                        
                                        <!-- BOTÓN REASIGNAR -->
                                        <button 
                                            v-if="canReassign" 
                                            @click="openReassignModal(offer)"
                                            class="text-amber-600 hover:text-amber-800 cursor-pointer"
                                            title="Reasignar Oferta"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 inline">
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM3.75 13.5v-2.875a3.375 3.375 0 00-3.375-3.375h-.375" />
                                              <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </button>

                                        <a
                                            :href="route('offers.pdf', offer.id)"
                                            class="inline-flex items-center px-2 py-1 bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-600 focus:bg-gray-600 active:bg-gray-800 transition ease-in-out duration-150"
                                            target="_blank"
                                            download
                                        >PDF</a>
                                        
                                        <DangerButton v-if="offer.status === 'borrador' || isAdmin" @click="confirmDeletion(offer)" class="text-xs px-2 py-1">
                                            Eliminar
                                        </DangerButton>
                                    </td>
                                </tr>
                                <tr v-if="offers.data.length === 0">
                                    <td colspan="11" class="px-6 py-10 text-center text-sm text-gray-500 italic">No hay ofertas guardadas todavía.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="offers.links.length > 3" class="mt-4 px-6 pb-4 flex flex-col sm:flex-row justify-between items-center gap-4">
                         <div class="text-sm text-gray-700">
                            Mostrando {{ offers.from }} a {{ offers.to }} de {{ offers.total }} resultados
                        </div>
                        <div class="flex flex-wrap justify-center gap-1">
                            <template v-for="(link, key) in offers.links" :key="key">
                                <div v-if="link.url === null" class="px-3 py-1 text-sm leading-4 text-gray-400 border rounded" v-html="link.label" />
                                <Link v-else class="px-3 py-1 text-sm leading-4 border rounded hover:bg-gray-100 focus:border-indigo-500 focus:text-indigo-500 transition ease-in-out duration-150" :class="{ 'bg-indigo-50 border-indigo-500 text-indigo-600': link.active }" :href="link.url" v-html="link.label" preserve-scroll />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MODAL REASIGNAR -->
        <Modal :show="showReassignModal" @close="closeReassignModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Reasignar Oferta #{{ offerToReassign?.id }}
                </h2>
                
                <p class="mt-1 text-sm text-gray-600 mb-4">
                    Selecciona el nuevo propietario para esta oferta.
                    <span v-if="offerToReassign?.client" class="block font-semibold mt-1">
                        Cliente: {{ offerToReassign.client.name }}
                    </span>
                </p>

                <div class="mt-6">
                    <InputLabel for="new_user" value="Nuevo Vendedor" />
                    
                    <select
                        id="new_user"
                        v-model="reassignForm.user_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="" disabled>Selecciona un usuario</option>
                        <option v-for="user in assignableUsers" :key="user.id" :value="user.id">
                            {{ user.name }} <span v-if="user.team">({{ user.team.name }})</span>
                        </option>
                    </select>
                    
                    <InputError :message="reassignForm.errors.user_id" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeReassignModal">
                        Cancelar
                    </SecondaryButton>

                    <PrimaryButton
                        class="ml-3"
                        :class="{ 'opacity-25': reassignForm.processing }"
                        :disabled="reassignForm.processing"
                        @click="submitReassign"
                    >
                        Guardar Cambios
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <ConfirmationModal
            :show="confirmingOfferDeletion"
            title="Eliminar Oferta"
            :message="deletionMessage"
            @close="closeModal"
            @confirm="deleteOffer"
        >
        </ConfirmationModal>

    </AuthenticatedLayout>
</template>