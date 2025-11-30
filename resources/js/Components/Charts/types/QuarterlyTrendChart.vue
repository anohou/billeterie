<!-- components/Charts/types/QuarterlyTrendChart.vue -->
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
    labels: ['T1 2023', 'T2 2023', 'T3 2023', 'T4 2023', 'T1 2024'],
    datasets: [{
      label: 'Collections Trimestrielles',
      data: [150000000, 180000000, 160000000, 200000000, 220000000],
      borderColor: 'rgb(34, 197, 94)',
      backgroundColor: 'rgba(34, 197, 94, 0.1)',
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
            return (context.raw / 1000000).toFixed(1) + 'M FCFA';
          }
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: {
          callback: function(value) {
            return (value / 1000000).toFixed(1) + 'M';
          }
        }
      }
    }
  };
  </script>