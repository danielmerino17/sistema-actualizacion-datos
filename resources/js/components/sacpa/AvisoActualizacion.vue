<script setup lang="ts">
/**
 * Aviso amarillo del Figma: se muestra cuando los datos del profesor
 * no se han actualizado en los últimos seis meses.
 * Quien lo usa decide cuándo mostrarlo (v-if).
 */
import SacpaIcono from '@/components/sacpa/SacpaIcono.vue';

defineProps<{ ultimaActualizacion: string | null }>();
</script>

<template>
    <div
        role="status"
        class="inline-flex items-center gap-2 rounded-lg bg-sacpa-aviso px-3 py-1.5 text-xs text-black"
    >
        <SacpaIcono nombre="aviso" clase="size-4 shrink-0" />
        <p>
            Estimado profesor, es necesario actualizar sus datos.<br />
            <strong>
                Última actualización:
                {{ ultimaActualizacion ?? 'sin registro' }}
            </strong>
        </p>
    </div>
</template>
