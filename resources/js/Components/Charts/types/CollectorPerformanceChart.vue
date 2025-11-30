<!-- components/Charts/types/CollectorPerformanceChart.vue -->
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
    labels: ['AMANI K.', 'AMOAKON A.', 'KOUAME N.', 'TRAORE M.', 'NANDIE K.'],
    datasets: [{
      label: 'Collections',
      data: [12500000, 9800000, 8700000, 7900000, 7200000],
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