<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TeamController;
// --- INICIO: IMPORTACIÓN AÑADIDA ---
use App\Http\Controllers\Admin\PackageController; 
use App\Http\Controllers\Admin\DiscountController;
use App\Http\Controllers\Admin\DiscountImportController; 
// --- FIN: IMPORTACIÓN AÑADIDA ---
use App\Http\Controllers\TeamLead\ManagementController;
use App\Http\Controllers\ClientController;

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
    Route::get('/offers/{offer}/pdf', [OfferController::class, 'generatePDF'])->name('offers.pdf');
    Route::post('offers/{offer}/lock', [OfferController::class, 'lock'])->name('offers.lock');
    // NOTA: La ruta exportFunnel de mi mensaje anterior no está aquí, 
    // pero si la necesitas, puedes volver a añadirla.

    // Importación de Terminales
    Route::get('/terminals/import', [ImportController::class, 'create'])->name('terminals.import.create');
    Route::post('/terminals/import', [ImportController::class, 'store'])->name('terminals.import.store');

    // Gestión de Clientes
    Route::resource('clients', ClientController::class);
    Route::get('/clients/{client}/offers', [ClientController::class, 'showOffers'])->name('clients.offers');
});

// --- GRUPO DE RUTAS DE ADMINISTRACIÓN ---
Route::prefix('admin')
    ->middleware(['auth', 'is_admin']) // Uso 'is_admin' como en tu archivo
    ->name('admin.')
    ->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('teams', TeamController::class);
        
        // --- INICIO: RUTAS DE PAQUETES AÑADIDAS ---
        Route::get('packages', [PackageController::class, 'index'])->name('packages.index');
        Route::get('packages/{package}/edit', [PackageController::class, 'edit'])->name('packages.edit');
        Route::put('packages/{package}', [PackageController::class, 'update'])->name('packages.update');
        // --- FIN: RUTAS DE PAQUETES AÑADIDAS ---
        // --- INICIO: RUTAS DE DESCUENTOS (COMPLETAS) ---
        Route::get('discounts/generate-seeder', [DiscountController::class, 'generateSeeder'])->name('discounts.generateSeeder');
        Route::get('discounts/export-csv', [DiscountController::class, 'exportCsv'])->name('discounts.exportCsv');
        
        // Importación
        Route::get('discounts/import-csv', [DiscountImportController::class, 'showImportForm'])->name('discounts.importCsv');
        Route::post('discounts/import-csv', [DiscountImportController::class, 'storeCsv'])->name('discounts.storeCsv');
        
        // Rutas CRUD estándar (index, create, store, edit, update, destroy)
        Route::resource('discounts', DiscountController::class);
        // --- FIN: RUTAS DE DESCUENTOS ---
});

// --- GRUPO DE RUTAS DE JEFE DE EQUIPO (Fusionado) ---
Route::prefix('team-lead')
    ->middleware(['auth', 'isTeamLead']) // Uso 'isTeamLead' como en tu archivo
    ->name('team-lead.')
    ->group(function () {
        // Rutas de ManagementController
        Route::get('users', [ManagementController::class, 'index'])->name('users.index');
        Route::get('users/{user}/edit', [ManagementController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [ManagementController::class, 'update'])->name('users.update');
        
        // Rutas de TeamLead\UserController
        Route::get('/users/create', [App\Http\Controllers\TeamLead\UserController::class, 'create'])->name('users.create');
        Route::post('/users', [App\Http\Controllers\TeamLead\UserController::class, 'store'])->name('users.store');
});

// Carga las rutas de autenticación
require __DIR__.'/auth.php';

