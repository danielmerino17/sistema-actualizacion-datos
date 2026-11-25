<script setup lang="ts">
/**
 * Layout de las pantallas después del login (profesor y administrador):
 *  - Encabezado institucional con pestañas a la derecha.
 *  - Franja con los datos del profesor (si se manda).
 *  - Contenido de la pantalla.
 *  - Botón "Salir" abajo a la izquierda.
 *  - Mensajes flash (éxito / error) abajo a la derecha.
 */
import { Link } from '@inertiajs/vue3';
import DatosProfesor from '@/components/sacpa/DatosProfesor.vue';
import MensajeFlash from '@/components/sacpa/MensajeFlash.vue';
import InstitucionalLayout from '@/layouts/InstitucionalLayout.vue';
import { cn } from '@/lib/utils';
import type { DatosProfesor as Profesor, Pestana } from '@/types/sacpa';

withDefaults(
    defineProps<{
        pestanas?: Pestana[];
        profesor?: Profesor | null;
        salirHref?: string;
    }>(),
    {
        pestanas: () => [],
        profesor: null,
        salirHref: '/logout',
    },
);
</script>

<template>
    <InstitucionalLayout>
        <template v-if="pestanas.length" #acciones>
            <nav aria-label="Secciones" class="flex gap-3 sm:gap-5">
                <Link
                    v-for="pestana in pestanas"
                    :key="pestana.href"
                    :href="pestana.href"
                    :aria-current="pestana.activa ? 'page' : undefined"
                    :class="
                        cn(
                            'flex h-14 w-28 items-center justify-center rounded-lg bg-sacpa-marino px-3 text-center text-sm leading-tight font-semibold text-white transition hover:bg-white/15 sm:w-36',
                            pestana.activa && 'ring-2 ring-white',
                        )
                    "
                >
                    {{ pestana.titulo }}
                </Link>
            </nav>
        </template>

        <DatosProfesor v-if="profesor" :profesor="profesor" />

        <div class="flex flex-grow flex-col px-4 py-4 sm:px-8">
            <slot />
        </div>

        <footer class="px-4 pb-6 sm:px-8">
            <Link
                :href="salirHref"
                method="post"
                as="button"
                class="inline-flex items-center rounded-full border border-gray-500 bg-white px-8 py-1 text-sm font-semibold text-gray-900 transition hover:bg-gray-100"
            >
                Salir
            </Link>
        </footer>

        <MensajeFlash />
    </InstitucionalLayout>
</template>