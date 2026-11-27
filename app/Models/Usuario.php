<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Usuario del sistema: profesores y administradores (según su rol).
 *
 * Es el modelo con el que se inicia sesión (config/auth.php). No guarda
 * contraseña: la validación de credenciales se hace contra bd_fca en el
 * login (Parte 5) y después se llama a Auth::login($usuario).
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
class Usuario extends Authenticatable
{
    /**
     * Indica si el usuario tiene alguno de los roles dados.
     */
    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->rol?->rol_nombre_rol, $roles, true);
    }

    /**
     * Pantalla a la que va el usuario al iniciar sesión, según su rol.
     */
    public function rutaInicio(): string
    {
        return $this->tieneRol(Rol::ADMINISTRADOR)
            ? route('admin.buscar')
            : route('datos-personales.index');
    }

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
