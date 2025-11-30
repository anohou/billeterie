<script setup>
import MainNavLayout from '@/Layouts/MainNavLayout.vue'
</script>

<template>
  <MainNavLayout>
    <div class="w-full px-4">
      <!-- Header -->
      <div class="bg-gradient-to-r from-green-50 to-orange-50/30 border-b border-orange-200 px-4 py-2 mb-4">
        <h1 class="text-2xl font-bold text-green-700">Tableau de bord Superviseur</h1>
        <p class="mt-1 text-sm text-green-600">Supervision des opérations</p>
      </div>

      <!-- Content -->
      <div class="grid gap-6 md:grid-cols-2">
        <div class="bg-white rounded-lg shadow p-6 border border-orange-200">
            <h2 class="text-lg font-semibold text-green-700 mb-4">Performance des Stations</h2>
            <p class="text-gray-500">Contenu à venir...</p>
        </div>
        <div class="bg-white rounded-lg shadow p-6 border border-orange-200">
            <h2 class="text-lg font-semibold text-green-700 mb-4">Classement des Vendeurs</h2>
            <p class="text-gray-500">Contenu à venir...</p>
        </div>
      </div>
    </div>
  </MainNavLayout>
</template>
