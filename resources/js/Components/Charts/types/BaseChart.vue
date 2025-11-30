// resources/js/Components/Charts/types/BaseChart.vue
<template>
  <div class="h-full w-full">
    <div v-if="loading" class="h-full flex items-center justify-center">
      <span class="text-gray-500">Chargement...</span>
    </div>
    <component 
      v-else
      :is="chartType"
      :data="chartData"
      :options="mergedOptions"
    />
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  ArcElement,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement
} from 'chart.js';
import { Line, Bar, Pie } from 'vue-chartjs';

// Register ChartJS components
ChartJS.register(
  Title,
  Tooltip,
  Legend,
  ArcElement,
  CategoryScale,
  LinearScale,
  BarElement,
  LineElement,
  PointElement
);

const props = defineProps({
  type: {
    type: String,
    required: true,
    validator: (value) => ['line', 'bar', 'pie'].includes(value)
  },
  label: {
    type: String,
    required: true
  },
  data: {
    type: Array,
    required: true
  },
  options: {
    type: Object,
    default: () => ({})
  }
});

const loading = ref(true);

const chartType = computed(() => {
  switch (props.type) {
    case 'line': return Line;
    case 'bar': return Bar;
    case 'pie': return Pie;
    default: return Line;
  }
});

const defaultOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: true
    },
    tooltip: {
      callbacks: {
        label: (context) => {
          return `${context.label}: ${formatNumber(context.raw)} FCFA`;
        }
      }
    }
  }
};

const mergedOptions = computed(() => ({
  ...defaultOptions,
  ...props.options
}));

const chartData = computed(() => ({
  labels: props.data.map(item => item.label),
  datasets: [{
    label: props.label,
    data: props.data.map(item => item.value),
    backgroundColor: generateColors(props.data.length),
    borderColor: props.type === 'line' ? 'rgb(34, 197, 94)' : generateColors(props.data.length),
    borderWidth: 2
  }]
}));

function generateColors(count) {
  const baseColors = [
    'rgba(34, 197, 94, 0.6)',   // Green
    'rgba(251, 146, 60, 0.6)',  // Orange
    'rgba(59, 130, 246, 0.6)',  // Blue
    'rgba(168, 85, 247, 0.6)',  // Purple
    'rgba(236, 72, 153, 0.6)'   // Pink
  ];
  
  return Array(count).fill().map((_, i) => baseColors[i % baseColors.length]);
}

function formatNumber(value) {
  return new Intl.NumberFormat('fr-FR', {
    style: 'decimal',
    maximumFractionDigits: 0
  }).format(value);
}

onMounted(() => {
  loading.value = false;
});
</script>