<!-- TaxTypeCollectionsChart.vue -->
<template>
    <div class="h-[300px]">
      <Doughnut
        :data="chartData"
        :options="chartOptions"
      />
    </div>
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
  
  const props = defineProps({
    chartData: {
      type: Object,
      required: true
    }
  });
  
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
            let label = context.label || '';
            if (label) {
              label += ': ';
            }
            label += (context.parsed / 1000000).toFixed(1) + 'M FCFA';
            return label;
          }
        }
      }
    }
  };
  </script>