<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PersonaContacto;
use App\Models\Rol;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Pantalla de búsqueda del administrador (Figma, sección ADMINISTRADOR).
 *
 * Busca entre los profesores registrados en sacpa (los que ya iniciaron
 * sesión al menos una vez) por nombre, número de trabajador o RFC.
 *  - 1 resultado  → entra directo a sus datos personales (como en el Figma).
 *  - Varios       → muestra la lista para elegir.
 *  - Ninguno      → mensaje de error.
 */
class BuscarProfesorController extends Controller
{
    private const MAXIMO_RESULTADOS = 50;

    public function __invoke(Request $request): Response|RedirectResponse
    {
        $busqueda = [
            'nombre' => trim((string) $request->query('nombre', '')),
            'numero_trabajador' => trim((string) $request->query('numero_trabajador', '')),
            'rfc' => mb_strtoupper(trim((string) $request->query('rfc', ''))),
        ];

        // Primera vez que se abre la pantalla: solo el formulario.
        if (! $request->hasAny(array_keys($busqueda))) {
            return $this->pantalla($busqueda);
        }

        $this->validar($busqueda);

        $resultados = $this->buscar($busqueda);

        if ($resultados->count() === 1) {
            return to_route('admin.profesores.datos-personales.index', [
                'profesor' => $resultados->first()->peco_id_usuario,
            ]);
        }

        if ($resultados->isEmpty()) {
            throw ValidationException::withMessages([
                'busqueda' => 'No se encontró ningún profesor con esos datos.',
            ]);
        }

        return $this->pantalla($busqueda, $resultados->map(fn (PersonaContacto $persona) => [
            'id' => $persona->peco_id_usuario,
            'numeroTrabajador' => $persona->peco_numero_trabajador,
            'nombre' => trim("{$persona->peco_nombre} {$persona->peco_apellido_paterno} {$persona->peco_apellido_materno}"),
        ])->values()->all());
    }

    /**
     * @param  array{nombre: string, numero_trabajador: string, rfc: string}  $busqueda
     * @param  array<int, array{id: int, numeroTrabajador: string, nombre: string}>|null  $resultados
     */
    private function pantalla(array $busqueda, ?array $resultados = null): Response
    {
        return Inertia::render('Admin/Buscar', [
            'busqueda' => $busqueda,
            'resultados' => $resultados,
        ]);
    }

    /**
     * @param  array{nombre: string, numero_trabajador: string, rfc: string}  $busqueda
     */
    private function validar(array $busqueda): void
    {
        if ($busqueda['nombre'] === '' && $busqueda['numero_trabajador'] === '' && $busqueda['rfc'] === '') {
            throw ValidationException::withMessages([
                'busqueda' => 'Ingresa al menos uno de los datos para consultar.',
            ]);
        }

        $errores = [];

        if (mb_strlen($busqueda['nombre']) > 100) {
            $errores['nombre'] = 'El nombre no puede tener más de 100 caracteres.';
        }

        if (mb_strlen($busqueda['numero_trabajador']) > 10) {
            $errores['numero_trabajador'] = 'El número de trabajador no puede tener más de 10 caracteres.';
        }

        if (mb_strlen($busqueda['rfc']) > 13) {
            $errores['rfc'] = 'El RFC no puede tener más de 13 caracteres.';
        }

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }
    }

    /**
     * Si se llenan varios campos, el profesor debe coincidir con todos.
     *
     * @param  array{nombre: string, numero_trabajador: string, rfc: string}  $busqueda
     * @return Collection<int, PersonaContacto>
     */
    private function buscar(array $busqueda)
    {
        $consulta = PersonaContacto::titular()
            ->whereHas('usuario.rol', fn (Builder $rol) => $rol->where('rol_nombre_rol', Rol::PROFESOR))
            ->orderBy('peco_apellido_paterno')
            ->orderBy('peco_nombre')
            ->limit(self::MAXIMO_RESULTADOS);

        if ($busqueda['numero_trabajador'] !== '') {
            $consulta->where('peco_numero_trabajador', $busqueda['numero_trabajador']);
        }

        if ($busqueda['nombre'] !== '') {
            // Nombre completo, sin importar mayúsculas: "torres arroyo", "Luis Miguel", etc.
            // "%" y "_" se buscan como texto (no como comodines de LIKE); "!" es el escape.
            $texto = mb_strtolower((string) preg_replace('/\s+/', ' ', $busqueda['nombre']));
            $texto = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $texto);

            $consulta->whereRaw(
                "LOWER(peco_nombre || ' ' || peco_apellido_paterno || ' ' || COALESCE(peco_apellido_materno, '')) LIKE ? ESCAPE '!'",
                ['%'.$texto.'%'],
            );
        }

        if ($busqueda['rfc'] !== '') {
            $consulta->whereIn('peco_numero_trabajador', $this->numerosPorRfc($busqueda['rfc']));
        }

        return $consulta->get();
    }

    /**
     * sacpa no guarda el RFC: se consulta en bd_fca para obtener el número de trabajador.
     *
     * @return array<int, string>
     */
    private function numerosPorRfc(string $rfc): array
    {
        try {
            return DB::connection('pgsql_fca')
                ->table('persona as pe')
                ->join('profesor as pr', 'pr.prof_id_persona', '=', 'pe.pers_id_persona')
                ->whereRaw('UPPER(TRIM(pe.pers_rfc)) = ?', [$rfc])
                ->pluck('pr.prof_num_unam')
                ->map(fn ($numero) => trim((string) $numero))
                ->all();
        } catch (Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'rfc' => 'No fue posible consultar el RFC en este momento. Intenta con el nombre o el número de trabajador.',
            ]);
        }
    }
}
