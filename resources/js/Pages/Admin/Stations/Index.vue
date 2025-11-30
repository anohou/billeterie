<script setup>
import { ref, computed, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import SettingsMenu from '@/Components/SettingsMenu.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextArea from '@/Components/TextArea.vue';
import DialogModal from '@/Components/DialogModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

import MainNavLayout from '@/Layouts/MainNavLayout.vue';
import Magnify from 'vue-material-design-icons/Magnify.vue';
import Trash2 from 'vue-material-design-icons/Delete.vue';
import Pencil from 'vue-material-design-icons/Pencil.vue';
import Plus from 'vue-material-design-icons/Plus.vue';
import MapMarkerRadius from 'vue-material-design-icons/MapMarkerRadius.vue';

const props = defineProps({
  stations: {
    type: Object,
    default: () => ({ data: [] })
  }
});

// State
const search = ref('');
const selectedStation = ref(null);
const processing = ref(false);
const errors = ref({});
const showModal = ref(false);
const isEditing = ref(false);

const form = ref({
  code: '',
  name: '',
  city: '',
  address: '',
  active: true
});

// Computed
const filteredStations = computed(() => {
  const stations = props.stations?.data || [];
  if (!search.value) return stations;

  const searchTerm = search.value.toLowerCase();
  return stations.filter(station =>
    station.name.toLowerCase().includes(searchTerm) ||
    station.code.toLowerCase().includes(searchTerm) ||
    station.city.toLowerCase().includes(searchTerm)
  );
});

// Watchers
watch(() => props.stations, (newStations) => {
  if (selectedStation.value) {
    const updatedStation = newStations.data.find(s => s.id === selectedStation.value.id);
    if (updatedStation) {
      selectedStation.value = updatedStation;
    }
  }
}, { deep: true });

// Methods
const isSelected = (station) => {
  if (!selectedStation.value) return false;
  return selectedStation.value.id === station.id;
};

const selectStation = (station) => {
  selectedStation.value = station;
};

const openCreateModal = () => {
  isEditing.value = false;
  form.value = {
    code: '',
    name: '',
    city: '',
    address: '',
    active: true
  };
  errors.value = {};
  showModal.value = true;
};

const openEditModal = () => {
  if (!selectedStation.value) return;
  isEditing.value = true;
  form.value = {
    code: selectedStation.value.code,
    name: selectedStation.value.name,
    city: selectedStation.value.city,
    address: selectedStation.value.address || '',
    active: selectedStation.value.active
  };
  errors.value = {};
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.value = {
    code: '',
    name: '',
    city: '',
    address: '',
    active: true
  };
  errors.value = {};
};

const submit = () => {
  processing.value = true;
  errors.value = {};

  const url = isEditing.value
    ? route('admin.stations.update', selectedStation.value.id)
    : route('admin.stations.store');

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

const deleteStation = (id) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette station ?')) {
    router.delete(route('admin.stations.destroy', id), {
      onSuccess: () => {
        if (selectedStation.value?.id === id) {
          selectedStation.value = null;
        }
      },
      onError: (errorResponse) => {
        let errorMessage = 'Impossible de supprimer cette station.';
        if (errorResponse.message) {
          errorMessage = errorResponse.message;
        } else if (errorResponse.error) {
          errorMessage = errorResponse.error;
        }
        alert(errorMessage);
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
        <p class="mt-1 text-sm text-green-600">Gestion des Stations</p>
      </div>

      <!-- Three Column Layout -->
      <div class="grid grid-cols-12 gap-4 h-full">
        <!-- Left Column - Navigation -->
        <div class="col-span-12 md:col-span-2">
          <SettingsMenu />
        </div>

        <!-- Middle Column - Stations List -->
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
                <button @click="openCreateModal" class="p-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors" title="Nouvelle Station">
                  <Plus class="h-5 w-5" />
                </button>
              </div>
            </div>

            <!-- List Content -->
            <div class="overflow-y-auto flex-1">
              <div v-if="filteredStations.length === 0" class="p-4 text-center text-gray-500">
                Aucune station trouvée.
              </div>
              <div v-else>
                <div v-for="station in filteredStations" :key="station.id" 
                  @click="selectStation(station)"
                  class="p-3 cursor-pointer transition-colors"
                  :style="{
                    backgroundColor: isSelected(station) ? '#f0fdf4' : '#ffffff',
                    borderLeft: isSelected(station) ? '4px solid #16a34a' : '4px solid #fed7aa'
                  }"
                >
                  <div class="flex justify-between items-start">
                    <div>
                      <h3 :class="['font-semibold', isSelected(station) ? 'text-green-800' : 'text-gray-800']">{{ station.name }}</h3>
                      <p class="text-xs text-gray-500 mt-1">
                        {{ station.city }} ({{ station.code }})
                      </p>
                    </div>
                    <span :class="[
                      'px-2 py-0.5 rounded-full text-[10px] font-medium',
                      station.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    ]">
                      {{ station.active ? 'Active' : 'Inactive' }}
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
          <div v-if="!selectedStation" class="bg-white rounded-lg border border-orange-200 shadow-sm p-8 text-center h-full flex flex-col items-center justify-center text-gray-500">
            <MapMarkerRadius class="h-16 w-16 text-orange-200 mb-4" />
            <p class="text-lg">Sélectionnez une station pour voir les détails</p>
            <button @click="openCreateModal" class="mt-4 text-green-600 hover:text-green-700 font-medium">
              ou créez une nouvelle station
            </button>
          </div>

          <!-- View Details -->
          <div v-else class="space-y-4">
            <!-- Details Card -->
            <div class="bg-white rounded-lg border border-orange-200 shadow-sm p-6">
              <!-- Header Row -->
              <div class="flex justify-between items-start mb-6">
                <h2 class="text-2xl font-bold text-gray-800">{{ selectedStation.name }}</h2>
                <div class="flex gap-2">
                  <button @click="openEditModal" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Modifier">
                    <Pencil class="h-5 w-5" />
                  </button>
                  <button @click="deleteStation(selectedStation.id)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Supprimer">
                    <Trash2 class="h-5 w-5" />
                  </button>
                </div>
              </div>

              <!-- Details Row -->
              <div class="grid grid-cols-12 gap-6 mb-6">
                <div class="col-span-6">
                  <span class="text-xs text-gray-500 uppercase tracking-wider font-bold block mb-2">CODE</span>
                  <div class="text-xl font-bold text-gray-900 leading-tight">
                    {{ selectedStation.code }}
                  </div>
                </div>
                <div class="col-span-6">
                  <span class="text-xs text-gray-500 uppercase tracking-wider font-bold block mb-2">VILLE</span>
                  <div class="text-xl font-bold text-gray-900 leading-tight">
                    {{ selectedStation.city }}
                  </div>
                </div>
                <div class="col-span-12">
                  <span class="text-xs text-gray-500 uppercase tracking-wider font-bold block mb-2">ADRESSE</span>
                  <div class="text-base text-gray-700">
                    {{ selectedStation.address || 'Non renseignée' }}
                  </div>
                </div>
              </div>

              <!-- Footer Row -->
              <div>
                 <span :class="[
                  'px-3 py-1 rounded-full text-xs font-semibold',
                  selectedStation.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                ]">
                  {{ selectedStation.active ? 'Active' : 'Inactive' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <DialogModal :show="showModal" @close="closeModal">
      <template #title>
        {{ isEditing ? 'Modifier la Station' : 'Nouvelle Station' }}
      </template>
      <template #content>
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <InputLabel for="code" value="Code (unique)" />
              <TextInput v-model="form.code" id="code" class="w-full" placeholder="Ex: ABJ" />
              <InputError :message="errors.code" />
            </div>
            <div>
              <InputLabel for="city" value="Ville" />
              <TextInput v-model="form.city" id="city" class="w-full" placeholder="Ex: Abidjan" />
              <InputError :message="errors.city" />
            </div>
          </div>

          <div>
            <InputLabel for="name" value="Nom de la station" />
            <TextInput v-model="form.name" id="name" class="w-full" placeholder="Ex: Gare Nord" />
            <InputError :message="errors.name" />
          </div>

          <div>
            <InputLabel for="address" value="Adresse" />
            <TextArea v-model="form.address" id="address" rows="2" class="w-full" placeholder="Adresse complète" />
            <InputError :message="errors.address" />
          </div>

          <div class="flex items-center">
            <input type="checkbox" v-model="form.active" id="active" class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500">
            <label for="active" class="ml-2 text-sm text-gray-600">Station Active</label>
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
