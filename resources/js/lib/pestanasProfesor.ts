import type { Pestana } from '@/types/sacpa';

/**
 * Pestañas del encabezado en las pantallas Datos personales / Datos de emergencia.
 *
 * @param rutaProfesor '' para el profesor; '/admin/profesores/{id}' para el administrador.
 */
export function pestanasProfesor(
    activa: 'personales' | 'emergencia',
    rutaProfesor = '',
): Pestana[] {
    return [
        {
            titulo: 'Datos personales',
            href: `${rutaProfesor}/datos-personales`,
            activa: activa === 'personales',
        },
        {
            titulo: 'Datos de emergencia',
            href: `${rutaProfesor}/datos-de-emergencia`,
            activa: activa === 'emergencia',
        },
    ];
}
