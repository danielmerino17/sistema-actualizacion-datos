<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Registro de acciones del sistema.
 *
 * @property int $bita_id_bitacora
 * @property int|null $bita_id_usuario quién hizo la acción (vacío: login fallido o consola)
 * @property int|null $bita_id_profesor a qué profesor afectó
 * @property string $bita_accion código corto, ej. 'crear_dato_personal'
 * @property string|null $bita_detalle qué registro, desde qué IP, etc.
 * @property Carbon $bita_fecha_hora
 */
#[Table(name: 'bitacora', key: 'bita_id_bitacora', timestamps: false)]
#[Fillable(['bita_id_usuario', 'bita_id_profesor', 'bita_accion', 'bita_detalle', 'bita_fecha_hora'])]
class Bitacora extends Model
{
    /**
     * Registra una acción.
     *
     * @param  Usuario|null  $usuario  quién la hizo (null si no hay sesión, ej. login fallido)
     * @param  string  $accion  código corto (máx. 20 caracteres), ej. 'crear_dato_personal'
     * @param  Usuario|null  $profesor  a qué profesor afectó
     * @param  string|null  $detalle  qué registro se tocó, IP, etc. NUNCA datos sensibles (RFC, teléfonos)
     */
    public static function registrar(?Usuario $usuario, string $accion, ?Usuario $profesor = null, ?string $detalle = null): self
    {
        return self::create([
            'bita_id_usuario' => $usuario?->usua_id_usuario,
            'bita_id_profesor' => $profesor?->usua_id_usuario,
            'bita_accion' => mb_substr($accion, 0, 20),
            'bita_detalle' => $detalle !== null ? mb_substr($detalle, 0, 255) : null,
            'bita_fecha_hora' => now(),
        ]);
    }

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
     * Quién hizo la acción.
     *
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'bita_id_usuario');
    }

    /**
     * A qué profesor afectó.
     *
     * @return BelongsTo<Usuario, $this>
     */
    public function profesor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'bita_id_profesor');
    }
}
