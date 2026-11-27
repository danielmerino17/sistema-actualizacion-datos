# SACPA — Sistema de Actualización de Datos de Profesores

Sistema para que los profesores de la FCA actualicen sus **datos personales** (correos y teléfonos) y sus **contactos de emergencia**, y para que la coordinación los consulte y los descargue en Excel.

- **Profesor:** entra con su número de trabajador y RFC, y actualiza sus datos personales y de emergencia.
- **Administrador:** busca profesores por nombre, número de trabajador o RFC; consulta, corrige y descarga sus datos en Excel.

**Tecnología:** Laravel 13 (PHP 8.4) · Inertia v3 · Vue 3 + TypeScript · Tailwind 4 · PostgreSQL 18.

---

## Bases de datos

| Conexión    | Base                    | Qué tiene                                                                                                                        |
| ----------- | ----------------------- | -------------------------------------------------------------------------------------------------------------------------------- |
| `pgsql`     | `sacpa`                 | Tablas del sistema (usuarios, contactos, bitácora). Se crea con las migraciones.                                                 |
| `pgsql_fca` | `bd_fca` (ej. `sie_bd`) | Base institucional de la FCA. **Solo lectura**: el login y la búsqueda por RFC consultan `persona`, `profesor` y `comunicacion`. |

> Los datos de la base institucional son confidenciales: **nunca** se suben al repositorio ni se comparten respaldos.

---

## Instalación en tu máquina

Requisitos: PHP 8.4 (con `pdo_pgsql`, `pgsql`, `intl`, `zip`), Composer, Node.js 22+ y PostgreSQL 18.

```powershell
git clone https://github.com/danielmerino17/sistema-actualizacion-datos.git
cd sistema-actualizacion-datos
composer install
npm install
copy .env.example .env
php artisan key:generate
```

1. En PostgreSQL (pgAdmin) crea la base **`sacpa`**.
2. En `.env` llena `DB_PASSWORD` y el bloque `DB_FCA_*` (ver abajo).
3. Crea las tablas y los catálogos:

    ```powershell
    php artisan migrate --seed
    ```

4. Levanta el sistema y entra a http://localhost:8000:

    ```powershell
    composer run dev
    ```

### Si tienes la base institucional (datos reales)

En `.env`, apunta `DB_FCA_*` a tu base real:

```
DB_FCA_HOST=127.0.0.1
DB_FCA_PORT=5432
DB_FCA_DATABASE=sie_bd
DB_FCA_USERNAME=postgres
DB_FCA_PASSWORD=tu_contraseña
```

Entra con tu **número de trabajador y RFC**.

### Si NO tienes la base institucional (accesos de prueba)

En `.env` agrega `SACPA_ACCESOS_PRUEBA=true` y vuelve a sembrar los datos inventados:

```powershell
php artisan migrate:fresh --seed
```

- Profesor de prueba: http://localhost:8000/dev/entrar-como-profesor
- Administrador de prueba: http://localhost:8000/dev/entrar-como-admin

> Solo funcionan con `APP_ENV=local` y `SACPA_ACCESOS_PRUEBA=true`. **Nunca** se activan en el servidor.

---

## Administradores

No hay pantalla para dar de alta administradores; se hace con un comando. La persona debe existir en la base institucional y haber **iniciado sesión al menos una vez**.

```powershell
php artisan sacpa:admin 1234567            # lo hace Administrador
php artisan sacpa:admin 1234567 --quitar   # lo regresa a Profesor
```

Después debe **cerrar sesión y volver a entrar**: el administrador va directo a la búsqueda de profesores.

> La búsqueda solo encuentra profesores que ya entraron al sistema al menos una vez.

## Bitácora

Se registra quién hizo cada acción, a qué profesor afectó y qué registro tocó (altas, cambios, bajas, consultas y descargas del administrador, inicios de sesión e **intentos fallidos con su IP**; nunca el RFC).

```powershell
php artisan sacpa:bitacora                          # últimos 50 registros
php artisan sacpa:bitacora --profesor=1234567       # los de un profesor
php artisan sacpa:bitacora --accion=login_fallido   # solo intentos fallidos
```

---

## Pruebas y revisiones

```powershell
php artisan test          # pruebas automáticas (usan bases en memoria, no tocan tus datos)
vendor/bin/pint --test    # estilo PHP
vendor/bin/phpstan        # tipos PHP
npm run check             # lint y formato del frontend
npm run types:check       # tipos TypeScript
composer audit            # librerías PHP con vulnerabilidades
npm audit                 # librerías JS con vulnerabilidades
```

El CI de GitHub corre todo esto en cada Pull Request, con PostgreSQL 18.

---

## Antes de publicar en el servidor

- `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`.
- **No** definir `SACPA_ACCESOS_PRUEBA` (o dejarlo en `false`).
- HTTPS y `SESSION_SECURE_COOKIE=true`.
- Usuario de **solo lectura** para `bd_fca`.
- `composer install --no-dev --optimize-autoloader`, `npm run build`, `php artisan migrate --force` y `php artisan optimize`.
