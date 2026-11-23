<script setup></script>

<template>
    <div class="flex min-h-screen flex-col bg-white">
        <!-- Encabezado Institucional SACPA -->
        <header
            class="relative flex h-28 w-full items-center overflow-hidden bg-[#001447] shadow-md"
            role="banner"
        >
            <img
                src="/images/headerSACPA.png"
                alt="Header Institucional UNAM - FCA"
                class="h-full w-full object-cover object-left"
            />

            <!-- Espacio opcional a la derecha del encabezado (ej. pestañas) -->
            <div
                v-if="$slots.acciones"
                class="absolute inset-y-0 right-0 flex items-center gap-4 pr-4 sm:pr-8"
            >
                <slot name="acciones" />
            </div>
        </header>

        <!-- Contenido dinámico de cada pantalla -->
        <main class="flex flex-grow flex-col">
            <slot />
        </main>
    </div>
</template>
