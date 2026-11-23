<script setup lang="ts">
/**
 * Página de muestra de la Parte 2 (solo existe en APP_ENV=local).
 * Usa datos inventados para revisar los componentes contra el Figma.
 * Se puede borrar cuando las pantallas reales estén listas.
 */
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AvisoActualizacion from '@/components/sacpa/AvisoActualizacion.vue';
import CampoSeleccion from '@/components/sacpa/CampoSeleccion.vue';
import CampoTexto from '@/components/sacpa/CampoTexto.vue';
import DialogoConfirmacion from '@/components/sacpa/DialogoConfirmacion.vue';
import PanelFormulario from '@/components/sacpa/PanelFormulario.vue';
import SacpaBoton from '@/components/sacpa/SacpaBoton.vue';
import SacpaIcono from '@/components/sacpa/SacpaIcono.vue';
import TablaSacpa from '@/components/sacpa/TablaSacpa.vue';
import SistemaLayout from '@/layouts/SistemaLayout.vue';
import type {
    ColumnaTabla,
    DatosProfesor,
    OpcionSeleccion,
    Pestana,
} from '@/types/sacpa';

const page = usePage();
const seccion = computed(() =>
    page.url.includes('seccion=emergencia') ? 'emergencia' : 'personales',
);

const pestanas = computed<Pestana[]>(() => [
    {
        titulo: 'Datos personales',
        href: '/demo/componentes',
        activa: seccion.value === 'personales',
    },
    {
        titulo: 'Datos de emergencia',
        href: '/demo/componentes?seccion=emergencia',
        activa: seccion.value === 'emergencia',
    },
]);

const profesor: DatosProfesor = {
    nombre: 'José Martín',
    apellidoPaterno: 'Jiménez',
    apellidoMaterno: 'Hernández',
    numeroTrabajador: '2903872',
};

const columnasPersonales: ColumnaTabla[] = [
    { clave: 'medio', titulo: 'Medio' },
    { clave: 'descripcion', titulo: 'Datos de contacto' },
    { clave: 'tipoUso', titulo: 'Tipo de uso' },
    { clave: 'fecha', titulo: 'Última fecha de actualización' },
];

const datosPersonales = [
    {
        id: 1,
        medio: 'Correo',
        descripcion: 'jmjh10@gmail.com',
        tipoUso: 'Principal',
        fecha: '20 / 01 / 2025',
    },
    {
        id: 2,
        medio: 'Teléfono (celular)',
        descripcion: '55 1928 0404',
        tipoUso: 'Principal',
        fecha: '01 / 09 / 2025',
    },
    {
        id: 3,
        medio: 'Teléfono (fijo)',
        descripcion: '56 1010 2629',
        tipoUso: 'Secundario',
        fecha: '17 / 09 / 2026',
    },
];

const columnasEmergencia: ColumnaTabla[] = [
    { clave: 'numero', titulo: 'No. Contacto' },
    { clave: 'nombre', titulo: 'Nombre(s)' },
    { clave: 'apellidoPaterno', titulo: 'Apellido paterno' },
    { clave: 'apellidoMaterno', titulo: 'Apellido materno' },
    { clave: 'parentesco', titulo: 'Parentesco' },
    { clave: 'celular', titulo: 'Teléfono (celular)' },
    { clave: 'alterno', titulo: 'Teléfono (alterno)' },
    { clave: 'fecha', titulo: 'Última fecha de actualización' },
];

const contactosEmergencia = [
    {
        id: 1,
        numero: 1,
        nombre: 'Ernesto José',
        apellidoPaterno: 'López',
        apellidoMaterno: 'Mejía',
        parentesco: 'Primo',
        celular: '55 5299 3963',
        alterno: '55 5019 3773',
        fecha: '20/01/2025',
    },
    {
        id: 2,
        numero: 2,
        nombre: 'Antonio',
        apellidoPaterno: 'Jiménez',
        apellidoMaterno: 'Rosas',
        parentesco: 'Tío',
        celular: '55 1937 2037',
        alterno: '55 1937 2938',
        fecha: '01/09/2025',
    },
    {
        id: 3,
        numero: 3,
        nombre: 'Rodolfo',
        apellidoPaterno: 'Peña',
        apellidoMaterno: 'Torres',
        parentesco: 'Hermano',
        celular: '55 1937 2037',
        alterno: '55 0292 7729',
        fecha: '17/09/2026',
    },
];

const tiposMedio: OpcionSeleccion[] = [
    { valor: 1, texto: 'Correo' },
    { valor: 2, texto: 'Celular' },
    { valor: 3, texto: 'Casa' },
];

const tiposUso: OpcionSeleccion[] = [
    { valor: 1, texto: 'Principal' },
    { valor: 0, texto: 'Secundario' },
];

const mostrandoFormulario = ref(false);
const formulario = reactive<{
    descripcion: string;
    tipoMedio: number | string | null;
    tipoUso: number | string | null;
}>({ descripcion: '', tipoMedio: null, tipoUso: null });
const erroresDemo = reactive<{ descripcion?: string }>({});
const registroAEliminar = ref<number | null>(null);

/** Simula una respuesta de Laravel con Inertia::flash(). */
function enviarFlashDemo(tipo: 'exito' | 'error', mensaje: string): void {
    router.post(
        '/demo/flash',
        { tipo, mensaje },
        { preserveScroll: true, preserveState: true },
    );
}

function guardarDemo(): void {
    if (formulario.descripcion.trim() === '') {
        erroresDemo.descripcion =
            'Este campo es obligatorio (error de ejemplo).';

        return;
    }

    erroresDemo.descripcion = undefined;
    mostrandoFormulario.value = false;
    enviarFlashDemo('exito', 'Datos creados de manera exitosa');
}

function eliminarDemo(): void {
    registroAEliminar.value = null;
    enviarFlashDemo('exito', 'Registro eliminado de manera exitosa');
}
</script>

<template>
    <Head title="Muestra de componentes" />

    <SistemaLayout :pestanas="pestanas" :profesor="profesor">
        <p class="text-xs text-black">
            Estimado docente: usted puede modificar su información cuando lo
            considere necesario; con el fin de mantener los registros
            actualizados, el sistema requiere una revisión de datos con una
            periodicidad máxima de seis meses.<br />
            Los datos registrados actualmente son los siguientes:
        </p>

        <div class="my-3 flex flex-wrap items-center justify-end gap-3">
            <AvisoActualizacion ultima-actualizacion="01 / 01 / 2026" />
            <SacpaBoton
                v-if="!mostrandoFormulario"
                icono="agregar"
                @click="mostrandoFormulario = true"
            >
                Agregar
            </SacpaBoton>
            <SacpaBoton
                variante="exito"
                icono="descargar"
                @click="enviarFlashDemo('error', 'Ejemplo de mensaje de error')"
            >
                Descargar Excel
            </SacpaBoton>
        </div>

        <div class="mx-auto w-full max-w-6xl">
            <PanelFormulario
                v-if="mostrandoFormulario"
                titulo="Registro de nuevos datos personales:"
                @regresar="mostrandoFormulario = false"
                @enviar="guardarDemo"
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <CampoTexto
                        id="descripcion"
                        v-model="formulario.descripcion"
                        class="sm:col-span-2 sm:mx-auto sm:w-2/3"
                        etiqueta="Medio de contacto:"
                        :maximo="50"
                        :error="erroresDemo.descripcion"
                    />
                    <CampoSeleccion
                        id="tipo-medio"
                        v-model="formulario.tipoMedio"
                        etiqueta="Tipo de medio:"
                        :opciones="tiposMedio"
                    />
                    <CampoSeleccion
                        id="tipo-uso"
                        v-model="formulario.tipoUso"
                        etiqueta="Tipo de uso:"
                        :opciones="tiposUso"
                    />
                </div>
            </PanelFormulario>

            <TablaSacpa
                v-else-if="seccion === 'personales'"
                :columnas="columnasPersonales"
                :filas="datosPersonales"
                clave-fila="id"
            >
                <template #celda-medio="{ fila }">
                    <strong>{{ fila.medio }}</strong>
                </template>
                <template #acciones="{ fila }">
                    <button
                        type="button"
                        class="text-gray-800 hover:text-sacpa-azul"
                        :aria-label="`Editar ${fila.medio}`"
                        @click="mostrandoFormulario = true"
                    >
                        <SacpaIcono nombre="editar" clase="size-4" />
                    </button>
                    <button
                        type="button"
                        class="text-gray-800 hover:text-red-600"
                        :aria-label="`Eliminar ${fila.medio}`"
                        @click="registroAEliminar = fila.id"
                    >
                        <SacpaIcono nombre="eliminar" clase="size-4" />
                    </button>
                </template>
            </TablaSacpa>

            <TablaSacpa
                v-else
                :columnas="columnasEmergencia"
                :filas="contactosEmergencia"
                clave-fila="id"
            >
                <template #acciones="{ fila }">
                    <button
                        type="button"
                        class="text-gray-800 hover:text-sacpa-azul"
                        :aria-label="`Editar a ${fila.nombre}`"
                    >
                        <SacpaIcono nombre="editar" clase="size-4" />
                    </button>
                    <button
                        type="button"
                        class="text-gray-800 hover:text-red-600"
                        :aria-label="`Eliminar a ${fila.nombre}`"
                        @click="registroAEliminar = fila.id"
                    >
                        <SacpaIcono nombre="eliminar" clase="size-4" />
                    </button>
                </template>
            </TablaSacpa>

            <div class="mt-6">
                <h2 class="mb-2 text-sm font-bold text-gray-700">
                    Tabla sin registros:
                </h2>
                <TablaSacpa
                    :columnas="columnasPersonales"
                    :filas="[]"
                    clave-fila="id"
                />
            </div>
        </div>

        <DialogoConfirmacion
            :abierto="registroAEliminar !== null"
            @cancelar="registroAEliminar = null"
            @aceptar="eliminarDemo"
        >
            ¿Está seguro de que desea <strong>eliminar</strong> el registro
            seleccionado?
        </DialogoConfirmacion>
    </SistemaLayout>
</template>
