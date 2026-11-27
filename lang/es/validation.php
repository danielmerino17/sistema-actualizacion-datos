<?php

/*
|--------------------------------------------------------------------------
| Mensajes de validación en español
|--------------------------------------------------------------------------
| Se usan cuando un formulario no define su propio mensaje.
| Solo están las reglas que usa SACPA; si se usa otra, agregarla aquí.
*/

return [
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'email' => 'El campo :attribute debe ser un correo electrónico válido.',
    'exists' => 'El :attribute seleccionado no es válido.',
    'in' => 'El :attribute seleccionado no es válido.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => [
        'array' => 'El campo :attribute no puede tener más de :max elementos.',
        'file' => 'El archivo :attribute no puede pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no puede ser mayor que :max.',
        'string' => 'El campo :attribute no puede tener más de :max caracteres.',
    ],
    'min' => [
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
        'file' => 'El archivo :attribute debe pesar al menos :min kilobytes.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'numeric' => 'El campo :attribute debe ser un número.',
    'regex' => 'El formato del campo :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',

    /*
    | Nombres de los campos para que los mensajes se lean bien.
    */
    'attributes' => [
        'numero_trabajador' => 'número de trabajador',
        'rfc' => 'RFC',
        'tipo_contacto' => 'tipo de medio',
        'descripcion' => 'medio de contacto',
        'es_principal' => 'tipo de uso',
        'nombre' => 'nombre',
        'apellido_paterno' => 'apellido paterno',
        'apellido_materno' => 'apellido materno',
        'parentesco' => 'parentesco',
        'telefono_celular' => 'teléfono celular',
        'telefono_alterno' => 'teléfono alterno',
    ],
];
