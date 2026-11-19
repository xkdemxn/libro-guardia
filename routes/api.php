<?php

use App\Http\Controllers\Api\Auth\CodigoCorreoSegundoFactorController;
use App\Http\Controllers\Api\Auth\ContrasenaRestablecidaController;
use App\Http\Controllers\Api\Auth\DesafioSegundoFactorController;
use App\Http\Controllers\Api\Auth\EnlaceRestablecimientoController;
use App\Http\Controllers\Api\Auth\RegistroController;
use App\Http\Controllers\Api\Auth\SesionController;
use App\Http\Controllers\Api\Auth\VerificacionCorreoController;
use App\Http\Controllers\Api\UsuarioActualController;
use Illuminate\Support\Facades\Route;

/*
 * API de la SPA de React (frontend/). Todas las rutas usan la sesión de Laravel por cookie
 * (Sanctum, modo SPA): antes de un POST la SPA pide /sanctum/csrf-cookie.
 */

Route::post('sesion', [SesionController::class, 'store'])->name('api.sesion.store')->block();
Route::post('registro', [RegistroController::class, 'store'])->name('api.registro.store');

Route::get('segundo-factor/desafio', [DesafioSegundoFactorController::class, 'show'])->name('api.segundo-factor.show')->block();
Route::post('segundo-factor/desafio', [DesafioSegundoFactorController::class, 'store'])->name('api.segundo-factor.store')->block();
Route::post('segundo-factor/desafio/correo', [CodigoCorreoSegundoFactorController::class, 'store'])->name('api.segundo-factor.correo')->block();

Route::post('contrasena/enlace', [EnlaceRestablecimientoController::class, 'store'])
    ->middleware('throttle:6,1')->name('api.contrasena.enlace');
Route::post('contrasena/restablecer', [ContrasenaRestablecidaController::class, 'store'])->name('api.contrasena.restablecer');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('usuario', UsuarioActualController::class)->name('api.usuario');
    Route::delete('sesion', [SesionController::class, 'destroy'])->name('api.sesion.destroy');

    Route::post('correo/verificacion', [VerificacionCorreoController::class, 'store'])
        ->middleware('throttle:6,1')->name('api.correo.verificacion');
});
