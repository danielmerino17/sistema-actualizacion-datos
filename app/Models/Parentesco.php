<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catálogo: Madre, Padre, Hijo(a), Hermano(a), Esposo(a), Otro.
 *
 * @property int $pare_id_parentesco
 * @property string $pare_parentesco
 */
#[Table(name: 'parentesco', key: 'pare_id_parentesco', timestamps: false)]
#[Fillable(['pare_parentesco'])]
class Parentesco extends Model
{
    /**
     * @return HasMany<PersonaContacto, $this>
     */
    public function personas(): HasMany
    {
        return $this->hasMany(PersonaContacto::class, 'peco_id_parentesco');
    }
}
