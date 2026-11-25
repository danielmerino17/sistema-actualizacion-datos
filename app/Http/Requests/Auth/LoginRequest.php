<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'numero_trabajador' => is_string($this->input('numero_trabajador'))
                ? trim($this->input('numero_trabajador'))
                : $this->input('numero_trabajador'),
            'rfc' => is_string($this->input('rfc'))
                ? mb_strtoupper(trim($this->input('rfc')))
                : $this->input('rfc'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'numero_trabajador' => ['required', 'string', 'max:7'],
            'rfc' => ['required', 'string', 'max:13'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'numero_trabajador.required' => 'Ingresa tu número de trabajador.',
            'rfc.required' => 'Ingresa tu RFC.',
        ];
    }
}