<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Un medio de contacto de una persona: correo, celular, teléfono de casa, etc.
 *
 * @property int $cont_id_contacto
 * @property int $cont_id_persona_contacto
 * @property int $cont_id_tipo_contacto
 * @property string $cont_descripcion
 * @property bool $cont_es_principal
 * @property string|null $cont_lada
 * @property string|null $cont_ext
 * @property Carbon $cont_fecha_actualizacion
 */
#[Table(name: 'contacto', key: 'cont_id_contacto', timestamps: false)]
#[Fillable([
    'cont_id_persona_contacto',
    'cont_id_tipo_contacto',
    'cont_descripcion',
    'cont_es_principal',
    'cont_lada',
    'cont_ext',
])]
class Contacto extends Model
{
    /**
     * Cada vez que se guarda, se actualiza su fecha de actualización.
     */
    protected static function booted(): void
    {
        static::saving(function (Contacto $contacto): void {
            $contacto->cont_fecha_actualizacion = Carbon::now();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cont_es_principal' => 'boolean',
            'cont_fecha_actualizacion' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PersonaContacto, $this>
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(PersonaContacto::class, 'cont_id_persona_contacto');
    }

    /**
     * @return BelongsTo<TipoContacto, $this>
     */
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoContacto::class, 'cont_id_tipo_contacto');
    }
}
