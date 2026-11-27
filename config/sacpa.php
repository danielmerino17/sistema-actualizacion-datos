<?php

/*
|--------------------------------------------------------------------------
| Configuración propia de SACPA
|--------------------------------------------------------------------------
*/

return [

    /*
    | Accesos de prueba (/dev/entrar-como-profesor, /dev/entrar-como-admin),
    | la muestra de componentes (/demo/componentes) y los usuarios inventados
    | de DesarrolloSeeder.
    |
    | Solo funcionan si APP_ENV=local Y SACPA_ACCESOS_PRUEBA=true.
    | NUNCA activarlo en el servidor: permite entrar sin datos.
    */
    'accesos_prueba' => (bool) env('SACPA_ACCESOS_PRUEBA', false),

];
