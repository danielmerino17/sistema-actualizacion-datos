/**
 * Tipos compartidos por los componentes y pantallas de SACPA.
 */

export type NombreIcono =
    | 'agregar'
    | 'editar'
    | 'eliminar'
    | 'regresar'
    | 'aviso'
    | 'descargar'
    | 'exito'
    | 'error';

/** Pestaña del encabezado ("Datos personales", "Datos de emergencia"). */
export type Pestana = {
    titulo: string;
    href: string;
    activa: boolean;
};

/** Datos del profesor que se muestran debajo del encabezado. */
export type DatosProfesor = {
    nombre: string;
    apellidoPaterno: string;
    apellidoMaterno: string | null;
    numeroTrabajador: string;
};

/** Columna de una tabla: `clave` es el campo de la fila que se muestra. */
export type ColumnaTabla = {
    clave: string;
    titulo: string;
};

/** Opción de un campo de selección. */
export type OpcionSeleccion = {
    valor: string | number;
    texto: string;
};

/** Mensajes flash que manda Laravel con Inertia::flash(). */
export type MensajesFlash = {
    exito?: string;
    error?: string;
};
