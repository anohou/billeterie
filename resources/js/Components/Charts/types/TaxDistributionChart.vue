<template>
  <div class="h-full">
    <div v-if="isLoading" class="h-full flex items-center justify-center">
      <span class="text-gray-500">Chargement...</span>
    </div>
    <div v-else-if="!hasData" class="h-full flex items-center justify-center">
      <span class="text-gray-500">Aucune donnée disponible</span>
    </div>
    <Doughnut 
      v-else
      :data="chartData"
      :options="chartOptions"
    />
  </div>
</template>

<script setup>
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  ArcElement
} from 'chart.js';
import { Doughnut } from 'vue-chartjs';
import { useStatsStore } from '@/stores/statsStore';
import { computed } from 'vue';

ChartJS.register(
  ArcElement,
  Title,
  Tooltip,
  Legend
);

const statsStore = useStatsStore();

const isLoading = computed(() => {
  return statsStore.isLoading;
});

// Define colors mapping
const TAX_COLORS = {
  'TF': { bg: 'rgba(34, 197, 94, 0.6)', border: 'rgb(34, 197, 94)' },
  'DPM': { bg: 'rgba(251, 146, 60, 0.6)', border: 'rgb(251, 146, 60)' },
  'BAL': { bg: 'rgba(59, 130, 246, 0.6)', border: 'rgb(59, 130, 246)' },
  'ODP': { bg: 'rgba(168, 85, 247, 0.6)', border: 'rgb(168, 85, 247)' },
  'TAXI': { bg: 'rgba(236, 72, 153, 0.6)', border: 'rgb(236, 72, 153)' }
};

const chartData = computed(() => {
  const byTax = statsStore.paymentStats?.by_tax || [];
  
  if (byTax.length === 0) {
    return {
      labels: [],
      datasets: [{
        data: [],
        backgroundColor: [],
        borderColor: [],
        borderWidth: 1
      }]
    };
  }

  return {
    labels: byTax.map(item => item.label),
    datasets: [{
      data: byTax.map(item => parseFloat(item.value)),
      backgroundColor: byTax.map(item => TAX_COLORS[item.label]?.bg || 'rgba(156, 163, 175, 0.6)'),
      borderColor: byTax.map(item => TAX_COLORS[item.label]?.border || 'rgb(156, 163, 175)'),
      borderWidth: 1
    }]
  };
});

const hasData = computed(() => {
  const data = chartData.value;
  return data?.labels?.length > 0 && data?.datasets?.[0]?.data?.length > 0;
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    title: {
      display: true,
      text: 'Distribution des taxes',
      font: {
        size: 16,
        weight: 'bold'
      },
      padding: {
        top: 10,
        bottom: 30
      },
      color: '#065f46'
    },
    legend: {
      position: 'right',
      labels: {
        font: {
          size: 12
        },
        usePointStyle: true,
        padding: 20
      }
    },
    tooltip: {
      callbacks: {
        label: (context) => {
          const value = parseFloat(context.raw) || 0;
          const dataset = context.dataset.data || [];
          const total = dataset.reduce((a, b) => a + (parseFloat(b) || 0), 0);
          const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
          return [
            `${context.label}: ${new Intl.NumberFormat('fr-FR').format(value)} FCFA`,
            `${percentage}% du total`
          ];
        }
      }
    }
  }
};
</script>