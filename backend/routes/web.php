<?php

use App\Http\Controllers\SpaController;
use App\Http\Controllers\VerificarCorreoController;
use Illuminate\Support\Facades\Route;

/*
 * La interfaz es la SPA de React (frontend/), que habla con routes/api.php. Aquí solo están los
 * enlaces que llegan por correo y la entrega de la SPA para todas las demás direcciones.
 */

Route::get('verify-email/{id}/{hash}', VerificarCorreoController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// Al final: todo lo que no sea de la API, de Sanctum o un archivo público lo resuelve React Router.
Route::get('{ruta?}', SpaController::class)
    ->where('ruta', '^(?!api/|api$|sanctum/|storage/|up$).*')
    ->name('spa');
