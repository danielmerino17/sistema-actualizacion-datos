import type { Pestana } from '@/types/sacpa';

/**
 * Pestañas del encabezado en las pantallas del profesor.
 */
export function pestanasProfesor(
    activa: 'personales' | 'emergencia',
): Pestana[] {
    return [
        {
            titulo: 'Datos personales',
            href: '/datos-personales',
            activa: activa === 'personales',
        },
        {
            titulo: 'Datos de emergencia',
            href: '/datos-de-emergencia',
            activa: activa === 'emergencia',
        },
    ];
}
