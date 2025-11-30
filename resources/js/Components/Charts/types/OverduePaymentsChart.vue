<!-- components/Charts/types/OverduePaymentsChart.vue -->
<template>
    <Doughnut :data="chartData" :options="chartOptions" />
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
    labels: ['À jour', '1-30 jours', '31-60 jours', '60+ jours'],
    datasets: [{
        data: [70, 15, 10, 5],
        backgroundColor: [
            'rgba(34, 197, 94, 0.6)',
            'rgba(251, 146, 60, 0.6)',
            'rgba(239, 68, 68, 0.6)',
            'rgba(239, 68, 68, 0.8)'
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
                label: function (context) {
                    return `${context.label}: ${context.raw}%`;
                }
            }
        }
    }
};
</script>