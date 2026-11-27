<script setup lang="ts">
/**
 * Pantalla "Datos personales" (Figma, flujos 1 y 2 de profesor y sección de administrador).
 * Lista los correos y teléfonos del profesor y permite agregarlos, editarlos y eliminarlos.
 * La usan el profesor (sus datos) y el administrador (rutaProfesor = '/admin/profesores/{id}').
 * Los datos llegan de DatosPersonalesController@index.
 */
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AvisoActualizacion from '@/components/sacpa/AvisoActualizacion.vue';
import CampoSeleccion from '@/components/sacpa/CampoSeleccion.vue';
import CampoTexto from '@/components/sacpa/CampoTexto.vue';
import DialogoConfirmacion from '@/components/sacpa/DialogoConfirmacion.vue';
import PanelFormulario from '@/components/sacpa/PanelFormulario.vue';
import SacpaBoton from '@/components/sacpa/SacpaBoton.vue';
import SacpaIcono from '@/components/sacpa/SacpaIcono.vue';
import TablaSacpa from '@/components/sacpa/TablaSacpa.vue';
import SistemaLayout from '@/layouts/SistemaLayout.vue';
import { pestanasProfesor } from '@/lib/pestanasProfesor';
import type {
    ColumnaTabla,
    DatosProfesor,
    OpcionSeleccion,
} from '@/types/sacpa';

type ContactoPersonal = {
    id: number;
    tipoId: number;
    medio: string;
    descripcion: string;
    esPrincipal: boolean;
    fecha: string;
};

const props = defineProps<{
    profesor: DatosProfesor;
    contactos: ContactoPersonal[];
    tiposContacto: OpcionSeleccion[];
    ultimaActualizacion: string | null;
    requiereActualizacion: boolean;
    rutaProfesor: string;
    esAdministrador: boolean;
}>();

const pestanas = pestanasProfesor('personales', props.rutaProfesor);
const rutaBase = `${props.rutaProfesor}/datos-personales`;

function descargarExcel(): void {
    window.location.href = `${rutaBase}/excel`;
}

const columnas: ColumnaTabla[] = [
    { clave: 'medio', titulo: 'Medio' },
    { clave: 'descripcion', titulo: 'Datos de contacto' },
    { clave: 'tipoUso', titulo: 'Tipo de uso' },
    { clave: 'fecha', titulo: 'Última fecha de actualización' },
];

const filas = computed(() =>
    props.contactos.map((contacto) => ({
        ...contacto,
        tipoUso: contacto.esPrincipal ? 'Principal' : 'Secundario',
    })),
);

const opcionesUso: OpcionSeleccion[] = [
    { valor: 1, texto: 'Principal' },
    { valor: 0, texto: 'Secundario' },
];

// ----- Formulario de alta / edición -----
const mostrandoFormulario = ref(false);
const editando = ref<ContactoPersonal | null>(null);

const form = useForm<{
    tipo_contacto: number | string | null;
    descripcion: string;
    es_principal: number | string | null;
}>({
    tipo_contacto: null,
    descripcion: '',
    es_principal: null,
});

const tituloFormulario = computed(() =>
    editando.value
        ? 'Actualización de datos personales:'
        : 'Registro de nuevos datos personales:',
);

function abrirAlta(): void {
    editando.value = null;
    form.reset();
    form.clearErrors();
    mostrandoFormulario.value = true;
}

function abrirEdicion(contacto: ContactoPersonal): void {
    editando.value = contacto;
    form.clearErrors();
    form.tipo_contacto = contacto.tipoId;
    form.descripcion = contacto.descripcion;
    form.es_principal = contacto.esPrincipal ? 1 : 0;
    mostrandoFormulario.value = true;
}

function cerrarFormulario(): void {
    mostrandoFormulario.value = false;
    editando.value = null;
    form.reset();
    form.clearErrors();
}

function enviar(): void {
    const opciones = { preserveScroll: true, onSuccess: cerrarFormulario };

    if (editando.value) {
        form.put(`${rutaBase}/${editando.value.id}`, opciones);
    } else {
        form.post(rutaBase, opciones);
    }
}

// ----- Eliminar -----
const porEliminar = ref<ContactoPersonal | null>(null);
const eliminando = ref(false);

function eliminar(): void {
    if (!porEliminar.value) {
        return;
    }

    router.delete(`${rutaBase}/${porEliminar.value.id}`, {
        preserveScroll: true,
        onStart: () => (eliminando.value = true),
        onFinish: () => {
            eliminando.value = false;
            porEliminar.value = null;
        },
    });
}
</script>

<template>
    <Head title="Datos personales" />

    <SistemaLayout :pestanas="pestanas" :profesor="profesor">
        <Link
            v-if="esAdministrador"
            href="/admin"
            class="mb-2 self-start text-sm font-semibold text-sacpa-azul hover:underline"
        >
            ← Buscar otro profesor
        </Link>

        <p class="text-xs text-black">
            Estimado docente: usted puede modificar su información cuando lo
            considere necesario; con el fin de mantener los registros
            actualizados, el sistema requiere una revisión de datos con una
            periodicidad máxima de seis meses.<br />
            Los datos registrados actualmente son los siguientes:
        </p>

        <div class="my-3 flex flex-wrap items-center justify-end gap-3">
            <AvisoActualizacion
                v-if="requiereActualizacion"
                :ultima-actualizacion="ultimaActualizacion"
            />
            <SacpaBoton
                v-if="!mostrandoFormulario"
                icono="agregar"
                @click="abrirAlta"
            >
                Agregar
            </SacpaBoton>
            <SacpaBoton
                v-if="esAdministrador"
                variante="exito"
                icono="descargar"
                @click="descargarExcel"
            >
                Descargar Excel
            </SacpaBoton>
        </div>

        <div class="mx-auto w-full max-w-5xl">
            <PanelFormulario
                v-if="mostrandoFormulario"
                :titulo="tituloFormulario"
                :procesando="form.processing"
                @regresar="cerrarFormulario"
                @enviar="enviar"
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <CampoTexto
                        id="descripcion"
                        v-model="form.descripcion"
                        class="sm:col-span-2 sm:mx-auto sm:w-2/3"
                        etiqueta="Medio de contacto:"
                        :maximo="50"
                        :error="form.errors.descripcion"
                    />
                    <CampoSeleccion
                        id="tipo-contacto"
                        v-model="form.tipo_contacto"
                        etiqueta="Tipo de medio:"
                        :opciones="tiposContacto"
                        :error="form.errors.tipo_contacto"
                    />
                    <CampoSeleccion
                        id="es-principal"
                        v-model="form.es_principal"
                        etiqueta="Tipo de uso:"
                        :opciones="opcionesUso"
                        :error="form.errors.es_principal"
                    />
                </div>
            </PanelFormulario>

            <TablaSacpa
                v-else
                :columnas="columnas"
                :filas="filas"
                clave-fila="id"
                :texto-vacio="
                    esAdministrador
                        ? 'Este profesor todavía no tiene datos de contacto registrados.'
                        : 'Todavía no has registrado datos de contacto. Usa el botón Agregar.'
                "
            >
                <template #celda-medio="{ fila }">
                    <strong>{{ fila.medio }}</strong>
                </template>
                <template #acciones="{ fila }">
                    <button
                        type="button"
                        class="text-gray-800 hover:text-sacpa-azul"
                        :aria-label="`Editar ${fila.medio} ${fila.descripcion}`"
                        @click="abrirEdicion(fila)"
                    >
                        <SacpaIcono nombre="editar" clase="size-4" />
                    </button>
                    <button
                        type="button"
                        class="text-gray-800 hover:text-red-600"
                        :aria-label="`Eliminar ${fila.medio} ${fila.descripcion}`"
                        @click="porEliminar = fila"
                    >
                        <SacpaIcono nombre="eliminar" clase="size-4" />
                    </button>
                </template>
            </TablaSacpa>
        </div>

        <DialogoConfirmacion
            :abierto="porEliminar !== null"
            :procesando="eliminando"
            @cancelar="porEliminar = null"
            @aceptar="eliminar"
        >
            ¿Está seguro de que desea <strong>eliminar</strong> el registro
            seleccionado?
        </DialogoConfirmacion>
    </SistemaLayout>
</template>
