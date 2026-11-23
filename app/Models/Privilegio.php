<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $priv_id_privilegio
 * @property string $priv_tipo_privilegio
 * @property string|null $priv_descripcion
 */
#[Table(name: 'privilegio', key: 'priv_id_privilegio', timestamps: false)]
#[Fillable(['priv_tipo_privilegio', 'priv_descripcion'])]
class Privilegio extends Model
{
    /**
     * @return BelongsToMany<Rol, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'privilegio_rol', 'pero_id_privilegio', 'pero_id_rol');
    }
}
