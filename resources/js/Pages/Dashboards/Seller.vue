<script setup>
import MainNavLayout from '@/Layouts/MainNavLayout.vue'
import { ref } from 'vue'

const props = defineProps({
    trips: Array
})
</script>

<template>
  <MainNavLayout>
    <div class="w-full px-4">
      <!-- Header -->
      <div class="bg-gradient-to-r from-green-50 to-orange-50/30 border-b border-orange-200 px-4 py-2 mb-4">
        <h1 class="text-2xl font-bold text-green-700">Tableau de bord Vendeur</h1>
        <p class="mt-1 text-sm text-green-600">Gestion des ventes de tickets</p>
      </div>

      <!-- Content Cards -->
      <div class="grid gap-6 md:grid-cols-2">
        <!-- Today's Sales Card -->
        <div class="bg-white rounded-lg shadow p-6 border border-orange-200">
          <div class="flex items-center mb-4">
            <div class="p-2 bg-green-100 rounded-lg mr-3">
              <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
              </svg>
            </div>
            <h2 class="text-lg font-semibold text-green-700">Ventes d'aujourd'hui</h2>
          </div>
          <div class="text-center py-8">
            <div class="text-3xl font-bold text-gray-400 mb-2">Bientôt disponible</div>
            <p class="text-gray-500">Les statistiques de vente seront affichées ici</p>
          </div>
        </div>

        <!-- Available Seats Card -->
        <div class="bg-white rounded-lg shadow p-6 border border-orange-200">
          <div class="flex items-center mb-4">
            <div class="p-2 bg-orange-100 rounded-lg mr-3">
              <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
              </svg>
            </div>
            <h2 class="text-lg font-semibold text-green-700">Places disponibles</h2>
          </div>
          <div class="text-center py-8">
            <div class="text-gray-500 mb-2">Sélectionnez un voyage et des arrêts</div>
            <p class="text-gray-400">pour voir la carte des places</p>
          </div>
        </div>
      </div>

      <!-- Trips Section -->
      <div class="mt-6 bg-white rounded-lg shadow border border-orange-200">
        <div class="border-b border-orange-200 p-4 bg-gradient-to-r from-green-50 to-orange-50/30">
          <h3 class="text-lg font-semibold text-green-700">Voyages disponibles</h3>
        </div>
        <div class="p-4">
          <div v-if="trips && trips.length > 0" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div v-for="trip in trips" :key="trip.id" class="border border-orange-200 rounded-lg p-4 hover:bg-green-50 transition-colors">
              <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-green-700">{{ trip.route?.name }}</span>
                <span class="text-xs bg-orange-100 text-orange-700 px-2 py-1 rounded-full">
                  {{ trip.vehicle?.identifier }}
                </span>
              </div>
              <div class="text-sm text-gray-600 mb-2">
                Départ: {{ new Date(trip.departure_at).toLocaleTimeString() }}
              </div>
              <button class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors text-sm">
                Vendre des tickets
              </button>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            Aucun voyage disponible pour le moment
          </div>
        </div>
      </div>
    </div>
  </MainNavLayout>
</template>
