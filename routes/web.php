<?php

use App\Http\Controllers\VerificarCorreoController;
use Illuminate\Support\Facades\Route;

/*
 * La interfaz es la SPA de React (frontend/), que habla con routes/api.php. Laravel solo atiende
 * aquí los enlaces que llegan por correo y manda todo lo demás a la SPA.
 */

Route::redirect('/', config('app.frontend_url'));

Route::get('verify-email/{id}/{hash}', VerificarCorreoController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
