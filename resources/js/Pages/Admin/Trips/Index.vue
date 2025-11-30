<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SettingsMenu from '@/Components/SettingsMenu.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

import MainNavLayout from '@/Layouts/MainNavLayout.vue';
import Magnify from 'vue-material-design-icons/Magnify.vue';
import Trash2 from 'vue-material-design-icons/Delete.vue';
import Pencil from 'vue-material-design-icons/Pencil.vue';
import Plus from 'vue-material-design-icons/Plus.vue';
import MapClock from 'vue-material-design-icons/MapClock.vue';

const props = defineProps({
  trips: {
    type: Object,
    default: () => ({ data: [] })
  },
  routes: {
    type: Array,
    default: () => []
  },
  vehicles: {
    type: Array,
    default: () => []
  }
});

// State
const search = ref('');
const selectedTrip = ref(null);
const processing = ref(false);
const errors = ref({});
const showModal = ref(false);
const isEditing = ref(false);

const form = ref({
  route_id: '',
  vehicle_id: '',
  departure_at: '',
  active: true
});

// Computed
const filteredTrips = computed(() => {
  const trips = props.trips?.data || [];
  if (!search.value) return trips;

  const searchTerm = search.value.toLowerCase();
  return trips.filter(trip =>
    trip.route?.name.toLowerCase().includes(searchTerm) ||
    trip.vehicle?.identifier.toLowerCase().includes(searchTerm)
  );
});

// Watchers
watch(() => props.trips, (newTrips) => {
  if (selectedTrip.value) {
    const updatedTrip = newTrips.data.find(t => t.id === selectedTrip.value.id);
    if (updatedTrip) {
      selectedTrip.value = updatedTrip;
    }
  }
}, { deep: true });

// Methods
const formatDate = (dateString) => {
  if (!dateString) return '';
  return new Date(dateString).toLocaleString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const isSelected = (trip) => {
  if (!selectedTrip.value) return false;
  return selectedTrip.value.id === trip.id;
};

const selectTrip = (trip) => {
  selectedTrip.value = trip;
};

const openCreateModal = () => {
  isEditing.value = false;
  form.value = {
    route_id: '',
    vehicle_id: '',
    departure_at: '',
    active: true
  };
  errors.value = {};
  showModal.value = true;
};

const openEditModal = () => {
  if (!selectedTrip.value) return;
  isEditing.value = true;
  form.value = {
    route_id: selectedTrip.value.route_id,
    vehicle_id: selectedTrip.value.vehicle_id,
    departure_at: selectedTrip.value.departure_at.slice(0, 16), // Format for datetime-local input
    active: selectedTrip.value.active
  };
  errors.value = {};
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.value = {
    route_id: '',
    vehicle_id: '',
    departure_at: '',
    active: true
  };
  errors.value = {};
};

const submit = () => {
  processing.value = true;
  errors.value = {};

  const url = isEditing.value
    ? route('admin.trips.update', selectedTrip.value.id)
    : route('admin.trips.store');

  const method = isEditing.value ? 'put' : 'post';

  router[method](url, form.value, {
    onSuccess: () => {
      processing.value = false;
      closeModal();
    },
    onError: (newErrors) => {
      processing.value = false;
      errors.value = newErrors;
    }
  });
};

const deleteTrip = (id) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce voyage ?')) {
    router.delete(route('admin.trips.destroy', id), {
      onSuccess: () => {
        if (selectedTrip.value?.id === id) {
          selectedTrip.value = null;
        }
      },
      onError: (errorResponse) => {
        console.error('Error deleting trip:', errorResponse);
      }
    });
  }
};
</script>

<template>
  <MainNavLayout>
    <div class="w-full px-4 h-[calc(100vh-80px)]">
      <!-- Header -->
      <div class="bg-gradient-to-r from-green-50 to-orange-50/30 border-b border-orange-200 px-4 py-2 mb-4">
        <h1 class="text-2xl font-bold text-green-700">Paramètres</h1>
        <p class="mt-1 text-sm text-green-600">Gestion des Voyages</p>
      </div>

      <!-- Three Column Layout -->
      <div class="grid grid-cols-12 gap-4 h-full">
        <!-- Left Column - Navigation -->
        <div class="col-span-12 md:col-span-2">
          <SettingsMenu />
        </div>

        <!-- Middle Column - Trips List -->
        <div class="col-span-12 md:col-span-4 flex flex-col h-full">
          <div class="bg-white rounded-lg border border-orange-200 shadow-sm flex flex-col h-full">
            <!-- List Header -->
            <div class="border-b border-orange-200 p-3 bg-gradient-to-r from-green-50 to-orange-50/30">
              <div class="flex items-center justify-between gap-2">
                <div class="relative flex-1">
                  <input type="text" v-model="search" placeholder="Rechercher..."
                    class="w-full px-4 py-2 pl-10 pr-4 border border-orange-200 rounded-lg focus:outline-none focus:border-orange-400 text-sm" />
                  <Magnify class="absolute left-3 top-2.5 h-4 w-4 text-orange-400" />
                </div>
                <button @click="openCreateModal" class="p-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors" title="Nouveau Voyage">
                  <Plus class="h-5 w-5" />
                </button>
              </div>
            </div>

            <!-- List Content -->
            <div class="overflow-y-auto flex-1">
              <div v-if="filteredTrips.length === 0" class="p-4 text-center text-gray-500">
                Aucun voyage trouvé.
              </div>
              <div v-else>
                <div v-for="trip in filteredTrips" :key="trip.id" 
                  @click="selectTrip(trip)"
                  class="p-3 cursor-pointer transition-colors"
                  :style="{
                    backgroundColor: isSelected(trip) ? '#f0fdf4' : '#ffffff',
                    borderLeft: isSelected(trip) ? '4px solid #16a34a' : '4px solid #fed7aa'
                  }"
                >
                  <div class="flex justify-between items-start">
                    <div>
                      <h3 :class="['font-semibold', isSelected(trip) ? 'text-green-800' : 'text-gray-800']">{{ trip.route?.name }}</h3>
                      <p class="text-xs text-gray-500 mt-1">{{ formatDate(trip.departure_time) }}</p>
                    </div>
                    <span :class="[
                      'px-2 py-0.5 rounded-full text-[10px] font-medium',
                      trip.status === 'scheduled' ? 'bg-blue-100 text-blue-800' : 
                      trip.status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                    ]">
                      {{ trip.status }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Workspace -->
        <div class="col-span-12 md:col-span-6 h-full overflow-y-auto pb-20">
          <!-- Empty State -->
          <div v-if="!selectedTrip" class="bg-white rounded-lg border border-orange-200 shadow-sm p-8 text-center h-full flex flex-col items-center justify-center text-gray-500">
            <MapClock class="h-16 w-16 text-orange-200 mb-4" />
            <p class="text-lg">Sélectionnez un voyage pour voir les détails</p>
            <button @click="openCreateModal" class="mt-4 text-green-600 hover:text-green-700 font-medium">
              ou créez un nouveau voyage
            </button>
          </div>

          <!-- View Details -->
          <div v-else class="space-y-4">
            <!-- Details Card -->
            <div class="bg-white rounded-lg border border-orange-200 shadow-sm p-6">
              <!-- Header Row -->
              <div class="flex justify-between items-start mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Détails du Voyage</h2>
                <div class="flex gap-2">
                  <button @click="openEditModal" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Modifier">
                    <Pencil class="h-5 w-5" />
                  </button>
                  <button @click="deleteTrip(selectedTrip.id)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Supprimer">
                    <Trash2 class="h-5 w-5" />
                  </button>
                </div>
              </div>

              <!-- Details Row -->
              <div class="grid grid-cols-12 gap-6 mb-6">
                <div class="col-span-6">
                  <span class="text-xs text-gray-500 uppercase tracking-wider font-bold block mb-2">ROUTE</span>
                  <div class="text-xl font-bold text-gray-900 leading-tight">
                    {{ selectedTrip.route?.name }}
                  </div>
                </div>
                <div class="col-span-6">
                  <span class="text-xs text-gray-500 uppercase tracking-wider font-bold block mb-2">VÉHICULE</span>
                  <div class="text-xl font-bold text-gray-900 leading-tight">
                    {{ selectedTrip.vehicle?.identifier }}
                  </div>
                </div>
                <div class="col-span-6">
                  <span class="text-xs text-gray-500 uppercase tracking-wider font-bold block mb-2">DÉPART</span>
                  <div class="text-xl font-bold text-gray-900 leading-tight">
                    {{ formatDate(selectedTrip.departure_at) }}
                  </div>
                </div>
                <div class="col-span-6">
                  <span class="text-xs text-gray-500 uppercase tracking-wider font-bold block mb-2">STATUT</span>
                  <div>
                    <span :class="[
                      'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                      selectedTrip.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    ]">
                      {{ selectedTrip.active ? 'Actif' : 'Inactif' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <DialogModal :show="showModal" @close="closeModal">
      <template #title>
        {{ isEditing ? 'Modifier le Voyage' : 'Nouveau Voyage' }}
      </template>
      <template #content>
        <div class="space-y-4">
          <div>
            <InputLabel for="route_id" value="Route" />
            <select
              id="route_id"
              v-model="form.route_id"
              class="w-full px-3 py-1.5 border border-orange-200 rounded-lg focus:border-green-500 focus:ring-green-500 text-sm"
              required
            >
              <option value="">Sélectionner une route</option>
              <option
                v-for="route in routes"
                :key="route.id"
                :value="route.id"
              >
                {{ route.name }}
              </option>
            </select>
            <InputError :message="errors.route_id" />
          </div>

          <div>
            <InputLabel for="vehicle_id" value="Véhicule" />
            <select
              id="vehicle_id"
              v-model="form.vehicle_id"
              class="w-full px-3 py-1.5 border border-orange-200 rounded-lg focus:border-green-500 focus:ring-green-500 text-sm"
              required
            >
              <option value="">Sélectionner un véhicule</option>
              <option
                v-for="vehicle in vehicles"
                :key="vehicle.id"
                :value="vehicle.id"
              >
                {{ vehicle.identifier }}
              </option>
            </select>
            <InputError :message="errors.vehicle_id" />
          </div>

          <div>
            <InputLabel for="departure_at" value="Date et Heure de Départ" />
            <TextInput v-model="form.departure_at" id="departure_at" type="datetime-local" class="w-full" />
            <InputError :message="errors.departure_at" />
          </div>

          <div class="flex items-center">
            <input
              id="active"
              v-model="form.active"
              type="checkbox"
              class="rounded border-orange-200 text-green-600 shadow-sm focus:ring-green-500"
            />
            <InputLabel for="active" value="Actif" class="ml-2" />
          </div>
        </div>
      </template>
      <template #footer>
        <SecondaryButton @click="closeModal">Annuler</SecondaryButton>
        <PrimaryButton class="ml-3" @click="submit" :disabled="processing">
          {{ isEditing ? 'Mettre à jour' : 'Enregistrer' }}
        </PrimaryButton>
      </template>
    </DialogModal>
  </MainNavLayout>
</template>
