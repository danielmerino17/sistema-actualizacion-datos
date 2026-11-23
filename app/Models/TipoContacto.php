<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo: Casa, Oficina, Celular, Trabajo, Correo.
 *
 * @property int $tico_id_tipo_contacto
 * @property string $tico_tipo_contacto
 */
#[Table(name: 'tipo_contacto', key: 'tico_id_tipo_contacto', timestamps: false)]
#[Fillable(['tico_tipo_contacto'])]
class TipoContacto extends Model
{
    /**
     * @return HasMany<Contacto, $this>
     */
    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class, 'cont_id_tipo_contacto');
    }
}
