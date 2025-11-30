<!-- components/Charts/types/PaymentMethodsChart.vue -->
<template>
    <Doughnut
      :data="chartData"
      :options="chartOptions"
    />
  </template>
  
  <script setup>
  import { Doughnut } from 'vue-chartjs';
  import {
    Chart as ChartJS,
    ArcElement,
    Tooltip,
    Legend
  } from 'chart.js';
  
  ChartJS.register(
    ArcElement,
    Tooltip,
    Legend
  );
  
  const chartData = {
    labels: ['Espèces', 'Mobile Money', 'Virement', 'Autres'],
    datasets: [{
      data: [65, 20, 10, 5],
      backgroundColor: [
        'rgba(34, 197, 94, 0.6)',
        'rgba(251, 146, 60, 0.6)',
        'rgba(59, 130, 246, 0.6)',
        'rgba(168, 85, 247, 0.6)'
      ]
    }]
  };
  
  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        position: 'right',
        labels: {
          usePointStyle: true,
          pointStyle: 'circle'
        }
      },
      tooltip: {
        callbacks: {
          label: function(context) {
            return `${context.label}: ${context.raw}%`;
          }
        }
      }
    }
  };
  </script>