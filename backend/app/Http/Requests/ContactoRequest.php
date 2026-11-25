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
            // El contacto se registra el mismo día en que ocurre (en UTC): ni fechas pasadas ni futuras.
            // Al editar se acepta además la fecha que ya tenía el contacto.
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.self::fechaMinima($this->contactoEditado()), 'before_or_equal:'.now('UTC')->toDateString()],
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
            'estacion_llamada' => __('indicativo'),
            'hora_inicio' => __('hora de inicio'),
            'hora_fin' => __('hora de fin'),
            'frecuencia_mhz' => __('frecuencia'),
            'modo_id' => __('modo'),
            'reporte_enviado' => __('reporte enviado'),
            'reporte_recibido' => __('reporte recibido'),
            'potencia_w' => __('potencia'),
            'grid_locator' => __('grid locator'),
            'nombre_operador' => __('nombre del operador'),
            'activacion' => __('activación'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'grid_locator.regex' => __('El grid locator debe tener el formato FI09 o FI09AA.'),
            'fecha.after_or_equal' => __('La fecha no puede ser anterior a hoy (:hoy UTC).', ['hoy' => now('UTC')->toDateString()]),
            'fecha.before_or_equal' => __('La fecha no puede ser posterior a hoy (:hoy UTC).', ['hoy' => now('UTC')->toDateString()]),
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
            $validator->errors()->add('frecuencia_mhz', __('La frecuencia está fuera de las bandas de radioaficionados.'));
        }
    }

    private function validarReportes(Validator $validator): void
    {
        $tipoReporte = Modo::findOrFail($this->integer('modo_id'))->tipo_reporte;

        foreach (['reporte_enviado', 'reporte_recibido'] as $campo) {
            if (! $tipoReporte->esValido((string) $this->input($campo))) {
                $validator->errors()->add(
                    $campo,
                    __('En este modo el reporte es :tipo (ej. :ejemplo).', ['tipo' => $tipoReporte->value, 'ejemplo' => $tipoReporte->ejemplo()]),
                );
            }
        }
    }

    private function validarHoras(Validator $validator): void
    {
        $fin = $this->finUtc();

        if ($fin !== null && $fin->lessThan($this->inicioUtc())) {
            $validator->errors()->add('hora_fin', __('La hora de fin no puede ser menor que la hora de inicio.'));
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
                __('Ya registraste un contacto con :indicativo en la misma banda y modo el :fecha UTC. ¿Quieres guardarlo de todos modos?', [
                    'indicativo' => $duplicado->estacion_llamada,
                    'fecha' => $duplicado->inicio_utc->format('Y-m-d H:i'),
                ]),
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

    /**
     * Primer día que acepta el formulario: hoy en UTC o, al editar, la fecha original del contacto si es anterior.
     */
    public static function fechaMinima(?Contacto $contacto = null): string
    {
        $hoy = now('UTC')->toDateString();
        $original = $contacto?->inicio_utc?->format('Y-m-d');

        return $original !== null && $original < $hoy ? $original : $hoy;
    }

    private function contactoEditado(): ?Contacto
    {
        $id = $this->route('contacto');

        return is_string($id) && ctype_digit($id) ? $this->user()?->contactos()->find((int) $id) : null;
    }
}
