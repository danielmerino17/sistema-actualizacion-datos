<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Usuario del sistema: profesores y administradores (según su rol).
 *
 * @property int $usua_id_usuario
 * @property int $usua_id_rol
 * @property string $usua_nombre
 * @property string $usua_apellido_paterno
 * @property string|null $usua_apellido_materno
 * @property string $usua_correo
 * @property bool $usua_activo
 */
#[Table(name: 'usuario', key: 'usua_id_usuario', timestamps: false)]
#[Fillable([
    'usua_id_rol',
    'usua_nombre',
    'usua_apellido_paterno',
    'usua_apellido_materno',
    'usua_correo',
    'usua_activo',
])]
class Usuario extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'usua_activo' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Rol, $this>
     */
    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'usua_id_rol');
    }

    /**
     * @return BelongsToMany<Coordinacion, $this>
     */
    public function coordinaciones(): BelongsToMany
    {
        return $this->belongsToMany(Coordinacion::class, 'usuario_coordinacion', 'usco_id_usuario', 'usco_id_coordinacion');
    }

    /**
     * @return HasMany<Bitacora, $this>
     */
    public function bitacora(): HasMany
    {
        return $this->hasMany(Bitacora::class, 'bita_id_usuario');
    }

    /**
     * Todas las personas del profesor: él mismo y sus contactos de emergencia.
     *
     * @return HasMany<PersonaContacto, $this>
     */
    public function personas(): HasMany
    {
        return $this->hasMany(PersonaContacto::class, 'peco_id_usuario');
    }

    /**
     * El propio profesor (pantalla "Datos personales").
     *
     * @return HasOne<PersonaContacto, $this>
     */
    public function datosPersonales(): HasOne
    {
        return $this->hasOne(PersonaContacto::class, 'peco_id_usuario')->whereNull('peco_id_parentesco');
    }

    /**
     * Sus contactos de emergencia (pantalla "Datos de emergencia").
     *
     * @return HasMany<PersonaContacto, $this>
     */
    public function contactosEmergencia(): HasMany
    {
        return $this->hasMany(PersonaContacto::class, 'peco_id_usuario')->whereNotNull('peco_id_parentesco');
    }
}
