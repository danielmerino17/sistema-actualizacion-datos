<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $rol_id_rol
 * @property string $rol_nombre_rol
 * @property string|null $rol_descripcion
 */
#[Table(name: 'rol', key: 'rol_id_rol', timestamps: false)]
#[Fillable(['rol_nombre_rol', 'rol_descripcion'])]
class Rol extends Model
{
    /**
     * @return HasMany<Usuario, $this>
     */
    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'usua_id_rol');
    }

    /**
     * @return BelongsToMany<Privilegio, $this>
     */
    public function privilegios(): BelongsToMany
    {
        return $this->belongsToMany(Privilegio::class, 'privilegio_rol', 'pero_id_rol', 'pero_id_privilegio');
    }
}
