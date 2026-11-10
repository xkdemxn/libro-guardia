<?php

use App\Http\Controllers\ConfirmacionQslController;
use App\Http\Controllers\ContactoController;
use App\Http\Controllers\EstadisticasController;
use App\Http\Controllers\ExportacionController;
use App\Http\Controllers\FichaIndicativoController;
use App\Http\Controllers\ImportacionController;
use App\Http\Controllers\PerfilPublicoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SolicitudConfirmacionController;
use App\Http\Controllers\TarjetaQslController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('contactos.index')
        : redirect()->route('login');
});

// Perfil público por indicativo: no necesita sesión (US-15).
Route::get('indicativos', [PerfilPublicoController::class, 'index'])->name('perfil-publico.index');
Route::get('indicativos/{indicativo}', [PerfilPublicoController::class, 'show'])->name('perfil-publico.show');

Route::middleware('auth')->group(function () {
    // Antes del resource para que estas rutas no se tomen como el id de un contacto.
    Route::get('contactos/exportar', [ExportacionController::class, 'create'])->name('exportacion.create');
    Route::post('contactos/exportar', [ExportacionController::class, 'store'])->name('exportacion.store');

    Route::get('contactos/importar', [ImportacionController::class, 'create'])->name('importacion.create');
    Route::post('contactos/importar', [ImportacionController::class, 'store'])->name('importacion.store');
    Route::get('contactos/importar/vista-previa', [ImportacionController::class, 'vistaPrevia'])->name('importacion.vista-previa');
    Route::post('contactos/importar/confirmar', [ImportacionController::class, 'confirmar'])->name('importacion.confirmar');
    Route::post('contactos/importar/cancelar', [ImportacionController::class, 'cancelar'])->name('importacion.cancelar');

    Route::get('contactos/ficha', FichaIndicativoController::class)->name('contactos.ficha');

    Route::resource('contactos', ContactoController::class);
    Route::put('contactos/{contacto}/qsl', [ConfirmacionQslController::class, 'update'])->name('contactos.qsl.update');
    Route::get('contactos/{contacto}/tarjeta', [TarjetaQslController::class, 'show'])->name('contactos.tarjeta');
    Route::get('contactos/{contacto}/tarjeta.svg', [TarjetaQslController::class, 'svg'])->name('contactos.tarjeta.svg');

    Route::get('estadisticas', EstadisticasController::class)->name('estadisticas');

    Route::get('solicitudes', [SolicitudConfirmacionController::class, 'index'])->name('solicitudes.index');
    Route::post('solicitudes/{contacto}/aceptar', [SolicitudConfirmacionController::class, 'aceptar'])->name('solicitudes.aceptar');
    Route::post('solicitudes/{contacto}/rechazar', [SolicitudConfirmacionController::class, 'rechazar'])->name('solicitudes.rechazar');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
