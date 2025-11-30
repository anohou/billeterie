<template>
  <BaseChart
    type="bar"
    :data="data"
    :label="label"
    :options="options"
  />
</template>

<script setup>
import BaseChart from './BaseChart.vue';

defineProps({
  data: {
    type: Array,
    required: true
  },
  label: {
    type: String,
    default: 'Distribution'
  },
  options: {
    type: Object,
    default: () => ({})
  }
});
</script>