<script setup lang="ts">
/**
 * Panel de formulario como en el Figma: no es una ventana flotante,
 * aparece dentro de la página en lugar de la tabla.
 * Barra azul con título y flecha para regresar; botones Cancelar / Aceptar.
 *
 * Eventos:
 *   @regresar  flecha o botón Cancelar
 *   @enviar    botón Aceptar (o Enter dentro del formulario)
 */
import SacpaBoton from '@/components/sacpa/SacpaBoton.vue';
import SacpaIcono from '@/components/sacpa/SacpaIcono.vue';

withDefaults(
    defineProps<{
        titulo: string;
        procesando?: boolean;
        textoAceptar?: string;
    }>(),
    {
        procesando: false,
        textoAceptar: 'Aceptar',
    },
);

const emit = defineEmits<{
    regresar: [];
    enviar: [];
}>();
</script>

<template>
    <form
        class="overflow-hidden rounded-xl border border-gray-400 bg-white shadow-sm"
        @submit.prevent="emit('enviar')"
    >
        <div
            class="flex items-center gap-3 rounded-xl bg-sacpa-azul px-4 py-2 text-white"
        >
            <button
                type="button"
                class="rounded-full p-1 hover:bg-white/15"
                aria-label="Regresar"
                @click="emit('regresar')"
            >
                <SacpaIcono nombre="regresar" />
            </button>
            <h2 class="text-base font-semibold">{{ titulo }}</h2>
        </div>

        <div class="px-6 py-5">
            <slot />
        </div>

        <div class="flex items-center justify-between px-6 pb-4">
            <SacpaBoton
                variante="secundario"
                :deshabilitado="procesando"
                @click="emit('regresar')"
            >
                Cancelar
            </SacpaBoton>
            <SacpaBoton
                variante="secundario"
                tipo="submit"
                :deshabilitado="procesando"
            >
                {{ procesando ? 'Guardando...' : textoAceptar }}
            </SacpaBoton>
        </div>
    </form>
</template>
