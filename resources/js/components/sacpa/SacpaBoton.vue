<script setup lang="ts">
/**
 * Botón con los estilos del Figma.
 *  - primario:   azul relleno (ej. "Agregar")
 *  - secundario: blanco con borde (ej. "Cancelar", "Aceptar", "Salir")
 *  - exito:      verde relleno (ej. "Descargar Excel" del administrador)
 */
import SacpaIcono from '@/components/sacpa/SacpaIcono.vue';
import { cn } from '@/lib/utils';
import type { NombreIcono } from '@/types/sacpa';

const props = withDefaults(
    defineProps<{
        variante?: 'primario' | 'secundario' | 'exito';
        tipo?: 'button' | 'submit';
        icono?: NombreIcono;
        deshabilitado?: boolean;
    }>(),
    {
        variante: 'primario',
        tipo: 'button',
        icono: undefined,
        deshabilitado: false,
    },
);

const estilos = {
    primario:
        'bg-sacpa-azul text-white hover:bg-sacpa-azul-oscuro focus-visible:outline-sacpa-azul',
    secundario:
        'border border-gray-500 bg-white text-gray-900 hover:bg-gray-100 focus-visible:outline-gray-500',
    exito: 'bg-sacpa-verde text-white hover:bg-sacpa-verde-oscuro focus-visible:outline-sacpa-verde',
};
</script>

<template>
    <button
        :type="props.tipo"
        :disabled="props.deshabilitado"
        :class="
            cn(
                'inline-flex items-center justify-center gap-2 rounded-full px-5 py-1.5 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                estilos[props.variante],
            )
        "
    >
        <SacpaIcono v-if="props.icono" :nombre="props.icono" clase="size-4" />
        <slot />
    </button>
</template>
