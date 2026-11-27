<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Bitacora;
use App\Models\PersonaContacto;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function store(LoginRequest $request): RedirectResponse
    {
        $numeroTrabajador = (string) $request->input('numero_trabajador');
        $rfc = (string) $request->input('rfc');

        $correoSubquery = DB::connection('pgsql_fca')
            ->table('comunicacion as co')
            ->select('co.comu_numero')
            ->whereColumn('co.comu_id_persona', 'pe.pers_id_persona')
            ->where('co.comu_numero', 'LIKE', '%@%')
            ->orderByRaw('co.comu_es_principal DESC NULLS LAST')
            ->limit(1);

        // 1. Validar número de trabajador y RFC en bd_fca
        $registro = DB::connection('pgsql_fca')
            ->table('persona as pe')
            ->join('profesor as pr', 'pr.prof_id_persona', '=', 'pe.pers_id_persona')
            ->whereRaw('TRIM(pr.prof_num_unam) = ?', [$numeroTrabajador])
            ->whereRaw('UPPER(TRIM(pe.pers_rfc)) = ?', [$rfc])
            ->select([
                'pe.pers_nombre',
                'pe.pers_apaterno',
                'pe.pers_amaterno',
            ])
            ->selectSub($correoSubquery, 'correo')
            ->first();

        if ($registro === null) {
            throw ValidationException::withMessages([
                'numero_trabajador' => 'El número de trabajador o el RFC no coinciden con nuestros registros.',
            ]);
        }

        // 2. Buscar si ya existe en tu BD local o crearlo (evita duplicados)
        $personaExistente = PersonaContacto::titular()
            ->delProfesor($numeroTrabajador)
            ->with('usuario')
            ->first();

        if ($personaExistente?->usuario !== null) {
            $usuario = $personaExistente->usuario;
        } else {
            $correo = filled($registro->correo)
                ? mb_strtolower(trim((string) $registro->correo))
                : "{$numeroTrabajador}@fca.unam.mx";

            $rolProfesorId = (int) Rol::where('rol_nombre_rol', Rol::PROFESOR)->value('rol_id_rol');

            $usuario = DB::transaction(function () use ($rolProfesorId, $correo, $numeroTrabajador, $registro): Usuario {
                // firstOrCreate (no updateOrCreate): si ya existe un usuario con ese
                // correo, no se le cambia el rol ni se reactiva si estaba desactivado.
                $usuario = Usuario::firstOrCreate(
                    ['usua_correo' => $correo],
                    [
                        'usua_id_rol' => $rolProfesorId,
                        'usua_nombre' => trim((string) $registro->pers_nombre),
                        'usua_apellido_paterno' => trim((string) $registro->pers_apaterno),
                        'usua_apellido_materno' => filled($registro->pers_amaterno)
                            ? trim((string) $registro->pers_amaterno)
                            : null,
                        'usua_activo' => true,
                    ],
                );

                $usuario->datosPersonales()->firstOrCreate(
                    ['peco_id_parentesco' => null],
                    [
                        'peco_numero_trabajador' => $numeroTrabajador,
                        'peco_nombre' => $usuario->usua_nombre,
                        'peco_apellido_paterno' => $usuario->usua_apellido_paterno,
                        'peco_apellido_materno' => $usuario->usua_apellido_materno,
                    ],
                );

                return $usuario;
            });
        }

        // Un usuario desactivado no puede entrar (antes entraba y veía un error 403).
        if (! $usuario->usua_activo) {
            throw ValidationException::withMessages([
                'numero_trabajador' => 'Tu usuario está desactivado. Comunícate con la coordinación.',
            ]);
        }

        // 3. Iniciar sesión y registrar en bitácora
        Auth::login($usuario);
        $request->session()->regenerate();
        Bitacora::registrar($usuario, 'inicio_sesion');

        return to_route('datos-personales.index');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }
}
