<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Auth\RegisteredUserController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- RUTA PRINCIPAL ---
// Si el usuario es un invitado (no ha iniciado sesión), muestra la página de login.
Route::get('/', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest');

// --- RUTAS PARA USUARIOS AUTENTICADOS ---
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Dashboard (página de inicio para usuarios logueados)
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

});

// --- GRUPO DE RUTAS DE ADMINISTRACIÓN ---
Route::prefix('admin')
    ->middleware(['auth', 'is_admin'])
    ->name('admin.')
    ->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('teams', TeamController::class);
});

 Route::get('register', [RegisteredUserController::class, 'create'])
                 ->middleware('guest')
                 ->name('register');

 Route::post('register', [RegisteredUserController::class, 'store'])
                 ->middleware('guest');

// Carga las rutas de autenticación (login, logout, etc.)
require __DIR__.'/auth.php';