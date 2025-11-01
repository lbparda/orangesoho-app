<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\TeamLead\ManagementController;
use App\Http\Controllers\ClientController; // <-- SOLUCIÓN: IMPORTACIÓN AÑADIDA

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- RUTA PRINCIPAL ---
Route::get('/', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest');

// --- RUTAS PARA USUARIOS AUTENTICADOS ---
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Perfil de Usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Gestión de Ofertas
    Route::resource('offers', OfferController::class);

    // Importación de Terminales
    Route::get('/terminals/import', [ImportController::class, 'create'])->name('terminals.import.create');
    Route::post('/terminals/import', [ImportController::class, 'store'])->name('terminals.import.store');

    // Gestión de Clientes
    Route::resource('clients', ClientController::class); // <-- CORREGIDO: Usamos el nombre corto
    Route::get('/clients/{client}/offers', [ClientController::class, 'showOffers'])->name('clients.offers'); // <-- ESTA LÍNEA AHORA FUNCIONARÁ
    // RUTA NUEVA PARA EL PDF
    Route::get('/offers/{offer}/pdf', [OfferController::class, 'generatePDF'])->name('offers.pdf');

    Route::post('offers/{offer}/lock', [OfferController::class, 'lock'])->name('offers.lock');
});

// --- GRUPO DE RUTAS DE ADMINISTRACIÓN ---
Route::prefix('admin')
    ->middleware(['auth', 'is_admin'])
    ->name('admin.')
    ->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('teams', TeamController::class);
});

// --- GRUPO DE RUTAS DE JEFE DE EQUIPO ---
Route::prefix('team-lead')
    ->middleware(['auth'])
    ->name('team-lead.')
    ->group(function () {
        Route::get('users', [ManagementController::class, 'index'])->name('users.index');
        Route::get('users/{user}/edit', [ManagementController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [ManagementController::class, 'update'])->name('users.update');
});

// --- GRUPO DE RUTAS DE JEFE DE EQUIPO ---
Route::middleware(['auth', 'isTeamLead'])->prefix('team-lead')->name('team-lead.')->group(function () {
    Route::get('/users/create', [App\Http\Controllers\TeamLead\UserController::class, 'create'])->name('users.create');
    Route::post('/users', [App\Http\Controllers\TeamLead\UserController::class, 'store'])->name('users.store');
});






// --- GRUPO DE RUTAS DE EDICION DE OFERTA ---
// Estas rutas ya están cubiertas por Route::resource('offers', ...), puedes borrarlas si quieres.
// Las dejo por si las necesitas para algo específico.
Route::get('/offers/{offer}/edit', [OfferController::class, 'edit'])->name('offers.edit');
Route::put('/offers/{offer}', [OfferController::class, 'update'])->name('offers.update');

// Carga las rutas de autenticación
require __DIR__.'/auth.php';