<script setup>
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: String,
});

defineEmits(['update:modelValue']);

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        ref="input"
        class="w-full px-3 py-1.5 border border-orange-200 rounded-lg focus:border-green-500 focus:ring-green-500 text-sm"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
    >
</template>
