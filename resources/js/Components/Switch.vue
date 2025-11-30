<!-- Components/Switch.vue -->
<template>
    <button
        type="button"
        role="switch"
        :aria-checked="modelValue"
        :disabled="disabled"
        :class="[
            'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none',
            modelValue ? 'bg-green-600' : 'bg-gray-200',
            disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'
        ]"
        @click="toggle"
    >
        <span
            :class="[
                'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                modelValue ? 'translate-x-6' : 'translate-x-1',
            ]"
        />
    </button>
</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true,
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const toggle = () => {
    if (!props.disabled) {
        emit('update:modelValue', !props.modelValue);
    }
};
</script>