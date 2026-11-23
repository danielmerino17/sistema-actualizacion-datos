<script setup lang="ts">
/**
 * Lista desplegable con etiqueta y mensaje de error.
 * Uso: <CampoSeleccion id="tipo" etiqueta="Tipo de medio:" :opciones="tipos" v-model="form.tipo" :error="form.errors.tipo" />
 */
import type { OpcionSeleccion } from '@/types/sacpa';

withDefaults(
    defineProps<{
        id: string;
        etiqueta: string;
        opciones: OpcionSeleccion[];
        error?: string;
        textoVacio?: string;
        requerido?: boolean;
    }>(),
    {
        error: undefined,
        textoVacio: 'Selecciona una opción...',
        requerido: false,
    },
);

const modelo = defineModel<string | number | null>({ default: null });
</script>

<template>
    <div class="flex flex-col gap-1">
        <div class="flex items-center gap-3">
            <label :for="id" class="shrink-0 text-sm font-bold text-black">
                {{ etiqueta }}
            </label>
            <select
                :id="id"
                v-model="modelo"
                :required="requerido"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="error ? `${id}-error` : undefined"
                class="w-full rounded border border-gray-400 bg-white px-2 py-1 text-sm focus:border-sacpa-azul focus:ring-1 focus:ring-sacpa-azul focus:outline-none"
            >
                <option :value="null" disabled>{{ textoVacio }}</option>
                <option
                    v-for="opcion in opciones"
                    :key="opcion.valor"
                    :value="opcion.valor"
                >
                    {{ opcion.texto }}
                </option>
            </select>
        </div>
        <p v-if="error" :id="`${id}-error`" class="text-xs text-red-600">
            {{ error }}
        </p>
    </div>
</template>
