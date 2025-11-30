<script setup>
import MainNavLayout from '@/Layouts/MainNavLayout.vue'
import { ref, onMounted } from 'vue'
import Chart from 'chart.js/auto'

const props = defineProps({
    links: Array,
    stats: Object,
    charts: Object
})

const salesChartRef = ref(null)
const routesChartRef = ref(null)
const occupancyChartRef = ref(null)

onMounted(() => {
    // Sales Trend Chart
    if (salesChartRef.value && props.charts.salesTrend) {
        new Chart(salesChartRef.value, {
            type: 'line',
            data: {
                labels: props.charts.salesTrend.map(item => item.date),
                datasets: [{
                    label: 'Daily Sales',
                    data: props.charts.salesTrend.map(item => item.count),
                    borderColor: '#059669',
                    backgroundColor: 'rgba(5, 150, 105, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        })
    }

    // Top Routes Chart
    if (routesChartRef.value && props.charts.topRoutes) {
        new Chart(routesChartRef.value, {
            type: 'doughnut',
            data: {
                labels: props.charts.topRoutes.map(item => item.name),
                datasets: [{
                    data: props.charts.topRoutes.map(item => item.trips),
                    backgroundColor: [
                        '#059669',
                        '#ea580c',
                        '#3b82f6',
                        '#8b5cf6',
                        '#ec4899'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        })
    }

    // Vehicle Occupancy Chart
    if (occupancyChartRef.value && props.charts.vehicleOccupancy) {
        new Chart(occupancyChartRef.value, {
            type: 'bar',
            data: {
                labels: props.charts.vehicleOccupancy.map(item => item.name),
                datasets: [{
                    label: 'Occupancy Rate (%)',
                    data: props.charts.vehicleOccupancy.map(item => item.occupancy),
                    backgroundColor: '#059669',
                    borderColor: '#047857',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        })
    }
})
</script>

<template>
  <MainNavLayout>
    <div class="w-full px-4">
      <!-- Header -->
      <div class="bg-gradient-to-r from-green-50 to-orange-50/30 border-b border-orange-200 px-4 py-2 mb-4">
        <h1 class="text-2xl font-bold text-green-700">Tableau de bord</h1>
        <p class="mt-1 text-sm text-green-600">Vue d'ensemble du système de transport</p>
      </div>

      <!-- Statistics Cards -->
      <div class='grid gap-6 md:grid-cols-2 lg:grid-cols-4 mb-6'>
        <div class='bg-white rounded-lg shadow p-6 border-l-4 border-green-500'>
          <div class='flex items-center'>
            <div class='p-2 bg-green-100 rounded-lg'>
              <svg class='w-6 h-6 text-green-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'></path>
              </svg>
            </div>
            <div class='ml-4'>
              <p class='text-sm font-medium text-gray-600'>Total Ventes</p>
              <p class='text-2xl font-semibold text-gray-900'>{{ stats.totalSales }}</p>
            </div>
          </div>
          <div class='mt-4'>
            <span class='text-sm text-green-600'>Aujourd'hui: {{ stats.todaySales }}</span>
          </div>
        </div>

        <div class='bg-white rounded-lg shadow p-6 border-l-4 border-orange-500'>
          <div class='flex items-center'>
            <div class='p-2 bg-orange-100 rounded-lg'>
              <svg class='w-6 h-6 text-orange-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1'></path>
              </svg>
            </div>
            <div class='ml-4'>
              <p class='text-sm font-medium text-gray-600'>Revenus</p>
              <p class='text-2xl font-semibold text-gray-900'>{{ stats.totalRevenue.toLocaleString() }} FCFA</p>
            </div>
          </div>
          <div class='mt-4'>
            <span class='text-sm text-orange-600'>Aujourd'hui: {{ stats.todayRevenue.toLocaleString() }} FCFA</span>
          </div>
        </div>

        <div class='bg-white rounded-lg shadow p-6 border-l-4 border-blue-500'>
          <div class='flex items-center'>
            <div class='p-2 bg-blue-100 rounded-lg'>
              <svg class='w-6 h-6 text-blue-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'></path>
              </svg>
            </div>
            <div class='ml-4'>
              <p class='text-sm font-medium text-gray-600'>Voyages Actifs</p>
              <p class='text-2xl font-semibold text-gray-900'>{{ stats.activeTrips }}</p>
            </div>
          </div>
          <div class='mt-4'>
            <span class='text-sm text-blue-600'>Total Véhicules: {{ stats.totalVehicles }}</span>
          </div>
        </div>

        <div class='bg-white rounded-lg shadow p-6 border-l-4 border-purple-500'>
          <div class='flex items-center'>
            <div class='p-2 bg-purple-100 rounded-lg'>
              <svg class='w-6 h-6 text-purple-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'></path>
              </svg>
            </div>
            <div class='ml-4'>
              <p class='text-sm font-medium text-gray-600'>Utilisateurs</p>
              <p class='text-2xl font-semibold text-gray-900'>{{ stats.totalUsers }}</p>
            </div>
          </div>
          <div class='mt-4'>
            <span class='text-sm text-purple-600'>Vendeurs: {{ stats.sellers }} | Superviseurs: {{ stats.supervisors }}</span>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class='grid gap-6 lg:grid-cols-2 mb-6'>
        <!-- Sales Trend Chart -->
        <div class='bg-white rounded-lg shadow p-6'>
          <h3 class='text-lg font-semibold text-gray-900 mb-4'>Tendance des Ventes (7 derniers jours)</h3>
          <canvas ref='salesChartRef' height='200'></canvas>
        </div>

        <!-- Top Routes Chart -->
        <div class='bg-white rounded-lg shadow p-6'>
          <h3 class='text-lg font-semibold text-gray-900 mb-4'>Routes Populaires</h3>
          <canvas ref='routesChartRef' height='200'></canvas>
        </div>
      </div>

      <!-- Vehicle Occupancy Chart -->
      <div class='mb-6'>
        <div class='bg-white rounded-lg shadow p-6'>
          <h3 class='text-lg font-semibold text-gray-900 mb-4'>Taux d\'Occupation des Véhicules</h3>
          <canvas ref='occupancyChartRef' height='100'></canvas>
        </div>
      </div>

      <!-- Configuration Section -->
      <div class='bg-white rounded-lg shadow p-6'>
        <div class='mb-4'>
          <h3 class='text-lg font-semibold text-gray-900'>Configuration Rapide</h3>
          <p class='text-sm text-gray-600'>Gérez les paramètres de votre système de transport</p>
        </div>
        <div class='grid gap-4 md:grid-cols-2 lg:grid-cols-3'>
          <a v-for="link in links" :key="link.href"
             class='flex items-center p-4 rounded-lg border border-gray-200 hover:border-green-300 hover:bg-green-50 transition-colors'
             :href="link.href">
            <div class='p-2 bg-green-100 rounded-lg mr-3'>
              <svg class='w-5 h-5 text-green-600' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924-1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'></path>
                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'></path>
              </svg>
            </div>
            <span class='font-medium text-gray-900'>{{ link.label }}</span>
          </a>
        </div>
      </div>
    </div>
  </MainNavLayout>
</template>
