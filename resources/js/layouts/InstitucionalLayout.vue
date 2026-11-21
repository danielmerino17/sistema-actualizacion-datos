<script setup></script>

<template>
    <div class="flex min-h-screen flex-col bg-white">
        <!-- Encabezado Institucional SACPA -->
        <header
            class="flex h-28 w-full items-center overflow-hidden bg-[#001447] shadow-md"
            role="banner"
        >
            <img
                src="/images/headerSACPA.png"
                alt="Header Institucional UNAM - FCA"
                class="h-full w-full object-cover object-left"
            />
        </header>

        <!-- Contenido dinámico de cada pantalla -->
        <main class="flex flex-grow flex-col">
            <slot />
        </main>
    </div>
</template>
