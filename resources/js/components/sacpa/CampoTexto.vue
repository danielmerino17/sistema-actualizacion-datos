<script setup lang="ts">
/**
 * Campo de texto con etiqueta a la izquierda y mensaje de error.
 * Uso: <CampoTexto id="descripcion" etiqueta="Medio de contacto:" v-model="form.descripcion" :error="form.errors.descripcion" />
 */
withDefaults(
    defineProps<{
        id: string;
        etiqueta: string;
        error?: string;
        tipo?: 'text' | 'email' | 'tel';
        maximo?: number;
        requerido?: boolean;
    }>(),
    {
        error: undefined,
        tipo: 'text',
        maximo: undefined,
        requerido: false,
    },
);

const modelo = defineModel<string>({ default: '' });
</script>

<template>
    <div class="flex flex-col gap-1">
        <div class="flex items-center gap-3">
            <label :for="id" class="shrink-0 text-sm font-bold text-black">
                {{ etiqueta }}
            </label>
            <input
                :id="id"
                v-model="modelo"
                :type="tipo"
                :maxlength="maximo"
                :required="requerido"
                :aria-invalid="error ? 'true' : undefined"
                :aria-describedby="error ? `${id}-error` : undefined"
                class="w-full rounded border border-gray-400 px-2 py-1 text-sm focus:border-sacpa-azul focus:ring-1 focus:ring-sacpa-azul focus:outline-none"
            />
        </div>
        <p v-if="error" :id="`${id}-error`" class="text-xs text-red-600">
            {{ error }}
        </p>
    </div>
</template>
