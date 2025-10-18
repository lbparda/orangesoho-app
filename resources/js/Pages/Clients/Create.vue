<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue'; // Usaremos TextInput
import InputError from '@/Components/InputError.vue';
// Importa las funciones necesarias de Vue
import { ref, watch, onMounted, onUnmounted } from 'vue';

const form = useForm({
    name: '',
    cif_nif: '',
    contact_person: '',
    email: '',
    phone: '',
    address: '',
    // Añadimos city y postal_code para que se puedan rellenar y enviar
    city: '',
    postal_code: '',
});

// --- Lógica de Autocompletado OpenStreetMap ---

const suggestions = ref([]);
const isSelecting = ref(false); // Para evitar que el watch se dispare al seleccionar
const showSuggestions = ref(false); // Controla si se muestra la lista de sugerencias
let searchTimeout = null; // Para el debounce (retraso en la búsqueda)
const addressInputRef = ref(null); // Ref para el componente TextInput de dirección

// Función para buscar direcciones usando Nominatim
const searchAddress = async (query) => {
    // No buscar si la consulta es muy corta o si estamos seleccionando
    if (!query || query.length < 3 || isSelecting.value) {
        suggestions.value = [];
        showSuggestions.value = false;
        return;
    }
    showSuggestions.value = true; // Mostrar el contenedor (podría mostrar "Buscando...")

    try {
        const params = new URLSearchParams({
            q: query,
            countrycodes: 'ES', // Limitar a España
            format: 'json',
            addressdetails: 1, // Pedir detalles como ciudad, CP
            limit: 5,          // Limitar a 5 resultados
            'accept-language': 'es', // Preferir resultados en español
        });

        // ¡¡IMPORTANTE!! Cambia esto por un User-Agent apropiado para tu aplicación
        const userAgent = 'OrangesohoApp/1.0 (ccklbparda@gmail.com)'; // <-- CAMBIA ESTO

        const response = await fetch(`https://nominatim.openstreetmap.org/search?${params}`, {
            headers: { 'User-Agent': userAgent }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        suggestions.value = data;
        // Mantener visible incluso si no hay resultados para mostrar "No encontrado"
        // showSuggestions.value = data.length > 0;

    } catch (error) {
        console.error('Error al buscar dirección en Nominatim:', error);
        suggestions.value = [];
        showSuggestions.value = false; // Ocultar en caso de error
    }
};

// Función llamada al seleccionar una sugerencia de la lista
const selectSuggestion = (suggestion) => {
    isSelecting.value = true; // Activar flag para evitar que el watch busque de nuevo
    form.address = suggestion.display_name; // Actualizar campo de dirección

    // Extraer ciudad y código postal de los detalles
    const addr = suggestion.address;
    if (addr) {
        form.city = addr.city || addr.town || addr.village || ''; // Intentar city, luego town, luego village
        form.postal_code = addr.postcode || '';
    } else {
        // Limpiar si no hay detalles
        form.city = '';
        form.postal_code = '';
    }

    suggestions.value = []; // Limpiar sugerencias
    showSuggestions.value = false; // Ocultar la lista

    // Esperar un instante y desactivar el flag isSelecting
    setTimeout(() => {
        isSelecting.value = false;
    }, 100);
};

// Observador para el campo de dirección con debounce
watch(() => form.address, (newVal) => {
    if (isSelecting.value) {
        // No buscar si estamos en medio de una selección
        return;
    }
    clearTimeout(searchTimeout); // Limpiar el temporizador anterior
    if (newVal && newVal.length >= 3) {
        // Configurar un nuevo temporizador para buscar después de 500ms
        searchTimeout = setTimeout(() => {
            searchAddress(newVal);
        }, 500); // Espera 500ms después de la última pulsación
    } else {
        // Si el texto es muy corto, limpiar sugerencias y ocultar
        suggestions.value = [];
        showSuggestions.value = false;
    }
});

// Función para ocultar sugerencias si se hace clic fuera
const handleClickOutside = (event) => {
    // Si el clic ocurrió fuera del input (o su contenedor en TextInput) Y fuera de la lista de sugerencias
    if (addressInputRef.value && addressInputRef.value.$el && !addressInputRef.value.$el.contains(event.target) && !document.getElementById('address-suggestions')?.contains(event.target)) {
        showSuggestions.value = false;
    }
};

// Añadir/quitar listener de clic externo
onMounted(() => {
    document.addEventListener('click', handleClickOutside, true); // Usar captura para detectar antes
});
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside, true);
    clearTimeout(searchTimeout); // Limpiar timeout al desmontar
});

// --- Fin Lógica Autocompletado ---

const submit = () => {
    showSuggestions.value = false; // Asegura que las sugerencias estén ocultas
    form.post(route('clients.store'),{
         // Mantenemos la redirección que configuramos en el controlador
         preserveScroll: true, // Evita saltos de página
         onSuccess: () => {
             // El controlador redirige, no necesitamos alert aquí si usamos flash
             // alert('¡Cliente creado con éxito!');
         },
         onError: (errors) => {
             console.error('Errores:', errors);
             let errorMsg = 'Hubo un error al guardar el cliente:\n';
             for (const field in errors) {
                 errorMsg += `- ${errors[field]}\n`;
             }
             alert(errorMsg);
         }
     });
};
</script>

<template>
    <Head title="Crear Cliente" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Crear Nuevo Cliente</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 md:p-8 bg-white border-b border-gray-200">
                        <form @submit.prevent="submit" class="space-y-6" novalidate>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="name" value="Nombre / Razón Social *" />
                                    <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus autocomplete="name" />
                                    <InputError class="mt-2" :message="form.errors.name" />
                                </div>
                                <div>
                                    <InputLabel for="cif_nif" value="CIF / NIF *" />
                                    <TextInput id="cif_nif" type="text" class="mt-1 block w-full" v-model="form.cif_nif" required autocomplete="organization-vat" />
                                    <InputError class="mt-2" :message="form.errors.cif_nif" />
                                </div>
                                <div>
                                    <InputLabel for="contact_person" value="Persona de Contacto" />
                                    <TextInput id="contact_person" type="text" class="mt-1 block w-full" v-model="form.contact_person" autocomplete="name" />
                                    <InputError class="mt-2" :message="form.errors.contact_person" />
                                </div>
                                <div>
                                    <InputLabel for="email" value="Email" />
                                    <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" autocomplete="email" />
                                    <InputError class="mt-2" :message="form.errors.email" />
                                </div>
                                <div>
                                    <InputLabel for="phone" value="Teléfono" />
                                    <TextInput id="phone" type="tel" class="mt-1 block w-full" v-model="form.phone" autocomplete="tel" />
                                    <InputError class="mt-2" :message="form.errors.phone" />
                                </div>

                                <div class="relative md:col-span-2">
                                    <InputLabel for="address" value="Dirección (buscar con OpenStreetMap)" />
                                    <TextInput
                                        id="address"
                                        ref="addressInputRef"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.address"
                                        placeholder="Ej: Calle Mayor, 1, Madrid"
                                        autocomplete="off"
                                        @focus="showSuggestions = suggestions.length > 0"
                                    />
                                    <ul
                                        v-show="showSuggestions"
                                        id="address-suggestions"
                                        class="absolute mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto z-50"
                                    >
                                        <li v-if="suggestions.length === 0 && form.address.length >= 3 && !isSelecting" class="px-4 py-2 text-sm text-gray-500 italic">
                                            Buscando o sin resultados...
                                        </li>
                                         <li v-if="suggestions.length === 0 && form.address.length < 3" class="px-4 py-2 text-sm text-gray-500 italic">
                                            Escribe al menos 3 caracteres...
                                        </li>
                                        <li
                                            v-for="(suggestion, index) in suggestions" :key="suggestion.place_id || index"
                                            @click="selectSuggestion(suggestion)"
                                            class="px-4 py-2 hover:bg-indigo-100 cursor-pointer text-sm"
                                        >
                                            {{ suggestion.display_name }}
                                        </li>
                                    </ul>
                                    <InputError class="mt-2" :message="form.errors.address" />
                                </div>

                                <div>
                                     <InputLabel for="city" value="Ciudad" />
                                     <TextInput
                                         id="city" type="text"
                                         class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-gray-100"
                                         v-model="form.city"
                                         readonly
                                     />
                                     <InputError class="mt-2" :message="form.errors.city" />
                                 </div>
                                 <div>
                                     <InputLabel for="postal_code" value="Código Postal" />
                                     <TextInput
                                         id="postal_code" type="text"
                                         class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-gray-100"
                                         v-model="form.postal_code"
                                         readonly
                                     />
                                     <InputError class="mt-2" :message="form.errors.postal_code" />
                                 </div>

                            </div>

                            <div class="flex items-center justify-end pt-4">
                                <Link :href="route('clients.index')" class="text-sm text-gray-600 hover:text-gray-900 underline mr-4">
                                    Cancelar
                                </Link>
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Guardar Cliente
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>