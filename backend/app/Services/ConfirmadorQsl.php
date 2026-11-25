<?php

namespace App\Services;

use App\Enums\TipoQsl;
use App\Models\Contacto;
use App\Models\User;

/**
 * Innovación 1: confirmación QSL automática entre operadores del sistema.
 *
 * Si HC1QRC registra un contacto con LU4BB y LU4BB (también usuario) registra el mismo contacto
 * con HC1QRC en la misma banda y modo, a pocos minutos de diferencia, ambos quedan confirmados
 * sin tarjetas ni trámites, como hace LoTW.
 */
class ConfirmadorQsl
{
    /**
     * Diferencia máxima entre las horas de inicio anotadas por cada operador.
     */
    public const MINUTOS_TOLERANCIA = 15;

    /**
     * Confirma el contacto y su contraparte si coinciden; si no, le quita la confirmación automática.
     *
     * Al modificar un contacto, pasa la contraparte que tenía ANTES de guardar para limpiarla si ya no coincide.
     * Devuelve true si el contacto quedó confirmado.
     */
    public function sincronizar(Contacto $contacto, ?Contacto $contraparteAnterior = null): bool
    {
        $contraparte = $this->buscarContraparte($contacto);

        if ($contraparteAnterior !== null && $contraparteAnterior->isNot($contraparte)) {
            $this->quitarConfirmacion($contraparteAnterior);
        }

        if ($contraparte === null) {
            $this->quitarConfirmacion($contacto);

            return false;
        }

        $this->confirmar($contacto);
        $this->confirmar($contraparte);

        return true;
    }

    /**
     * Antes de eliminar un contacto, la contraparte deja de estar confirmada.
     */
    public function desvincular(Contacto $contacto): void
    {
        $contraparte = $this->buscarContraparte($contacto);

        if ($contraparte !== null) {
            $this->quitarConfirmacion($contraparte);
        }
    }

    /**
     * El contacto que la otra estación registró en su libro y que coincide con este.
     */
    public function buscarContraparte(Contacto $contacto): ?Contacto
    {
        $otroOperador = User::query()
            ->where('indicativo', $contacto->estacion_llamada)
            ->whereKeyNot($contacto->user_id)
            ->first();

        if ($otroOperador === null) {
            return null;
        }

        $miIndicativo = User::query()->whereKey($contacto->user_id)->value('indicativo');

        // Si hay varios candidatos dentro de la tolerancia se elige el de hora más cercana.
        // Se calcula en PHP para que funcione igual en PostgreSQL, MariaDB o cualquier base.
        return $otroOperador->contactos()
            ->where('estacion_llamada', $miIndicativo)
            ->where('banda_id', $contacto->banda_id)
            ->where('modo_id', $contacto->modo_id)
            ->whereBetween('inicio_utc', [
                $contacto->inicio_utc->copy()->subMinutes(self::MINUTOS_TOLERANCIA),
                $contacto->inicio_utc->copy()->addMinutes(self::MINUTOS_TOLERANCIA),
            ])
            ->get()
            ->sortBy(fn (Contacto $candidato): int => abs($candidato->inicio_utc->getTimestamp() - $contacto->inicio_utc->getTimestamp()))
            ->first();
    }

    private function confirmar(Contacto $contacto): void
    {
        $hoy = now('UTC')->toDateString();

        $contacto->confirmacionesQsl()->firstOrCreate(
            ['tipo' => TipoQsl::Interna],
            ['enviada' => true, 'fecha_envio' => $hoy, 'recibida' => true, 'fecha_recepcion' => $hoy],
        );
    }

    private function quitarConfirmacion(Contacto $contacto): void
    {
        $contacto->confirmacionesQsl()->where('tipo', TipoQsl::Interna)->delete();
    }
}
