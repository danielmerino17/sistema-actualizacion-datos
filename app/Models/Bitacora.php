<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $bita_id_bitacora
 * @property int $bita_id_usuario
 * @property string $bita_accion
 * @property Carbon $bita_fecha_hora
 */
#[Table(name: 'bitacora', key: 'bita_id_bitacora', timestamps: false)]
#[Fillable(['bita_id_usuario', 'bita_accion', 'bita_fecha_hora'])]
class Bitacora extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'bita_fecha_hora' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'bita_id_usuario');
    }
}
