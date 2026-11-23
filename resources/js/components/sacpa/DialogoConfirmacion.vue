<script setup lang="ts">
/**
 * Cuadro de confirmación (ej. "¿Está seguro de que desea eliminar el registro seleccionado?").
 * Se cierra con Cancelar, con la tecla Esc o al dar clic fuera del cuadro.
 *
 * Uso:
 *   <DialogoConfirmacion :abierto="borrando !== null" @cancelar="borrando = null" @aceptar="eliminar">
 *       ¿Está seguro de que desea <strong>eliminar</strong> el registro seleccionado?
 *   </DialogoConfirmacion>
 */
import { onBeforeUnmount, watch } from 'vue';
import SacpaBoton from '@/components/sacpa/SacpaBoton.vue';

const props = withDefaults(
    defineProps<{
        abierto: boolean;
        procesando?: boolean;
    }>(),
    {
        procesando: false,
    },
);

const emit = defineEmits<{
    cancelar: [];
    aceptar: [];
}>();

function alPresionarTecla(evento: KeyboardEvent): void {
    if (evento.key === 'Escape' && !props.procesando) {
        emit('cancelar');
    }
}

watch(
    () => props.abierto,
    (abierto) => {
        if (abierto) {
            window.addEventListener('keydown', alPresionarTecla);
        } else {
            window.removeEventListener('keydown', alPresionarTecla);
        }
    },
    { immediate: true },
);

onBeforeUnmount(() => window.removeEventListener('keydown', alPresionarTecla));
</script>

<template>
    <Teleport to="body">
        <div
            v-if="abierto"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 px-4"
            @click.self="!procesando && emit('cancelar')"
        >
            <div
                role="alertdialog"
                aria-modal="true"
                aria-labelledby="dialogo-confirmacion-mensaje"
                class="w-full max-w-sm rounded-2xl border border-gray-400 bg-white px-6 py-5 text-center shadow-lg"
            >
                <p id="dialogo-confirmacion-mensaje" class="text-sm text-black">
                    <slot />
                </p>
                <div class="mt-5 flex justify-center gap-6">
                    <SacpaBoton
                        variante="secundario"
                        :deshabilitado="procesando"
                        @click="emit('cancelar')"
                    >
                        Cancelar
                    </SacpaBoton>
                    <SacpaBoton
                        v-focus
                        variante="secundario"
                        :deshabilitado="procesando"
                        @click="emit('aceptar')"
                    >
                        {{ procesando ? 'Procesando...' : 'Aceptar' }}
                    </SacpaBoton>
                </div>
            </div>
        </div>
    </Teleport>
</template>
