<!-- components/Charts/types/CollectionRateChart.vue -->
<template>
    <Bar
      :data="chartData"
      :options="chartOptions"
    />
  </template>
  
  <script setup>
  import { Bar } from 'vue-chartjs';
  import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend
  } from 'chart.js';
  
  ChartJS.register(
    CategoryScale,
    LinearScale,
    BarElement,
    Title,
    Tooltip,
    Legend
  );
  
  const chartData = {
    labels: ['Zone A', 'Zone B', 'Zone C', 'Zone D', 'Zone E'],
    datasets: [{
      label: 'Taux de Recouvrement (%)',
      data: [85, 78, 92, 71, 88],
      backgroundColor: 'rgba(34, 197, 94, 0.6)',
      borderRadius: 5
    }]
  };
  
  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: function(context) {
            return `${context.raw}%`;
          }
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        max: 100,
        ticks: {
          callback: function(value) {
            return value + '%';
          }
        }
      }
    }
  };
  </script>