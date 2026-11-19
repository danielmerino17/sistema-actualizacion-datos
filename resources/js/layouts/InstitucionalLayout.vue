<script setup>
</script>

<template>
    <div class="min-h-screen flex flex-col bg-white">
        <!-- Encabezado Institucional SACPA -->
        <header 
            class="w-full h-28 bg-[#001447] shadow-md overflow-hidden flex items-center" 
            role="banner"
        >
            <img 
                src="/images/headerSACPA.png" 
                alt="Header Institucional UNAM - FCA" 
                class="h-full w-full object-cover object-left"
            />
        </header>

        <!-- Contenido dinámico de cada pantalla -->
        <main class="flex-grow flex flex-col">
            <slot />
        </main>
    </div>
</template>