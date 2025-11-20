<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import DangerButton from '@/Components/DangerButton.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { ref, watch } from 'vue';

// --- FUNCIÓN HELPER PARA DEBOUNCE (Reemplazo de Lodash) ---
// Esto evita el error "Failed to resolve import lodash" sin instalar paquetes extra.
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}
// ----------------------------------------------------------

const props = defineProps({
    users: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

watch(search, debounce((value) => {
    router.get(route('admin.users.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
}, 300));

const form = useForm({});
const confirmingUserDeletion = ref(false);
const userToDelete = ref(null);

const confirmUserDeletion = (user) => {
    userToDelete.value = user;
    confirmingUserDeletion.value = true;
};

const deleteUser = () => {
    if (!userToDelete.value) return;
    form.delete(route('admin.users.destroy', userToDelete.value.id), {
        onSuccess: () => closeModal(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    userToDelete.value = null;
};
</script>

<template>
    <Head title="Gestión de Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Administración de Usuarios</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        
                        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
                            <Link :href="route('admin.users.create')">
                                <PrimaryButton>Crear Nuevo Usuario</PrimaryButton>
                            </Link>
                            <div class="w-full sm:w-1/3">
                                <TextInput
                                    v-model="search"
                                    type="text"
                                    placeholder="Buscar usuario..."
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <!-- Contenedor con Scroll (Solución al problema de visualización) -->
                        <div class="overflow-x-auto overflow-y-auto max-h-[70vh]">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50 sticky top-0 z-10">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rol</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipo</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Comisión (%)</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">{{ user.email }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                :class="{
                                                    'bg-green-100 text-green-800': user.role === 'admin',
                                                    'bg-blue-100 text-blue-800': user.role === 'team_lead',
                                                    'bg-gray-100 text-gray-800': user.role === 'user'
                                                }">
                                                {{ user.role === 'team_lead' ? 'Jefe de Equipo' : (user.role === 'admin' ? 'Administrador' : 'Comercial') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">{{ user.team ? user.team.name : '-' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500">{{ user.commission_percentage }}%</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                            <Link :href="route('admin.users.edit', user.id)" class="text-indigo-600 hover:text-indigo-900">Editar</Link>
                                            <button @click="confirmUserDeletion(user)" class="text-red-600 hover:text-red-900">Eliminar</button>
                                        </td>
                                    </tr>
                                    <tr v-if="users.data.length === 0">
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            No se encontraron usuarios.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginación (se mostrará si hay más de 1000 usuarios o si cambias el límite) -->
                        <div v-if="users.links.length > 3" class="mt-4 flex justify-center">
                            <div class="flex gap-1">
                                <Link
                                    v-for="(link, k) in users.links"
                                    :key="k"
                                    :href="link.url || '#'"
                                    class="px-3 py-1 border rounded text-sm"
                                    :class="{'bg-indigo-600 text-white': link.active, 'bg-white text-gray-700 hover:bg-gray-50': !link.active, 'opacity-50 cursor-not-allowed': !link.url}"
                                    v-html="link.label"
                                />
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <ConfirmationModal 
            :show="confirmingUserDeletion" 
            @close="closeModal"
            @confirm="deleteUser"
            title="Eliminar Usuario"
            :message="`¿Estás seguro de que quieres eliminar al usuario '${userToDelete?.name}'? Esta acción no se puede deshacer.`"
        />
    </AuthenticatedLayout>
</template>