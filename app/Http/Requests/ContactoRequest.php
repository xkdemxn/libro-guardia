<?php

namespace App\Http\Requests;

use App\Models\Banda;
use App\Models\Contacto;
use App\Models\Modo;
use App\Models\PrefijoPais;
use App\Rules\IndicativoValido;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Valida un contacto al registrarlo o modificarlo (US-03, US-05, US-07).
 *
 * Además de las reglas de cada campo revisa que la frecuencia esté dentro de una banda,
 * que el reporte corresponda al modo, que el fin no sea antes del inicio y que no sea un duplicado.
 */
class ContactoRequest extends FormRequest
{
    private ?Banda $banda = null;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'estacion_llamada' => IndicativoValido::normalizar($this->input('estacion_llamada')),
            'frecuencia_mhz' => str_replace(',', '.', (string) $this->input('frecuencia_mhz')),
            'grid_locator' => $this->filled('grid_locator') ? strtoupper(trim($this->input('grid_locator'))) : null,
            'activacion' => $this->filled('activacion') ? strtoupper(trim($this->input('activacion'))) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'estacion_llamada' => ['required', 'string', 'max:20', new IndicativoValido],
            // El contacto se registra el mismo día en que ocurre: no se aceptan fechas futuras (en UTC).
            'fecha' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now('UTC')->toDateString()],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['nullable', 'date_format:H:i'],
            'frecuencia_mhz' => ['required', 'numeric', 'gt:0'],
            'modo_id' => ['bail', 'required', 'integer', 'exists:modos,id'],
            'reporte_enviado' => ['required', 'string', 'max:4'],
            'reporte_recibido' => ['required', 'string', 'max:4'],
            'potencia_w' => ['nullable', 'numeric', 'gt:0', 'max:1500'],
            'grid_locator' => ['nullable', 'regex:/^[A-R]{2}[0-9]{2}([A-X]{2})?$/'],
            'nombre_operador' => ['nullable', 'string', 'max:100'],
            'equipo' => ['nullable', 'string', 'max:255'],
            'activacion' => ['nullable', 'string', 'max:30'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'confirmar_duplicado' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'estacion_llamada' => 'indicativo',
            'hora_inicio' => 'hora de inicio',
            'hora_fin' => 'hora de fin',
            'frecuencia_mhz' => 'frecuencia',
            'modo_id' => 'modo',
            'reporte_enviado' => 'reporte enviado',
            'reporte_recibido' => 'reporte recibido',
            'potencia_w' => 'potencia',
            'grid_locator' => 'grid locator',
            'nombre_operador' => 'nombre del operador',
            'activacion' => 'activación',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'grid_locator.regex' => 'El grid locator debe tener el formato FI09 o FI09AA.',
            'fecha.before_or_equal' => 'La fecha no puede ser posterior a hoy ('.now('UTC')->toDateString().' UTC).',
        ];
    }

    /**
     * Validaciones que dependen de varios campos a la vez.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $this->validarBanda($validator);
                $this->validarReportes($validator);
                $this->validarHoras($validator);

                if ($validator->errors()->isEmpty() && ! $this->boolean('confirmar_duplicado')) {
                    $this->avisarSiEsDuplicado($validator);
                }
            },
        ];
    }

    private function validarBanda(Validator $validator): void
    {
        $this->banda = Banda::paraFrecuencia((float) $this->input('frecuencia_mhz'));

        if ($this->banda === null) {
            $validator->errors()->add('frecuencia_mhz', 'La frecuencia está fuera de las bandas de radioaficionados.');
        }
    }

    private function validarReportes(Validator $validator): void
    {
        $tipoReporte = Modo::findOrFail($this->integer('modo_id'))->tipo_reporte;

        foreach (['reporte_enviado', 'reporte_recibido'] as $campo) {
            if (! $tipoReporte->esValido((string) $this->input($campo))) {
                $validator->errors()->add(
                    $campo,
                    "En este modo el reporte es {$tipoReporte->value} (ej. {$tipoReporte->ejemplo()}).",
                );
            }
        }
    }

    private function validarHoras(Validator $validator): void
    {
        $fin = $this->finUtc();

        if ($fin !== null && $fin->lessThan($this->inicioUtc())) {
            $validator->errors()->add('hora_fin', 'La hora de fin no puede ser menor que la hora de inicio.');
        }
    }

    private function avisarSiEsDuplicado(Validator $validator): void
    {
        $duplicado = $this->user()->contactos()
            ->posiblesDuplicados(
                $this->input('estacion_llamada'),
                $this->banda->id,
                $this->integer('modo_id'),
                $this->inicioUtc(),
                $this->route('contacto') !== null ? (int) $this->route('contacto') : null,
            )
            ->first();

        if ($duplicado !== null) {
            $validator->errors()->add(
                'duplicado',
                "Ya registraste un contacto con {$duplicado->estacion_llamada} en la misma banda y modo el "
                .$duplicado->inicio_utc->format('Y-m-d H:i').' UTC. ¿Quieres guardarlo de todos modos?',
            );
        }
    }

    public function inicioUtc(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('Y-m-d H:i', $this->input('fecha').' '.$this->input('hora_inicio'), 'UTC')
            ->startOfMinute();
    }

    public function finUtc(): ?CarbonImmutable
    {
        if (! $this->filled('hora_fin')) {
            return null;
        }

        return CarbonImmutable::createFromFormat('Y-m-d H:i', $this->input('fecha').' '.$this->input('hora_fin'), 'UTC')
            ->startOfMinute();
    }

    /**
     * Atributos listos para guardar en el modelo Contacto, con banda y país calculados.
     *
     * @return array<string, mixed>
     */
    public function datosContacto(): array
    {
        return [
            ...$this->safe()->except(['fecha', 'hora_inicio', 'hora_fin', 'confirmar_duplicado']),
            'inicio_utc' => $this->inicioUtc(),
            'fin_utc' => $this->finUtc(),
            'banda_id' => $this->banda->id,
            'prefijo_pais_id' => PrefijoPais::paraIndicativo($this->input('estacion_llamada'))?->id,
        ];
    }
}
