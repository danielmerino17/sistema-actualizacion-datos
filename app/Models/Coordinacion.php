<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $coor_id_coordinacion
 * @property int $coor_id_licenciatura
 * @property string $coor_nombre_coordinacion
 */
#[Table(name: 'coordinacion', key: 'coor_id_coordinacion', timestamps: false)]
#[Fillable(['coor_id_licenciatura', 'coor_nombre_coordinacion'])]
class Coordinacion extends Model
{
    /**
     * @return BelongsTo<Licenciatura, $this>
     */
    public function licenciatura(): BelongsTo
    {
        return $this->belongsTo(Licenciatura::class, 'coor_id_licenciatura');
    }

    /**
     * @return BelongsToMany<Usuario, $this>
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(Usuario::class, 'usuario_coordinacion', 'usco_id_coordinacion', 'usco_id_usuario');
    }
}
