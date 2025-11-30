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
} from 'chart.js';
import { Bar } from 'vue-chartjs';
import { useStatsStore } from '@/stores/statsStore';
import { ref, computed } from 'vue';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';

ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Title,
  Tooltip,
  Legend
);

const statsStore = useStatsStore();

const isLoading = computed(() => {
  return statsStore.isLoading;
});

const hasData = computed(() => {
  const dailyPayments = statsStore.paymentStats?.daily_payments || [];
  return dailyPayments.length > 0;
});

const chartData = computed(() => {
  const dailyPayments = statsStore.paymentStats?.daily_payments || [];
  
  // If we have real data, use it
  if (dailyPayments.length > 0) {
    return {
      labels: dailyPayments.map(item => format(new Date(item.date), 'dd MMM', { locale: fr })),
      datasets: [{
        label: 'Paiements journaliers',
        data: dailyPayments.map(item => parseFloat(item.amount)),
        backgroundColor: 'rgba(34, 197, 94, 0.2)',
        borderColor: 'rgb(34, 197, 94)',
        borderWidth: 2
      }]
    };
  }
  
  // Fallback test data
  return {
    labels: ['01 Jan', '02 Jan', '03 Jan', '04 Jan', '05 Jan'],
    datasets: [{
      label: 'Paiements journaliers',
      data: [12000, 15000, 8000, 22000, 18000],
      backgroundColor: 'rgba(34, 197, 94, 0.2)',
      borderColor: 'rgb(34, 197, 94)',
      borderWidth: 2
    }]
  };
});

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
    },
    x: {
      grid: {
        display: false
      }
    }
  },
  plugins: {
    title: {
      display: true,
      text: 'Paiements journaliers',
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
      display: false
    },
    tooltip: {
      callbacks: {
        label: (context) => {
          const value = context.raw;
          return `${new Intl.NumberFormat('fr-FR').format(value)} FCFA`;
        }
      }
    }
  }
};
</script>