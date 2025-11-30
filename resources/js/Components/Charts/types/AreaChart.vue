// resources/js/Components/Charts/AreaChart.vue
<template>
    <div class="w-full">
        <canvas ref="chartRef"></canvas>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

const props = defineProps({
    data: {
        type: Array,
        required: true
    },
    label: {
        type: String,
        default: 'Data'
    }
});

const chartRef = ref(null);
let chart = null;

const createChart = () => {
    const ctx = chartRef.value.getContext('2d');
    
    if (chart) {
        chart.destroy();
    }

    chart = new Chart(ctx, {
        type: 'line', // We'll use line type but with fill
        data: {
            labels: props.data.map(item => item.name),
            datasets: [{
                label: props.label,
                data: props.data.map(item => item.value),
                fill: true, // This makes it an area chart
                borderColor: '#f97316',
                backgroundColor: 'rgba(249, 115, 22, 0.1)', // Light orange with opacity
                tension: 0.3, // Adds some curve to the line
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
};

onMounted(() => {
    createChart();
});

watch(() => props.data, () => {
    createChart();
}, { deep: true });

// Clean up on component unmount
onUnmounted(() => {
    if (chart) {
        chart.destroy();
    }
});
</script>