<script setup lang="ts">
/**
 * Muestra abajo a la derecha los mensajes que manda Laravel con
 * Inertia::flash('exito', '...') o Inertia::flash('error', '...').
 * Se oculta solo después de unos segundos.
 */
import { usePage } from '@inertiajs/vue3';
import { onBeforeUnmount, ref, watch } from 'vue';
import SacpaIcono from '@/components/sacpa/SacpaIcono.vue';
import type { MensajesFlash } from '@/types/sacpa';

const DURACION_MS = 4000;

const page = usePage();
const mensaje = ref<{ tipo: 'exito' | 'error'; texto: string } | null>(null);
let temporizador: ReturnType<typeof setTimeout> | undefined;

function mostrar(flash: MensajesFlash): void {
    const texto = flash.error ?? flash.exito;

    if (!texto) {
        return;
    }

    mensaje.value = { tipo: flash.error ? 'error' : 'exito', texto };
    clearTimeout(temporizador);
    temporizador = setTimeout(() => (mensaje.value = null), DURACION_MS);
}

watch(() => page.flash, mostrar, { immediate: true });

onBeforeUnmount(() => clearTimeout(temporizador));
</script>

<template>
    <Transition
        enter-from-class="translate-y-2 opacity-0"
        enter-active-class="transition duration-200"
        leave-to-class="opacity-0"
        leave-active-class="transition duration-200"
    >
        <div
            v-if="mensaje"
            role="status"
            aria-live="polite"
            :class="[
                'fixed right-6 bottom-6 z-40 flex items-center gap-2 rounded-lg border bg-white px-4 py-2 text-sm shadow-md',
                mensaje.tipo === 'exito'
                    ? 'border-green-600 text-green-800'
                    : 'border-red-600 text-red-700',
            ]"
        >
            <SacpaIcono :nombre="mensaje.tipo" clase="size-4" />
            {{ mensaje.texto }}
        </div>
    </Transition>
</template>
