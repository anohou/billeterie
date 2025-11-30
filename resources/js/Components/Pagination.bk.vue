// resources/js/Components/Pagination.vue
<template>
    <div v-if="links.length > 3" class="flex items-center justify-center space-x-1 mt-4">
        <template v-for="(link, key) in links" :key="key">
            <div
                v-if="link.url === null"
                class="px-4 py-2 text-sm text-gray-500 rounded-md"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                class="px-4 py-2 text-sm rounded-md"
                :class="{
                    'bg-orange-500 text-white hover:bg-orange-600': link.active,
                    'text-gray-700 hover:bg-gray-100': !link.active
                }"
                v-html="link.label"
            />
        </template>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true
    }
});
</script>