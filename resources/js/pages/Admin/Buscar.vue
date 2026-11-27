<script setup lang="ts">
/**
 * Búsqueda de profesores del administrador (Figma, sección ADMINISTRADOR).
 * Si la búsqueda encuentra un solo profesor, Laravel redirige directo a sus datos;
 * si encuentra varios, se muestran aquí para elegir.
 * Los datos llegan de BuscarProfesorController.
 */
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import CampoTexto from '@/components/sacpa/CampoTexto.vue';
import SacpaBoton from '@/components/sacpa/SacpaBoton.vue';
import SacpaIcono from '@/components/sacpa/SacpaIcono.vue';
import TablaSacpa from '@/components/sacpa/TablaSacpa.vue';
import SistemaLayout from '@/layouts/SistemaLayout.vue';
import type { ColumnaTabla } from '@/types/sacpa';

type Resultado = {
    id: number;
    numeroTrabajador: string;
    nombre: string;
};

const props = defineProps<{
    busqueda: { nombre: string; numero_trabajador: string; rfc: string };
    resultados: Resultado[] | null;
}>();

const form = useForm({
    nombre: props.busqueda.nombre,
    numero_trabajador: props.busqueda.numero_trabajador,
    rfc: props.busqueda.rfc,
});

// Error general de la búsqueda (ej. "No se encontró ningún profesor").
const page = usePage();
const errorBusqueda = computed(() => page.props.errors?.busqueda);

const columnas: ColumnaTabla[] = [
    { clave: 'numeroTrabajador', titulo: 'Número de Trabajador' },
    { clave: 'nombre', titulo: 'Nombre' },
];

function consultar(): void {
    form.rfc = form.rfc.toUpperCase().trim();
    form.get('/admin', { preserveScroll: true });
}
</script>

<template>
    <Head title="Consulta de profesores" />

    <SistemaLayout>
        <div class="flex flex-col items-center gap-6 py-4">
            <p
                role="status"
                class="inline-flex items-center gap-2 rounded-full border border-gray-500 px-4 py-1 text-xs text-black"
            >
                <SacpaIcono nombre="error" clase="size-4" />
                <span>
                    <strong>AVISO:</strong> Ahora mismo, usted cuenta con
                    permisos de <span class="underline">administrador</span>.
                </span>
            </p>

            <form
                class="flex w-full max-w-md flex-col gap-4"
                @submit.prevent="consultar"
            >
                <h1 class="text-center text-sm font-bold text-black">
                    Para consultar la información de un profesor, ingrese alguno
                    de los siguientes datos:
                </h1>

                <CampoTexto
                    id="nombre"
                    v-model="form.nombre"
                    etiqueta="Nombre:"
                    ancho-etiqueta="w-32"
                    :maximo="100"
                    :error="form.errors.nombre"
                />
                <CampoTexto
                    id="numero-trabajador"
                    v-model="form.numero_trabajador"
                    etiqueta="No. Trabajador:"
                    ancho-etiqueta="w-32"
                    :maximo="10"
                    :error="form.errors.numero_trabajador"
                />
                <CampoTexto
                    id="rfc"
                    v-model="form.rfc"
                    etiqueta="RFC:"
                    ancho-etiqueta="w-32"
                    :maximo="13"
                    :error="form.errors.rfc"
                />

                <p
                    v-if="errorBusqueda"
                    role="alert"
                    class="text-center text-sm text-red-600"
                >
                    {{ errorBusqueda }}
                </p>

                <div class="flex justify-center">
                    <SacpaBoton
                        variante="secundario"
                        tipo="submit"
                        :deshabilitado="form.processing"
                    >
                        {{ form.processing ? 'Consultando...' : 'Consultar' }}
                    </SacpaBoton>
                </div>
            </form>

            <section
                v-if="resultados && resultados.length"
                class="w-full max-w-2xl"
                aria-label="Resultados de la búsqueda"
            >
                <h2 class="mb-2 text-sm font-bold text-black">
                    Se encontraron {{ resultados.length }} profesores. Elige
                    uno:
                </h2>
                <TablaSacpa
                    :columnas="columnas"
                    :filas="resultados"
                    clave-fila="id"
                >
                    <template #acciones="{ fila }">
                        <Link
                            :href="`/admin/profesores/${fila.id}/datos-personales`"
                            class="font-semibold text-sacpa-azul hover:underline"
                        >
                            Consultar
                        </Link>
                    </template>
                </TablaSacpa>
            </section>
        </div>
    </SistemaLayout>
</template>
