<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    status: Number,
    message: String,
});

const title = computed(() => {
    return {
        503: 'Service Indisponible',
        500: 'Erreur Serveur',
        404: 'Page Non Trouvée',
        403: 'Accès Interdit',
    }[props.status]
});

const description = computed(() => {
    if (props.message) {
        return props.message;
    }
    return {
        503: 'Désolé, nous effectuons une maintenance. Veuillez réessayer plus tard.',
        500: 'Oups, quelque chose s\'est mal passé sur nos serveurs.',
        404: 'Désolé, la page que vous recherchez est introuvable.',
        403: 'Désolé, vous n\'êtes pas autorisé à accéder à cette page.',
    }[props.status]
});
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg text-center">
            <div class="mb-4 text-red-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ title }}</h1>
            
            <p class="text-gray-600 mb-8">{{ description }}</p>

            <Link
                :href="route('dashboard')"
                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
            >
                Retour au tableau de bord
            </Link>
        </div>
    </div>
</template>
