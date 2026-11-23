<script setup lang="ts" generic="T extends Record<string, unknown>">
/**
 * Tabla con borde azul redondeado como en el Figma.
 *
 * Uso:
 *   <TablaSacpa :columnas="columnas" :filas="contactos" clave-fila="id">
 *       <template #celda-medio="{ fila }">...</template>   (opcional, para personalizar una celda)
 *       <template #acciones="{ fila }">...</template>      (opcional, agrega la columna "Acciones")
 *   </TablaSacpa>
 */
import type { ColumnaTabla } from '@/types/sacpa';

withDefaults(
    defineProps<{
        columnas: ColumnaTabla[];
        filas: T[];
        claveFila: keyof T & string;
        textoVacio?: string;
    }>(),
    {
        textoVacio: 'Todavía no hay datos registrados.',
    },
);

defineSlots<
    {
        acciones?: (props: { fila: T }) => unknown;
    } & {
        [celda: `celda-${string}`]: (props: { fila: T }) => unknown;
    }
>();
</script>

<template>
    <div class="overflow-x-auto rounded-xl border-2 border-sacpa-azul bg-white">
        <table class="w-full border-collapse text-center text-sm text-black">
            <thead>
                <tr class="border-b-2 border-sacpa-azul">
                    <th
                        v-for="columna in columnas"
                        :key="columna.clave"
                        scope="col"
                        class="border-r-2 border-sacpa-azul px-3 py-2 font-bold last:border-r-0"
                    >
                        {{ columna.titulo }}
                    </th>
                    <th
                        v-if="$slots.acciones"
                        scope="col"
                        class="px-3 py-2 font-bold"
                    >
                        Acciones
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="filas.length === 0">
                    <td
                        :colspan="columnas.length + ($slots.acciones ? 1 : 0)"
                        class="px-3 py-6 text-gray-600"
                    >
                        {{ textoVacio }}
                    </td>
                </tr>
                <tr v-for="fila in filas" :key="String(fila[claveFila])">
                    <td
                        v-for="columna in columnas"
                        :key="columna.clave"
                        class="border-r-2 border-sacpa-azul px-3 py-2 whitespace-nowrap last:border-r-0"
                    >
                        <slot :name="`celda-${columna.clave}`" :fila="fila">
                            {{ fila[columna.clave] }}
                        </slot>
                    </td>
                    <td v-if="$slots.acciones" class="px-3 py-2">
                        <div class="flex items-center justify-center gap-3">
                            <slot name="acciones" :fila="fila" />
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
