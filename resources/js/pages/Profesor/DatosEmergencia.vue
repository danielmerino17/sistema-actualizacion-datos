<script setup lang="ts">
/**
 * Pantalla "Datos de emergencia" del profesor (Figma, flujos 3, 4 y 5).
 * Lista sus contactos de emergencia y permite agregarlos, editarlos y eliminarlos.
 * Los datos llegan de DatosEmergenciaController@index.
 */
import { Head, router, useForm } from '@inertiajs/vue3';
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

type ContactoEmergencia = {
    id: number;
    numero: number;
    nombre: string;
    apellidoPaterno: string;
    apellidoMaterno: string | null;
    parentescoId: number;
    parentesco: string | null;
    celular: string | null;
    alterno: string | null;
    fecha: string | null;
};

defineProps<{
    profesor: DatosProfesor;
    contactos: ContactoEmergencia[];
    parentescos: OpcionSeleccion[];
    ultimaActualizacion: string | null;
    requiereActualizacion: boolean;
}>();

const pestanas = pestanasProfesor('emergencia');

const columnas: ColumnaTabla[] = [
    { clave: 'numero', titulo: 'No. Contacto' },
    { clave: 'nombre', titulo: 'Nombre(s)' },
    { clave: 'apellidoPaterno', titulo: 'Apellido paterno' },
    { clave: 'apellidoMaterno', titulo: 'Apellido materno' },
    { clave: 'parentesco', titulo: 'Parentesco' },
    { clave: 'celular', titulo: 'Teléfono (celular)' },
    { clave: 'alterno', titulo: 'Teléfono (alterno)' },
    { clave: 'fecha', titulo: 'Última fecha de actualización' },
];

// ----- Formulario de alta / edición -----
const mostrandoFormulario = ref(false);
const editando = ref<ContactoEmergencia | null>(null);

const form = useForm<{
    nombre: string;
    apellido_paterno: string;
    apellido_materno: string;
    parentesco: number | string | null;
    telefono_celular: string;
    telefono_alterno: string;
}>({
    nombre: '',
    apellido_paterno: '',
    apellido_materno: '',
    parentesco: null,
    telefono_celular: '',
    telefono_alterno: '',
});

const tituloFormulario = computed(() =>
    editando.value
        ? 'Actualización de contacto de emergencia:'
        : 'Nuevo contacto de emergencia:',
);

function abrirAlta(): void {
    editando.value = null;
    form.reset();
    form.clearErrors();
    mostrandoFormulario.value = true;
}

function abrirEdicion(contacto: ContactoEmergencia): void {
    editando.value = contacto;
    form.clearErrors();
    form.nombre = contacto.nombre;
    form.apellido_paterno = contacto.apellidoPaterno;
    form.apellido_materno = contacto.apellidoMaterno ?? '';
    form.parentesco = contacto.parentescoId;
    form.telefono_celular = contacto.celular ?? '';
    form.telefono_alterno = contacto.alterno ?? '';
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
        form.put(`/datos-de-emergencia/${editando.value.id}`, opciones);
    } else {
        form.post('/datos-de-emergencia', opciones);
    }
}

// ----- Eliminar -----
const porEliminar = ref<ContactoEmergencia | null>(null);
const eliminando = ref(false);

function eliminar(): void {
    if (!porEliminar.value) {
        return;
    }

    router.delete(`/datos-de-emergencia/${porEliminar.value.id}`, {
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
    <Head title="Datos de emergencia" />

    <SistemaLayout :pestanas="pestanas" :profesor="profesor">
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
        </div>

        <div class="mx-auto w-full max-w-6xl">
            <PanelFormulario
                v-if="mostrandoFormulario"
                :titulo="tituloFormulario"
                :procesando="form.processing"
                texto-aceptar="Guardar"
                @regresar="cerrarFormulario"
                @enviar="enviar"
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <CampoTexto
                        id="nombre"
                        v-model="form.nombre"
                        etiqueta="Nombre(s):"
                        :maximo="40"
                        :error="form.errors.nombre"
                    />
                    <CampoSeleccion
                        id="parentesco"
                        v-model="form.parentesco"
                        etiqueta="Parentesco:"
                        :opciones="parentescos"
                        :error="form.errors.parentesco"
                    />
                    <CampoTexto
                        id="apellido-paterno"
                        v-model="form.apellido_paterno"
                        etiqueta="Apellido paterno:"
                        :maximo="30"
                        :error="form.errors.apellido_paterno"
                    />
                    <CampoTexto
                        id="telefono-celular"
                        v-model="form.telefono_celular"
                        etiqueta="Teléfono (celular):"
                        tipo="tel"
                        :maximo="20"
                        :error="form.errors.telefono_celular"
                    />
                    <CampoTexto
                        id="apellido-materno"
                        v-model="form.apellido_materno"
                        etiqueta="Apellido materno:"
                        :maximo="30"
                        :error="form.errors.apellido_materno"
                    />
                    <CampoTexto
                        id="telefono-alterno"
                        v-model="form.telefono_alterno"
                        etiqueta="Teléfono (alterno):"
                        tipo="tel"
                        :maximo="20"
                        :error="form.errors.telefono_alterno"
                    />
                </div>
            </PanelFormulario>

            <TablaSacpa
                v-else
                :columnas="columnas"
                :filas="contactos"
                clave-fila="id"
                texto-vacio="Todavía no has registrado contactos de emergencia. Usa el botón Agregar."
            >
                <template #celda-apellidoMaterno="{ fila }">
                    {{ fila.apellidoMaterno ?? '—' }}
                </template>
                <template #celda-alterno="{ fila }">
                    {{ fila.alterno ?? '—' }}
                </template>
                <template #acciones="{ fila }">
                    <button
                        type="button"
                        class="text-gray-800 hover:text-sacpa-azul"
                        :aria-label="`Editar a ${fila.nombre} ${fila.apellidoPaterno}`"
                        @click="abrirEdicion(fila)"
                    >
                        <SacpaIcono nombre="editar" clase="size-4" />
                    </button>
                    <button
                        type="button"
                        class="text-gray-800 hover:text-red-600"
                        :aria-label="`Eliminar a ${fila.nombre} ${fila.apellidoPaterno}`"
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
