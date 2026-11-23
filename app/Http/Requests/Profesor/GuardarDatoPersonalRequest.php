<?php

namespace App\Http\Requests\Profesor;

use App\Models\TipoContacto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validación del formulario "Registro / Edición de datos personales".
 *
 * Campos que manda la pantalla:
 *  - tipo_contacto: id del catálogo tipo_contacto (Correo, Celular, Casa...)
 *  - descripcion:   el correo o el teléfono
 *  - es_principal:  true = Principal, false = Secundario
 */
class GuardarDatoPersonalRequest extends FormRequest
{
    /** Dígitos de un teléfono en México (con lada, sin +52). */
    private const DIGITOS_TELEFONO = 10;

    public function authorize(): bool
    {
        // Quién puede entrar lo controlan las rutas (auth + rol:Profesor).
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'descripcion' => is_string($this->input('descripcion'))
                ? trim($this->input('descripcion'))
                : $this->input('descripcion'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tipo_contacto' => ['required', 'integer', Rule::exists('tipo_contacto', 'tico_id_tipo_contacto')],
            'descripcion' => ['required', 'string', 'max:50'],
            'es_principal' => ['required', 'boolean'],
        ];
    }

    /**
     * Revisa el formato según el tipo de medio: correo o teléfono.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $descripcion = (string) $this->input('descripcion');

                if ($this->esCorreo()) {
                    if (filter_var($descripcion, FILTER_VALIDATE_EMAIL) === false) {
                        $validator->errors()->add('descripcion', 'Escribe un correo electrónico válido.');
                    }

                    return;
                }

                $digitos = preg_replace('/[\s\-().]/', '', $descripcion) ?? '';

                if (! preg_match('/^\d{'.self::DIGITOS_TELEFONO.'}$/', $digitos)) {
                    $validator->errors()->add(
                        'descripcion',
                        'El teléfono debe tener '.self::DIGITOS_TELEFONO.' dígitos (puedes usar espacios).',
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tipo_contacto.required' => 'Selecciona el tipo de medio.',
            'tipo_contacto.integer' => 'Selecciona un tipo de medio válido.',
            'tipo_contacto.exists' => 'Selecciona un tipo de medio válido.',
            'descripcion.required' => 'Escribe el medio de contacto.',
            'descripcion.max' => 'El medio de contacto no puede tener más de 50 caracteres.',
            'es_principal.required' => 'Selecciona el tipo de uso.',
            'es_principal.boolean' => 'Selecciona un tipo de uso válido.',
        ];
    }

    /**
     * Datos listos para guardar en la tabla contacto.
     *
     * @return array{cont_id_tipo_contacto: int, cont_descripcion: string, cont_es_principal: bool}
     */
    public function datosContacto(): array
    {
        return [
            'cont_id_tipo_contacto' => $this->integer('tipo_contacto'),
            'cont_descripcion' => (string) $this->input('descripcion'),
            'cont_es_principal' => $this->boolean('es_principal'),
        ];
    }

    private function esCorreo(): bool
    {
        return TipoContacto::whereKey($this->integer('tipo_contacto'))
            ->where('tico_tipo_contacto', 'Correo')
            ->exists();
    }
}
