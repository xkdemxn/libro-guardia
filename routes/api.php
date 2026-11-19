<?php

use App\Http\Controllers\Api\Auth\CodigoCorreoSegundoFactorController;
use App\Http\Controllers\Api\Auth\ContrasenaRestablecidaController;
use App\Http\Controllers\Api\Auth\DesafioSegundoFactorController;
use App\Http\Controllers\Api\Auth\EnlaceRestablecimientoController;
use App\Http\Controllers\Api\Auth\RegistroController;
use App\Http\Controllers\Api\Auth\SesionController;
use App\Http\Controllers\Api\Auth\VerificacionCorreoController;
use App\Http\Controllers\Api\ConfiguracionSegundoFactorController;
use App\Http\Controllers\Api\ContrasenaController;
use App\Http\Controllers\Api\EstadisticasController;
use App\Http\Controllers\Api\PerfilController;
use App\Http\Controllers\Api\PerfilPublicoController;
use App\Http\Controllers\Api\UbicacionesController;
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

// Perfil público por indicativo: no necesita sesión (US-15).
Route::get('indicativos/{indicativo}', [PerfilPublicoController::class, 'show'])->name('api.perfil-publico.show');
Route::get('ubicaciones', UbicacionesController::class)->name('api.ubicaciones');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('usuario', UsuarioActualController::class)->name('api.usuario');
    Route::delete('sesion', [SesionController::class, 'destroy'])->name('api.sesion.destroy');

    Route::post('correo/verificacion', [VerificacionCorreoController::class, 'store'])
        ->middleware('throttle:6,1')->name('api.correo.verificacion');

    Route::get('perfil', [PerfilController::class, 'show'])->name('api.perfil.show');
    Route::patch('perfil', [PerfilController::class, 'update'])->name('api.perfil.update');
    Route::delete('perfil', [PerfilController::class, 'destroy'])->name('api.perfil.destroy');
    Route::put('contrasena', [ContrasenaController::class, 'update'])->name('api.contrasena.update');

    Route::post('segundo-factor/configuracion', [ConfiguracionSegundoFactorController::class, 'update'])
        ->middleware('throttle:10,1')->name('api.segundo-factor.configuracion')->block();
    Route::post('segundo-factor/configuracion/confirmar', [ConfiguracionSegundoFactorController::class, 'confirmar'])
        ->middleware('throttle:10,1')->name('api.segundo-factor.confirmar')->block();

    Route::get('estadisticas', EstadisticasController::class)->name('api.estadisticas');
});
