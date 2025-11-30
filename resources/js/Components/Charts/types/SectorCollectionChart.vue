<!-- components/Charts/types/SectorCollectionChart.vue -->
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
    labels: ['Marché', 'Transport', 'Commerce', 'Services', 'Industrie'],
    datasets: [{
      label: 'Montant Collecté',
      data: [35000000, 25000000, 20000000, 15000000, 5000000],
      backgroundColor: 'rgba(251, 146, 60, 0.6)',
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