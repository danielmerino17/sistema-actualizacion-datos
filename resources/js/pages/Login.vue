<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import InstitucionalLayout from '@/layouts/InstitucionalLayout.vue';

// Estado reactivo del formulario conectado a los 2 campos del diseño
const form = useForm({
    numero_trabajador: '',
    rfc: '',
});

const submit = () => {
    form.rfc = form.rfc.toUpperCase().trim();
    form.numero_trabajador = form.numero_trabajador.trim();

    form.post('/login');
};
</script>

<template>
    <Head title="SACPA - Login" />

    <InstitucionalLayout>
        <div
            class="flex flex-grow flex-col items-center justify-center px-4 py-8"
        >
            <h1
                class="mb-6 text-center text-base font-bold text-black md:text-lg"
            >
                Para ingresar proporciona los siguientes datos:
            </h1>

            <form
                @submit.prevent="submit"
                class="w-full max-w-md space-y-4 rounded-md border border-gray-400 bg-white p-6 shadow-sm"
            >
                <!-- Campo: Número de Trabajador -->
                <div>
                    <label
                        for="numero_trabajador"
                        class="mb-1 block text-lg font-bold text-black"
                    >
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
                    <p
                        v-if="form.errors.numero_trabajador"
                        class="mt-1 text-xs text-red-600"
                    >
                        {{ form.errors.numero_trabajador }}
                    </p>
                </div>

                <!-- Campo: RFC -->
                <div>
                    <label
                        for="rfc"
                        class="mb-1 block text-lg font-bold text-black"
                    >
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
                    <p v-if="form.errors.rfc" class="mt-1 text-xs text-red-600">
                        {{ form.errors.rfc }}
                    </p>
                </div>

                <!-- Botón de Ingresar -->
                <div class="pt-3">
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full rounded bg-[#333333] px-4 py-2.5 font-medium text-white transition hover:bg-black disabled:opacity-50"
                    >
                        {{ form.processing ? 'Verificando...' : 'Ingresar' }}
                    </button>
                </div>
            </form>
        </div>
    </InstitucionalLayout>
</template>