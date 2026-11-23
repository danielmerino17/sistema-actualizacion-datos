<script setup lang="ts">
/**
 * Íconos usados en las pantallas del Figma, dibujados en SVG
 * (no se necesita instalar ninguna librería de íconos).
 */
import type { NombreIcono } from '@/types/sacpa';

withDefaults(defineProps<{ nombre: NombreIcono; clase?: string }>(), {
    clase: 'size-5',
});
</script>

<template>
    <svg
        :class="clase"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <template v-if="nombre === 'agregar'">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v8M8 12h8" />
        </template>
        <template v-else-if="nombre === 'editar'">
            <path d="M4 20h4L18.5 9.5a2.1 2.1 0 0 0-4-4L4 16z" />
            <path d="m13.5 6.5 4 4" />
        </template>
        <template v-else-if="nombre === 'eliminar'">
            <path d="M4 7h16M10 11v6M14 11v6" />
            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2l1-12" />
            <path d="M9 7V4h6v3" />
        </template>
        <template v-else-if="nombre === 'regresar'">
            <path d="M12 19V5M5 12l7-7 7 7" />
        </template>
        <template v-else-if="nombre === 'aviso'">
            <path
                d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"
            />
            <path d="M12 9v4M12 17h.01" />
        </template>
        <template v-else-if="nombre === 'descargar'">
            <path d="M12 3v12M7 10l5 5 5-5M5 21h14" />
        </template>
        <template v-else-if="nombre === 'exito'">
            <circle cx="12" cy="12" r="9" />
            <path d="m8 12 3 3 5-6" />
        </template>
        <template v-else-if="nombre === 'error'">
            <circle cx="12" cy="12" r="9" />
            <path d="M12 8v5M12 16h.01" />
        </template>
    </svg>
</template>
