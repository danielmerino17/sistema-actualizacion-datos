<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $lice_id_licenciatura
 * @property string $lice_nombre_licenciatura
 * @property string $lice_clave
 */
#[Table(name: 'licenciatura', key: 'lice_id_licenciatura', timestamps: false)]
#[Fillable(['lice_nombre_licenciatura', 'lice_clave'])]
class Licenciatura extends Model
{
    /**
     * @return HasMany<Coordinacion, $this>
     */
    public function coordinaciones(): HasMany
    {
        return $this->hasMany(Coordinacion::class, 'coor_id_licenciatura');
    }
}
