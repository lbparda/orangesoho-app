<script setup>
import { ref, computed } from 'vue'; // <-- Asegúrate de importar 'computed'
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3'; // <-- Asegúrate de importar 'usePage'

const showingNavigationDropdown = ref(false);

// --- INICIO: LÓGICA DE ROLES (AÑADIDA) ---
const page = usePage();
const isAdmin = computed(() => page.props.auth.user?.is_admin);
const isManager = computed(() => page.props.auth.user?.is_manager);
// --- FIN: LÓGICA DE ROLES ---
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav class="bg-white border-b border-gray-100">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800"
                                    />
                                </Link>
                            </div>

                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Dashboard
                                </NavLink>
                                <NavLink :href="route('offers.create')" :active="route().current('offers.create')">
                                    Crear Oferta
                                </NavLink>
                                <NavLink :href="route('offers.index')" :active="route().current('offers.index')">
                                    Consultar Ofertas
                                </NavLink>
                                <NavLink :href="route('clients.index')" :active="route().current('clients.index')">
                                    Clientes
                                </NavLink>
                                
                                <!-- INICIO: MENÚ ADMIN ACTUALIZADO -->
                                <div v-if="isAdmin" class="hidden sm:flex sm:items-center sm:ms-6">
                                    <div class="ms-3 relative">
                                        <Dropdown align="right" width="48">
                                            <template #trigger>
                                                <span class="inline-flex rounded-md">
                                                    <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                                        Administración
                                                        <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                                    </button>
                                                </span>
                                            </template>
                                            <template #content>
                                                <DropdownLink :href="route('admin.users.index')"> Gestionar Usuarios </DropdownLink>
                                                <DropdownLink :href="route('admin.teams.index')"> Gestionar Equipos </DropdownLink>
                                                <DropdownLink :href="route('terminals.import.create')"> Importar Terminales </DropdownLink>
                                                <hr class="my-1 border-gray-100">
                                                <DropdownLink :href="route('admin.packages.index')"> Gestionar Paquetes </DropdownLink>
                                                <DropdownLink :href="route('admin.discounts.index')"> Gestionar Descuentos </DropdownLink>
                                            </template>
                                        </Dropdown>
                                    </div>
                                </div>
                                <!-- FIN: MENÚ ADMIN ACTUALIZADO -->
                                
                                <NavLink v-if="isManager" :href="route('team-lead.users.index')" :active="route().current().startsWith('team-lead')">
                                    Gestionar Equipo
                                </NavLink>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <div v-if="$page.props.auth.user" class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {{ $page.props.auth.user.name }}
                                                <svg class="-me-0.5 ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                            </button>
                                        </span>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('profile.edit')"> Perfil </DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button"> Cerrar Sesión </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /><path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div :class="{ block: showingNavigationDropdown, hidden: !showingNavigationDropdown }" class="sm:hidden">
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')"> Dashboard </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('offers.create')" :active="route().current('offers.create')"> Crear Oferta </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('offers.index')" :active="route().current('offers.index')"> Consultar Ofertas </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('clients.index')" :active="route().current('clients.index')">Clientes </ResponsiveNavLink>
                    </div>

                    <!-- INICIO: MENÚ RESPONSIVE ADMIN ACTUALIZADO -->
                    <div v-if="isAdmin" class="border-t border-gray-200 pt-4 pb-1">
                        <div class="px-4"><div class="font-medium text-base text-gray-800">Administración</div></div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('admin.users.index')"> Gestionar Usuarios </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.teams.index')"> Gestionar Equipos </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('terminals.import.create')"> Importar Terminales </ResponsiveNavLink>
                            <hr class="my-1 border-gray-100">
                            <ResponsiveNavLink :href="route('admin.packages.index')"> Gestionar Paquetes </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.discounts.index')"> Gestionar Descuentos </ResponsiveNavLink>
                        </div>
                    </div>
                    <!-- FIN: MENÚ RESPONSIVE ADMIN ACTUALIZADO -->

                    <div v-if="isManager" class="border-t border-gray-200 pt-4 pb-1">
                        <div class="px-4"><div class="font-medium text-base text-gray-800">Jefe de Equipo</div></div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('team-lead.users.index')"> Gestionar Equipo </ResponsiveNavLink>
                        </div>
                    </div>

                    <div v-if="$page.props.auth.user" class="border-t border-gray-200 pb-1 pt-4">
                        <div class="px-4">
                            <div class="text-base font-medium text-gray-800">{{ $page.props.auth.user.name }}</div>
                            <div class="text-sm font-medium text-gray-500">{{ $page.props.auth.user.email }}</div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')"> Perfil </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('logout')" method="post" as="button"> Cerrar Sesión </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <header class="bg-white shadow" v-if="$slots.header">
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>