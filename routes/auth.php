<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// --- Añade el use para OfferController ---
use App\Http\Controllers\OfferController;
// --- (Añade aquí uses para otros controladores como ClientController, ProfileController si los usas en otras rutas no mostradas) ---

Route::middleware('guest')->group(function () {
    // DESCOMENTAMOS Y ASEGURAMOS QUE EL REGISTRO ESTÁ DESHABILITADO
     Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
     Route::post('register', [RegisteredUserController::class, 'store']);

    // ESTAS SON LAS RUTAS CLAVE PARA EL LOGIN
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
                ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
                ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
                ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
                ->name('password.store');
});

Route::middleware('auth')->group(function () {

    // --- (AQUÍ DEBERÍAN ESTAR TUS OTRAS RUTAS PROTEGIDAS: Dashboard, Profile, Offers resource, Clients resource, etc.) ---
    // Ejemplo de cómo deberían estar tus rutas de Offers (si usas resource):
    /*
    Route::resource('offers', OfferController::class);
    Route::get('/offers/{offer}/pdf', [OfferController::class, 'generatePDF'])->name('offers.pdf');
    */
    // --- (Asegúrate de que tus rutas de offers existen aquí) ---


    // ### ¡NUEVA RUTA DE EXPORTACIÓN AÑADIDA AQUÍ! ###
    Route::get('/offers-export-funnel', [OfferController::class, 'exportFunnel'])
        ->name('offers.exportFunnel');
    // ### ------------------------------------------ ###


    // --- Rutas de Verificación y Contraseña (Las que ya tenías) ---
    Route::get('verify-email', EmailVerificationPromptController::class)
                ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});

// Nota: Si tus rutas de Offers, Clients, Profile, Dashboard, etc., están en otro archivo
// o definidas de otra manera, asegúrate de que esta nueva ruta 'offers.exportFunnel'
// esté también protegida por el middleware 'auth'.