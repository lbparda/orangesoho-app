<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue'; // <-- Importado
import { ref, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    client: Object,
});

// FORMULARIO INICIALIZADO CON TODOS LOS DATOS DEL CLIENTE
const form = useForm({
    type: props.client.type || 'empresa',
    name: props.client.name,
    first_name: props.client.first_name,
    last_name: props.client.last_name,
    cif_nif: props.client.cif_nif,
    contact_person: props.client.contact_person,
    email: props.client.email,
    phone: props.client.phone,
    address: props.client.address,
    street_number: props.client.street_number,
    floor: props.client.floor,
    door: props.client.door,
    city: props.client.city,
    postal_code: props.client.postal_code,
});

// LÓGICA PARA CAMPOS DINÁMICOS
watch(() => form.type, (newType) => {
    form.clearErrors();
    if (newType === 'empresa') {
        form.first_name = '';
        form.last_name = '';
    } else { // 'autonomo'
        form.name = '';
        form.contact_person = '';
    }
});

// --- LÓGICA DE AUTOCOMPLETADO OPENSTREETMAP ---
const suggestions = ref([]);
const isSelecting = ref(false);
const showSuggestions = ref(false);
let searchTimeout = null;
const addressInputRef = ref(null);
const isLoading = ref(false);
const activeSuggestionIndex = ref(-1);

const searchAddress = async (query) => {
    if (!query || query.length < 3 || isSelecting.value) {
        suggestions.value = []; showSuggestions.value = false; return;
    }
    showSuggestions.value = true; isLoading.value = true; activeSuggestionIndex.value = -1;
    try {
      const params = new URLSearchParams({ q: query, countrycodes: 'ES', format: 'json', addressdetails: 1, limit: 5, 'accept-language': 'es' });
      const userAgent = 'OrangesohoApp/1.0 (ccklbparda@gmail.com)';
      const response = await fetch(`https://nominatim.openstreetmap.org/search?${params}`, { headers: { 'User-Agent': userAgent } });
      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      suggestions.value = await response.json();
    } catch (error) {
        console.error('Error al buscar dirección:', error); suggestions.value = [];
    } finally {
        isLoading.value = false;
    }
};
const selectSuggestion = (suggestion) => {
    if (!suggestion) return;
    isSelecting.value = true;
    const addr = suggestion.address;
    if (addr) {
        form.address = addr.road || ''; form.street_number = addr.house_number || '';
        form.city = addr.city || addr.town || addr.village || ''; form.postal_code = addr.postcode || '';
    } else {
        form.address = suggestion.display_name; form.street_number = form.city = form.postal_code = '';
    }
    form.floor = ''; form.door = '';
    showSuggestions.value = false; activeSuggestionIndex.value = -1;
    setTimeout(() => { isSelecting.value = false; }, 100);
};
watch(() => form.address, (newVal) => {
    if (isSelecting.value) return;
    clearTimeout(searchTimeout);
    if (newVal && newVal.length >= 3) {
        searchTimeout = setTimeout(() => searchAddress(newVal), 500);
    } else {
        suggestions.value = []; showSuggestions.value = false;
    }
});
const handleClickOutside = (event) => {
    if (addressInputRef.value && !addressInputRef.value.$el.contains(event.target)) {
        showSuggestions.value = false;
    }
};
onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
const onArrowDown = () => { if (activeSuggestionIndex.value < suggestions.value.length - 1) activeSuggestionIndex.value++; };
const onArrowUp = () => { if (activeSuggestionIndex.value > 0) activeSuggestionIndex.value--; };
const onEnter = () => { selectSuggestion(suggestions.value[activeSuggestionIndex.value]); };
const highlightMatch = (text) => {
    if (!form.address || form.address.length < 3) return text;
    const regex = new RegExp(`(${form.address})`, 'gi');
    return text.replace(regex, '<strong>$1</strong>');
};

// --- LÓGICA DE ACTUALIZACIÓN Y ELIMINACIÓN ---
const submit = () => {
    showSuggestions.value = false;
    form.put(route('clients.update', props.client.id));
};

const confirmDelete = ref(false);
const deleteForm = useForm({}); // Formulario específico para la eliminación

const deleteClient = () => {
    deleteForm.delete(route('clients.destroy', props.client.id), {
        preserveScroll: true,
        onFinish: () => {
            confirmDelete.value = false;
            // Inertia se encargará de redirigir si la eliminación es exitosa.
        }
    });
};
</script>

<template>
    <Head :title="'Editar Cliente: ' + (form.name || `${form.first_name} ${form.last_name}`)" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Cliente</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <form @submit.prevent="submit" class="p-6 md:p-8 space-y-8">
                        
                        <div class="space-y-6">
                            <h3 class="text-lg font-medium leading-6 text-gray-900">Datos Principales</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="type" value="Tipo de Cliente *" />
                                    <select id="type" v-model="form.type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="empresa">Empresa (CIF)</option>
                                        <option value="autonomo">Autónomo (NIF)</option>
                                    </select>
                                </div>
                                <div>
                                    <InputLabel for="cif_nif" :value="form.type === 'empresa' ? 'CIF *' : 'NIF *'" />
                                    <TextInput id="cif_nif" v-model="form.cif_nif" type="text" class="mt-1 block w-full" required />
                                    <InputError class="mt-2" :message="form.errors.cif_nif" />
                                </div>
                                <template v-if="form.type === 'empresa'">
                                    <div class="md:col-span-2">
                                        <InputLabel for="name" value="Razón Social *" />
                                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" />
                                        <InputError class="mt-2" :message="form.errors.name" />
                                    </div>
                                    <div class="md:col-span-2">
                                        <InputLabel for="contact_person" value="Persona de Contacto" />
                                        <TextInput id="contact_person" v-model="form.contact_person" type="text" class="mt-1 block w-full" />
                                        <InputError class="mt-2" :message="form.errors.contact_person" />
                                    </div>
                                </template>
                                <template v-if="form.type === 'autonomo'">
                                    <div>
                                        <InputLabel for="first_name" value="Nombre *" />
                                        <TextInput id="first_name" v-model="form.first_name" type="text" class="mt-1 block w-full" />
                                        <InputError class="mt-2" :message="form.errors.first_name" />
                                    </div>
                                    <div>
                                        <InputLabel for="last_name" value="Apellidos *" />
                                        <TextInput id="last_name" v-model="form.last_name" type="text" class="mt-1 block w-full" />
                                        <InputError class="mt-2" :message="form.errors.last_name" />
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="space-y-6 border-t border-gray-200 pt-8">
                             <h3 class="text-lg font-medium leading-6 text-gray-900">Información de Contacto y Dirección</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="email" value="Email" />
                                    <div class="relative"><span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">@</span><TextInput id="email" v-model="form.email" type="email" class="mt-1 block w-full pl-8" /></div>
                                    <InputError class="mt-2" :message="form.errors.email" />
                                </div>
                                <div>
                                    <InputLabel for="phone" value="Teléfono" />
                                    <div class="relative"><span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">📞</span><TextInput id="phone" v-model="form.phone" type="tel" class="mt-1 block w-full pl-10" /></div>
                                    <InputError class="mt-2" :message="form.errors.phone" />
                                </div>
                                <div class="relative md:col-span-2">
                                    <InputLabel for="address" value="Buscar Dirección" />
                                    <div class="relative">
                                        <TextInput id="address" ref="addressInputRef" v-model="form.address" type="text" class="mt-1 block w-full" placeholder="Ej: Calle Gran Vía, Madrid" autocomplete="off" @focus="showSuggestions = true" @keydown.down.prevent="onArrowDown" @keydown.up.prevent="onArrowUp" @keydown.enter.prevent="onEnter"/>
                                        <div v-if="isLoading" class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none"><svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div>
                                    </div>
                                    <ul v-show="showSuggestions && (suggestions.length > 0 || isLoading)" class="absolute mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto z-50">
                                        <li v-if="suggestions.length === 0 && !isLoading && form.address.length > 2" class="px-4 py-2 text-sm text-gray-500 italic">No se encontraron resultados.</li>
                                        <li v-for="(suggestion, index) in suggestions" :key="suggestion.place_id" @click="selectSuggestion(suggestion)" :class="{ 'bg-indigo-100': index === activeSuggestionIndex }" class="px-4 py-2 hover:bg-indigo-100 cursor-pointer text-sm" v-html="highlightMatch(suggestion.display_name)"></li>
                                    </ul>
                                    <InputError class="mt-2" :message="form.errors.address" />
                                </div>
                                <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div><InputLabel for="street_number" value="Número" /><TextInput id="street_number" v-model="form.street_number" type="text" class="mt-1 block w-full" /><InputError class="mt-2" :message="form.errors.street_number" /></div>
                                    <div><InputLabel for="floor" value="Piso" /><TextInput id="floor" v-model="form.floor" type="text" class="mt-1 block w-full" placeholder="Ej: 3º" /><InputError class="mt-2" :message="form.errors.floor" /></div>
                                    <div><InputLabel for="door" value="Puerta" /><TextInput id="door" v-model="form.door" type="text" class="mt-1 block w-full" placeholder="Ej: Izda." /><InputError class="mt-2" :message="form.errors.door" /></div>
                                </div>
                                <div><InputLabel for="postal_code" value="Código Postal" /><TextInput id="postal_code" v-model="form.postal_code" type="text" class="mt-1 block w-full bg-gray-50" readonly /><InputError class="mt-2" :message="form.errors.postal_code" /></div>
                                <div><InputLabel for="city" value="Ciudad" /><TextInput id="city" v-model="form.city" type="text" class="mt-1 block w-full bg-gray-50" readonly /><InputError class="mt-2" :message="form.errors.city" /></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                            <DangerButton @click="confirmDelete = true" type="button">Eliminar Cliente</DangerButton>
                            <div class="flex items-center">
                                <Link :href="route('clients.index')" class="text-sm text-gray-600 hover:text-gray-900 underline mr-4">Cancelar</Link>
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="flex items-center">
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    {{ form.processing ? 'Guardando...' : 'Actualizar Cliente' }}
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
                <div v-if="confirmDelete" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl max-w-md w-full">
                <h3 class="text-lg font-bold text-gray-900">¿Seguro que quieres eliminar este cliente?</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Se borrarán permanentemente los datos de <strong>{{ form.name || `${form.first_name} ${form.last_name}` }}</strong>. Esta acción no se puede deshacer.
                </p>
                <div class="mt-6 flex justify-end space-x-4">
                    <SecondaryButton @click="confirmDelete = false">Cancelar</SecondaryButton>
                    <DangerButton @click="deleteClient" :class="{ 'opacity-25': deleteForm.processing }" :disabled="deleteForm.processing">Sí, Eliminar</DangerButton>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>