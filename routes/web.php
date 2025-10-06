<?php

use App\Http\Controllers\ImportController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ProfileController;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/', function () {
    return Inertia::render('Inicio', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/ofertas/crear', [OfferController::class, 'create'])->name('Crear Oferta');

Route::get('/ofertas', [OfferController::class, 'index'])
    ->name('Consultar Ofertas');
Route::get('/terminals/import', [ImportController::class, 'create'])->name('terminals.import.create');
    Route::post('/terminals/import', [ImportController::class, 'store'])->name('terminals.import.store');

Route::get('/offers/create', [OfferController::class, 'create'])->name('offers.create');
Route::post('/offers', [OfferController::class, 'store'])->name('offers.store');
Route::resource('offers', OfferController::class);

require __DIR__.'/auth.php';
