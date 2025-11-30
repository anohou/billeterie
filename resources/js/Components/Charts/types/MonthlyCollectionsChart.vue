<template>
  <div class="h-full">
    <div v-if="isLoading" class="h-full flex items-center justify-center">
      <span class="text-gray-500">Chargement...</span>
    </div>
    <div v-else-if="!hasData" class="h-full flex items-center justify-center">
      <span class="text-gray-500">Aucune donnée disponible</span>
    </div>
    <Bar 
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
  BarElement,
  CategoryScale,
  LinearScale
} from 'chart.js'
import { Bar } from 'vue-chartjs';
import { useStatsStore } from '@/stores/statsStore';
import { ref, computed } from 'vue';

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
)

const statsStore = useStatsStore();

const isLoading = computed(() => {
  return statsStore.isLoading;
});

const hasData = computed(() => {
  return statsStore.monthlyCollections && statsStore.monthlyCollections.length > 0;
});

const chartData = computed(() => ({
  labels: statsStore.monthlyCollections.map((item) => item.month),
  datasets: [{
    label: 'Collections (FCFA)',
    data: statsStore.monthlyCollections.map((item) => item.amount),
    backgroundColor: 'rgba(34, 197, 94, 0.2)',
    borderColor: 'rgb(34, 197, 94)',
    borderWidth: 2
  }]
}));

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        callback: (value) => {
          return new Intl.NumberFormat('fr-FR', {
            style: 'decimal',
            maximumFractionDigits: 0
          }).format(value) + ' FCFA';
        }
      }
    }
  },
  plugins: {
    title: {
      display: true,
      text: 'Collections mensuelles',
      font: {
        size: 16,
        weight: 'bold'
      },
      padding: {
        top: 10,
        bottom: 30
      },
      color: '#065f46' // text-green-800 equivalent
    },
    legend: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: (context) => {
          return new Intl.NumberFormat('fr-FR', {
            style: 'decimal',
            maximumFractionDigits: 0
          }).format(context.raw) + ' FCFA';
        }
      }
    }
  }
};
</script>