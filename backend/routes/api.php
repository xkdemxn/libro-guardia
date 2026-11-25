<?php

use App\Http\Controllers\Api\Auth\CodigoCorreoSegundoFactorController;
use App\Http\Controllers\Api\Auth\ContrasenaRestablecidaController;
use App\Http\Controllers\Api\Auth\DesafioSegundoFactorController;
use App\Http\Controllers\Api\Auth\EnlaceRestablecimientoController;
use App\Http\Controllers\Api\Auth\RegistroController;
use App\Http\Controllers\Api\Auth\SesionController;
use App\Http\Controllers\Api\Auth\VerificacionCorreoController;
use App\Http\Controllers\Api\CatalogosController;
use App\Http\Controllers\Api\ConfiguracionSegundoFactorController;
use App\Http\Controllers\Api\ConfirmacionQslController;
use App\Http\Controllers\Api\ContactoController;
use App\Http\Controllers\Api\ContrasenaController;
use App\Http\Controllers\Api\EstadisticasController;
use App\Http\Controllers\Api\ExportacionController;
use App\Http\Controllers\Api\FichaIndicativoController;
use App\Http\Controllers\Api\ImportacionController;
use App\Http\Controllers\Api\PerfilController;
use App\Http\Controllers\Api\PerfilPublicoController;
use App\Http\Controllers\Api\PrefijosController;
use App\Http\Controllers\Api\SolicitudConfirmacionController;
use App\Http\Controllers\Api\TarjetaQslController;
use App\Http\Controllers\Api\UbicacionesController;
use App\Http\Controllers\Api\UsuarioActualController;
use App\Http\Controllers\Api\VerificacionQslController;
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
Route::get('prefijos', PrefijosController::class)->name('api.prefijos');
// «Consulta una QSL» sin sesión: número de QSO + indicativo contactado. Limitada para no adivinar números.
Route::get('qsl/verificar', VerificacionQslController::class)->middleware('throttle:15,1')->name('api.qsl.verificar');

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

    Route::get('catalogos', CatalogosController::class)->name('api.catalogos');

    // Antes del resource para que «ficha» y «exportar» no se tomen como el id de un contacto.
    Route::get('contactos/ficha', FichaIndicativoController::class)->name('api.contactos.ficha');
    Route::post('contactos/exportar', [ExportacionController::class, 'store'])->name('api.contactos.exportar');
    Route::apiResource('contactos', ContactoController::class)->names('api.contactos');
    Route::put('contactos/{contacto}/qsl', [ConfirmacionQslController::class, 'update'])->name('api.contactos.qsl');

    Route::get('solicitudes', [SolicitudConfirmacionController::class, 'index'])->name('api.solicitudes.index');
    Route::post('solicitudes/{contacto}/aceptar', [SolicitudConfirmacionController::class, 'aceptar'])->name('api.solicitudes.aceptar');
    Route::post('solicitudes/{contacto}/rechazar', [SolicitudConfirmacionController::class, 'rechazar'])->name('api.solicitudes.rechazar');

    Route::get('contactos/{contacto}/tarjeta', [TarjetaQslController::class, 'show'])->name('api.tarjeta.show');
    Route::get('contactos/{contacto}/tarjeta.svg', [TarjetaQslController::class, 'svg'])->name('api.tarjeta.svg');
    Route::post('contactos/{contacto}/tarjeta/enviar', [TarjetaQslController::class, 'enviar'])
        ->middleware(['verified', 'throttle:5,1'])->name('api.tarjeta.enviar');
    Route::patch('tarjeta-qsl', [TarjetaQslController::class, 'personalizar'])->name('api.tarjeta.personalizar');

    Route::post('importaciones', [ImportacionController::class, 'store'])->name('api.importaciones.store');
    Route::get('importaciones/pendiente', [ImportacionController::class, 'show'])->name('api.importaciones.show');
    Route::post('importaciones/pendiente/confirmar', [ImportacionController::class, 'confirmar'])->name('api.importaciones.confirmar');
    Route::delete('importaciones/pendiente', [ImportacionController::class, 'destroy'])->name('api.importaciones.destroy');
});
