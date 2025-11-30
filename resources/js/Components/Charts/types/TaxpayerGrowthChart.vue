<!-- components/Charts/types/TaxpayerGrowthChart.vue -->
<template>
    <Line
      :data="chartData"
      :options="chartOptions"
    />
  </template>
  
  <script setup>
  import { Line } from 'vue-chartjs';
  import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
  } from 'chart.js';
  
  ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
  );
  
  const chartData = {
    labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin'],
    datasets: [{
      label: 'Nouveaux Contribuables',
      data: [45, 52, 38, 65, 48, 56],
      borderColor: 'rgb(251, 146, 60)',
      backgroundColor: 'rgba(251, 146, 60, 0.1)',
      tension: 0.4,
      fill: true
    }]
  };
  
  const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      tooltip: {
        callbacks: {
          label: function(context) {
            return `${context.raw} contribuables`;
          }
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          stepSize: 10
        }
      }
    }
  };
  </script>