<?php

namespace App\Http\Requests\Profesor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validación del formulario "Nuevo / Edición de contacto de emergencia".
 *
 * Campos que manda la pantalla:
 *  - nombre, apellido_paterno, apellido_materno (opcional)
 *  - parentesco: id del catálogo parentesco
 *  - telefono_celular: obligatorio, 10 dígitos (se aceptan espacios)
 *  - telefono_alterno: opcional, 10 dígitos
 */
class GuardarContactoEmergenciaRequest extends FormRequest
{
    /** 10 dígitos, permitiendo espacios, guiones, puntos o paréntesis entre ellos. */
    private const FORMATO_TELEFONO = '/^[\s\-().]*(\d[\s\-().]*){10}$/';

    public function authorize(): bool
    {
        // Quién puede entrar lo controlan las rutas (auth + rol:Profesor).
        return true;
    }

    protected function prepareForValidation(): void
    {
        $limpio = [];

        foreach (['nombre', 'apellido_paterno', 'apellido_materno', 'telefono_celular', 'telefono_alterno'] as $campo) {
            $valor = $this->input($campo);
            $limpio[$campo] = is_string($valor) ? (trim($valor) === '' ? null : trim($valor)) : $valor;
        }

        $this->merge($limpio);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:40'],
            'apellido_paterno' => ['required', 'string', 'max:30'],
            'apellido_materno' => ['nullable', 'string', 'max:30'],
            'parentesco' => ['required', 'integer', Rule::exists('parentesco', 'pare_id_parentesco')],
            'telefono_celular' => ['required', 'string', 'max:20', 'regex:'.self::FORMATO_TELEFONO],
            'telefono_alterno' => ['nullable', 'string', 'max:20', 'regex:'.self::FORMATO_TELEFONO],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre.',
            'nombre.max' => 'El nombre no puede tener más de 40 caracteres.',
            'apellido_paterno.required' => 'Escribe el apellido paterno.',
            'apellido_paterno.max' => 'El apellido paterno no puede tener más de 30 caracteres.',
            'apellido_materno.max' => 'El apellido materno no puede tener más de 30 caracteres.',
            'parentesco.required' => 'Selecciona el parentesco.',
            'parentesco.integer' => 'Selecciona un parentesco válido.',
            'parentesco.exists' => 'Selecciona un parentesco válido.',
            'telefono_celular.required' => 'Escribe el teléfono celular.',
            'telefono_celular.regex' => 'El teléfono celular debe tener 10 dígitos.',
            'telefono_celular.max' => 'El teléfono celular debe tener 10 dígitos.',
            'telefono_alterno.regex' => 'El teléfono alterno debe tener 10 dígitos.',
            'telefono_alterno.max' => 'El teléfono alterno debe tener 10 dígitos.',
        ];
    }

    /**
     * Datos de la persona, listos para la tabla persona_contacto.
     *
     * @return array{peco_nombre: string, peco_apellido_paterno: string, peco_apellido_materno: string|null, peco_id_parentesco: int}
     */
    public function datosPersona(): array
    {
        return [
            'peco_nombre' => (string) $this->input('nombre'),
            'peco_apellido_paterno' => (string) $this->input('apellido_paterno'),
            'peco_apellido_materno' => $this->input('apellido_materno'),
            'peco_id_parentesco' => $this->integer('parentesco'),
        ];
    }
}
