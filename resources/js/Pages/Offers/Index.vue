<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue'; // <-- Tu Modal
import { ref, computed } from 'vue'; // <-- computed añadido

defineProps({
    offers: Object,
});

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
            // Podrías mostrar un mensaje de error al usuario aquí
            closeModal();
        },
        onFinish: () => {
            // Ya no es necesario resetear offerToDelete aquí si se hace en closeModal
            // offerToDelete.value = null;
        },
    });
};
// --- FIN LÓGICA PARA ELIMINAR ---

// --- Funciones de formato ---
const formatDate = (dateString) => { // Formato para Fecha Creación
    if (!dateString) return '-';
    try {
        const date = new Date(dateString);
         if (isNaN(date.getTime())) return 'Inválida'; // Comprobar si la fecha es válida
        return date.toLocaleDateString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit' });
        // Si quisieras hora:
        // return date.toLocaleString('es-ES', { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
    } catch (e) { return 'Error Fecha'; }
};

// <-- FUNCIÓN MODIFICADA -->
const formatSimpleDate = (dateString) => { // Formato DD/MM/YYYY
    if (!dateString) return '-';
    try {
        // Crear objeto Date. Esto maneja 'YYYY-MM-DD', 'YYYY-MM-DD HH:MM:SS', 'YYYY-MM-DDTHH:MM:SSZ' etc.
        const date = new Date(dateString);

        // Comprobar si la fecha creada es válida
        if (isNaN(date.getTime())) {
             console.warn("Invalid date string received:", dateString); // Aviso en consola
             // Fallback muy básico si new Date falla (poco probable si el backend envía formato estándar)
             if (typeof dateString === 'string' && dateString.length >= 10) {
                 const parts = dateString.substring(0, 10).split('-');
                 if (parts.length === 3) {
                     return `${parts[2]}/${parts[1]}/${parts[0]}`; // DD/MM/YYYY
                 }
             }
            return 'Inválida';
        }

        // Obtener día, mes y año (usando UTC para evitar problemas de zona horaria si solo es fecha)
        // Si sabes que siempre vendrá con hora local relevante, puedes quitar UTC
        const day = String(date.getUTCDate()).padStart(2, '0');
        const month = String(date.getUTCMonth() + 1).padStart(2, '0'); // Meses son 0-indexados
        const year = date.getUTCFullYear();

        // Asegurarse de que el año no sea algo como 1970 si la fecha era inválida pero pasó el isNaN
        if (year < 1000) return 'Inválida';

        return `${day}/${month}/${year}`;
    } catch (e) {
        console.error("Error formatting simple date:", dateString, e);
        return 'Error Fecha';
    }
};


const formatCurrency = (summary) => {
    const finalPrice = summary?.finalPrice;
    // Comprobación más robusta para asegurar que es un número
    if (finalPrice === null || finalPrice === undefined || isNaN(parseFloat(finalPrice))) return '-';
     try {
        // Usar parseFloat para asegurar que es un número antes de formatear
        return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(parseFloat(finalPrice));
    } catch (e) { return 'Error €'; }
};


// Mensaje dinámico para el modal
const deletionMessage = computed(() => {
    if (!offerToDelete.value) return '';
    return `¿Estás seguro de que quieres eliminar la oferta #${offerToDelete.value.id} para el cliente ${offerToDelete.value.client?.name || 'N/A'}? Esta acción no se puede deshacer.`;
});

</script>

<template>
    <Head title="Listado de Ofertas" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                 <h1 class="text-2xl font-bold text-gray-800">Ofertas Guardadas</h1>
                 <Link :href="route('offers.create')">
                    <PrimaryButton>Crear Oferta</PrimaryButton>
                 </Link>
             </div>
        </template>

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
            <div v-if="$page.props.flash.success" class="p-4 mb-4 bg-green-100 border border-green-300 text-green-800 rounded-md shadow-sm transition duration-300 ease-in-out">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash.error" class="p-4 mb-4 bg-red-100 border border-red-300 text-red-800 rounded-md shadow-sm transition duration-300 ease-in-out">
                {{ $page.props.flash.error }}
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 border">
                            <thead class="bg-gray-100">
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
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="offer in offers.data" :key="offer.id" class="hover:bg-gray-50 transition duration-150 ease-in-out">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#{{ offer.id }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ offer.client?.name || 'N/A' }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ offer.user?.name || 'N/A' }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ offer.package?.name || 'N/A' }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-right">{{ formatCurrency(offer.summary) }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ offer.probability ?? '-' }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ formatSimpleDate(offer.signing_date) }}</td> <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ formatSimpleDate(offer.processing_date) }}</td> <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ formatDate(offer.created_at) }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-center text-sm font-medium space-x-2">
                                        <Link :href="route('offers.show', offer.id)" class="text-indigo-600 hover:text-indigo-800">Ver</Link>
                                        <Link :href="route('offers.edit', offer.id)" class="text-green-600 hover:text-green-800">Editar</Link>
                                        <a
                                            :href="route('offers.pdf', offer.id)"
                                            class="inline-flex items-center px-3 py-1 bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-600 focus:bg-gray-600 active:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                            target="_blank"
                                            download
                                        >PDF</a>
                                        <DangerButton @click="confirmDeletion(offer)" class="text-xs px-2 py-1">
                                            Eliminar
                                        </DangerButton>
                                    </td>
                                </tr>
                                <tr v-if="offers.data.length === 0">
                                    <td colspan="10" class="px-6 py-10 text-center text-sm text-gray-500 italic">No hay ofertas guardadas todavía.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                     <div v-if="offers.links.length > 3" class="mt-4 px-6 pb-4 flex justify-between items-center">
                         <div class="text-sm text-gray-700">
                            Mostrando {{ offers.from }} a {{ offers.to }} de {{ offers.total }} resultados
                        </div>
                        <div class="flex flex-wrap -mb-1">
                            <template v-for="(link, key) in offers.links" :key="key">
                                <div v-if="link.url === null" class="mr-1 mb-1 px-3 py-2 text-sm leading-4 text-gray-400 border rounded" v-html="link.label" />
                                <Link v-else class="mr-1 mb-1 px-3 py-2 text-sm leading-4 border rounded hover:bg-gray-100 focus:border-indigo-500 focus:text-indigo-500 transition ease-in-out duration-150" :class="{ 'bg-indigo-50 border-indigo-500 text-indigo-600': link.active }" :href="link.url" v-html="link.label" preserve-scroll />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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