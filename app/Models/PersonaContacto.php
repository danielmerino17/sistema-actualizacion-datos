<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Persona que pertenece a un usuario profesor (peco_id_usuario).
 *  - Sin parentesco: es el propio profesor (pantalla "Datos personales").
 *  - Con parentesco: es un contacto de emergencia (pantalla "Datos de emergencia").
 *
 * @property int $peco_id_persona_contacto
 * @property int $peco_id_usuario
 * @property int|null $peco_id_parentesco
 * @property string $peco_numero_trabajador
 * @property string $peco_nombre
 * @property string $peco_apellido_paterno
 * @property string|null $peco_apellido_materno
 * @property Carbon|null $peco_fecha_ultima_actualizacion
 */
#[Table(name: 'persona_contacto', key: 'peco_id_persona_contacto', timestamps: false)]
#[Fillable([
    'peco_id_usuario',
    'peco_id_parentesco',
    'peco_numero_trabajador',
    'peco_nombre',
    'peco_apellido_paterno',
    'peco_apellido_materno',
])]
class PersonaContacto extends Model
{
    /**
     * Cada vez que se guarda, se actualiza la fecha de última actualización.
     */
    protected static function booted(): void
    {
        static::saving(function (PersonaContacto $persona): void {
            $persona->peco_fecha_ultima_actualizacion = Carbon::today();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'peco_fecha_ultima_actualizacion' => 'date',
        ];
    }

    /**
     * Personas registradas para un profesor, buscando por su número de
     * trabajador (útil para la búsqueda del administrador).
     *
     * @param  Builder<PersonaContacto>  $query
     */
    #[Scope]
    protected function delProfesor(Builder $query, string $numeroTrabajador): void
    {
        $query->where('peco_numero_trabajador', $numeroTrabajador);
    }

    /**
     * El propio profesor (sin parentesco).
     *
     * @param  Builder<PersonaContacto>  $query
     */
    #[Scope]
    protected function titular(Builder $query): void
    {
        $query->whereNull('peco_id_parentesco');
    }

    /**
     * Sus contactos de emergencia (con parentesco).
     *
     * @param  Builder<PersonaContacto>  $query
     */
    #[Scope]
    protected function deEmergencia(Builder $query): void
    {
        $query->whereNotNull('peco_id_parentesco');
    }

    /**
     * @return HasMany<Contacto, $this>
     */
    public function contactos(): HasMany
    {
        return $this->hasMany(Contacto::class, 'cont_id_persona_contacto');
    }

    /**
     * @return BelongsTo<Parentesco, $this>
     */
    public function parentesco(): BelongsTo
    {
        return $this->belongsTo(Parentesco::class, 'peco_id_parentesco');
    }

    /**
     * El usuario profesor al que pertenece esta persona.
     *
     * @return BelongsTo<Usuario, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'peco_id_usuario');
    }
}
