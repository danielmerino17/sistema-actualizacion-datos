<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import InstitucionalLayout from '@/layouts/InstitucionalLayout.vue';

// Estado reactivo del formulario conectado a los 3 campos de tu diseño
const form = useForm({
    numero_trabajador: '',
    rfc: '',
    password: '',
});

const submit = () => {
    // Normalizamos el RFC a mayúsculas antes de enviarlo al backend
    form.rfc = form.rfc.toUpperCase().trim();
    form.numero_trabajador = form.numero_trabajador.trim();

    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Inicio de Sesión - SACPA" />

    <InstitucionalLayout>
        <div class="flex-grow flex flex-col items-center justify-center px-4 py-8">
            <h1 class="text-black font-bold text-base md:text-lg mb-6 text-center">
                Para ingresar proporciona los siguientes datos:
            </h1>

            <form 
                @submit.prevent="submit" 
                class="w-full max-w-md border border-gray-400 rounded-md p-6 bg-white shadow-sm space-y-4"
            >
                <!-- Campo: Número de Trabajador -->
                <div>
                    <label for="numero_trabajador" class="block text-black font-bold text-lg mb-1">
                        Número de Trabajador
                    </label>
                    <input
                        id="numero_trabajador"
                        v-model="form.numero_trabajador"
                        type="text"
                        required
                        autofocus
                        class="w-full rounded border-gray-300 bg-gray-50 focus:border-blue-900 focus:ring-blue-900"
                    />
                    <p v-if="form.errors.numero_trabajador" class="text-red-600 text-xs mt-1">
                        {{ form.errors.numero_trabajador }}
                    </p>
                </div>

                <!-- Campo: RFC -->
                <div>
                    <label for="rfc" class="block text-black font-bold text-lg mb-1">
                        RFC
                    </label>
                    <input
                        id="rfc"
                        v-model="form.rfc"
                        type="text"
                        maxlength="13"
                        required
                        class="w-full rounded border-gray-300 bg-gray-50 uppercase focus:border-blue-900 focus:ring-blue-900"
                    />
                    <p v-if="form.errors.rfc" class="text-red-600 text-xs mt-1">
                        {{ form.errors.rfc }}
                    </p>
                </div>

                <!-- Campo: Contraseña -->
                <div>
                    <label for="password" class="block text-black font-bold text-lg mb-1">
                        Contraseña
                    </label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        class="w-full rounded border-gray-300 bg-gray-50 focus:border-blue-900 focus:ring-blue-900"
                    />
                    <p v-if="form.errors.password" class="text-red-600 text-xs mt-1">
                        {{ form.errors.password }}
                    </p>
                </div>

                <!-- Botón Ingresar -->
                <div class="pt-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-[#333333] hover:bg-black text-white font-medium py-2.5 px-4 rounded transition disabled:opacity-50"
                    >
                        {{ form.processing ? 'Verificando...' : 'Ingresar' }}
                    </button>
                </div>
            </form>
        </div>
    </InstitucionalLayout>
</template>