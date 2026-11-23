<script setup lang="ts">
/**
 * Franja debajo del encabezado con nombre, apellidos y número de trabajador.
 */
import type { DatosProfesor } from '@/types/sacpa';

defineProps<{ profesor: DatosProfesor }>();
</script>

<template>
    <section
        aria-label="Datos del profesor"
        class="grid grid-cols-2 gap-y-2 border-b border-gray-400 px-4 py-3 text-center text-sm text-black sm:grid-cols-4"
    >
        <div>
            <p class="font-bold">Nombre(s):</p>
            <p>{{ profesor.nombre }}</p>
        </div>
        <div>
            <p class="font-bold">Apellido paterno:</p>
            <p>{{ profesor.apellidoPaterno }}</p>
        </div>
        <div>
            <p class="font-bold">Apellido materno:</p>
            <p>{{ profesor.apellidoMaterno ?? '—' }}</p>
        </div>
        <div>
            <p class="font-bold">Número de Trabajador:</p>
            <p>{{ profesor.numeroTrabajador }}</p>
        </div>
    </section>
</template>
