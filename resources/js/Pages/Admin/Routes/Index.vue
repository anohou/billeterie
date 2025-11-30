<script setup>
import MainNavLayout from '@/Layouts/MainNavLayout.vue'
import SettingsMenu from '@/Components/SettingsMenu.vue'
import { ref, computed, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import Magnify from 'vue-material-design-icons/Magnify.vue'
import Trash2 from 'vue-material-design-icons/Delete.vue'
import Loader from 'vue-material-design-icons/Loading.vue'
import MapMarkerRadius from 'vue-material-design-icons/MapMarkerRadius.vue'
import Cash from 'vue-material-design-icons/Cash.vue'
import Plus from 'vue-material-design-icons/Plus.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue'
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import DialogModal from '@/Components/DialogModal.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'

const props = defineProps({
  routes: {
    type: Object,
    default: () => ({ data: [] })
  },
  stations: {
    type: Array,
    default: () => []
  },
  stops: {
    type: Array,
    default: () => []
  }
});

// State
const search = ref('');
const selectedRoute = ref(null);
const processing = ref(false);
const errors = ref({});

// Modals State
const showRouteModal = ref(false);
const showStopModal = ref(false);
const showFareModal = ref(false);
const isEditingRoute = ref(false);

// Foldable Sections State
const showStops = ref(false);
const showFares = ref(false);

// Forms
const routeForm = ref({
  name: '',
  origin_station_id: '',
  destination_station_id: '',
  active: true
});

const stopForm = ref({
  stop_id: '',
  stop_index: 0
});

const fareForm = ref({
  from_stop_id: '',
  to_stop_id: '',
  amount: ''
});

// Computed
const filteredRoutes = computed(() => {
  const routes = props.routes?.data || [];
  if (!search.value) return routes;

  const searchTerm = search.value.toLowerCase();
  return routes.filter(route =>
    route.name.toLowerCase().includes(searchTerm)
  );
});

// Methods - Route Selection
const isSelected = (route) => {
  if (!selectedRoute.value) return false;
  return selectedRoute.value.id === route.id;
};

const selectRoute = (route) => {
  selectedRoute.value = route;
  showStops.value = false;
  showFares.value = false;
};

// Methods - Route Actions
const openCreateRouteModal = () => {
  isEditingRoute.value = false;
  routeForm.value = {
    name: '',
    origin_station_id: '',
    destination_station_id: '',
    active: true
  };
  errors.value = {};
  showRouteModal.value = true;
};

const openEditRouteModal = () => {
  if (!selectedRoute.value) return;
  isEditingRoute.value = true;
  routeForm.value = {
    name: selectedRoute.value.name,
    origin_station_id: selectedRoute.value.origin_station_id,
    destination_station_id: selectedRoute.value.destination_station_id,
    active: selectedRoute.value.active
  };
  errors.value = {};
  showRouteModal.value = true;
};

const closeRouteModal = () => {
  showRouteModal.value = false;
  routeForm.value = {
    name: '',
    origin_station_id: '',
    destination_station_id: '',
    active: true
  };
  errors.value = {};
};

const submitRoute = () => {
  processing.value = true;
  errors.value = {};

  const url = isEditingRoute.value
    ? route('admin.routes.update', selectedRoute.value.id)
    : route('admin.routes.store');

  const method = isEditingRoute.value ? 'put' : 'post';

  router[method](url, routeForm.value, {
    onSuccess: () => {
      processing.value = false;
      closeRouteModal();
      // If created, we might want to select it, but for now let's just close
    },
    onError: (newErrors) => {
      processing.value = false;
      errors.value = newErrors;
    }
  });
};

const deleteRoute = (id) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette route ?')) {
    router.delete(route('admin.routes.destroy', id), {
      onSuccess: () => {
        if (selectedRoute.value?.id === id) {
          selectedRoute.value = null;
        }
      }
    });
  }
};

// Methods - Stops
const openAddStopModal = () => {
  stopForm.value = { stop_id: '', stop_index: 0 };
  errors.value = {};
  showStopModal.value = true;
};

const closeStopModal = () => {
  showStopModal.value = false;
  stopForm.value = { stop_id: '', stop_index: 0 };
  errors.value = {};
};

const addStop = () => {
  if (!selectedRoute.value) return;
  processing.value = true;
  
  // Auto-calculate index if not set (append to end)
  const nextIndex = (selectedRoute.value.route_stop_orders || selectedRoute.value.routeStopOrders || []).length;
  
  router.post(route('admin.routes.stops.store', selectedRoute.value.id), {
    ...stopForm.value,
    stop_index: nextIndex
  }, {
    preserveScroll: true,
    onSuccess: () => {
      processing.value = false;
      closeStopModal();
    },
    onError: (err) => {
      processing.value = false;
      errors.value = err;
    }
  });
};

const removeStop = (stopOrder) => {
  if (!confirm('Retirer cet arrêt ?')) return;
  router.delete(route('admin.routes.stops.destroy', [selectedRoute.value.id, stopOrder.id]), {
    preserveScroll: true
  });
};

// Methods - Fares
const openAddFareModal = () => {
  fareForm.value = { from_stop_id: '', to_stop_id: '', amount: '' };
  errors.value = {};
  showFareModal.value = true;
};

const closeFareModal = () => {
  showFareModal.value = false;
  fareForm.value = { from_stop_id: '', to_stop_id: '', amount: '' };
  errors.value = {};
};

const addFare = () => {
  if (!selectedRoute.value) return;
  processing.value = true;
  
  router.post(route('admin.route-fares.store'), {
    ...fareForm.value,
    route_id: selectedRoute.value.id
  }, {
    preserveScroll: true,
    onSuccess: () => {
      processing.value = false;
      closeFareModal();
    },
    onError: (err) => {
      processing.value = false;
      errors.value = err;
    }
  });
};

const removeFare = (fareId) => {
  if (!confirm('Supprimer ce tarif ?')) return;
  router.delete(route('admin.route-fares.destroy', fareId), {
    preserveScroll: true
  });
};

// Watchers
watch(() => props.routes, (newRoutes) => {
  if (selectedRoute.value) {
    const updatedRoute = newRoutes.data.find(r => r.id === selectedRoute.value.id);
    if (updatedRoute) {
      selectedRoute.value = updatedRoute;
    }
  }
}, { deep: true });

</script>

<template>
  <MainNavLayout>
    <div class="w-full px-4 h-[calc(100vh-80px)]">
      <!-- Header -->
      <div class="bg-gradient-to-r from-green-50 to-orange-50/30 border-b border-orange-200 px-4 py-2 mb-4">
        <h1 class="text-2xl font-bold text-green-700">Paramètres</h1>
        <p class="mt-1 text-sm text-green-600">Gestion des Routes</p>
      </div>

      <!-- Three Column Layout -->
      <div class="grid grid-cols-12 gap-4 h-full">
        <!-- Left Column - Navigation -->
        <div class="col-span-12 md:col-span-2">
          <SettingsMenu />
        </div>

        <!-- Middle Column - Routes List -->
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
                <button @click="openCreateRouteModal" class="p-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors" title="Nouvelle Route">
                  <Plus class="h-5 w-5" />
                </button>
              </div>
            </div>

            <!-- List Content -->
            <div class="overflow-y-auto flex-1">
              <div v-if="filteredRoutes.length === 0" class="p-4 text-center text-gray-500">
                Aucune route trouvée.
              </div>
              <div v-else>
                <div v-for="routeItem in filteredRoutes" :key="routeItem.id" 
                  @click="selectRoute(routeItem)"
                  class="p-3 cursor-pointer transition-colors"
                  :style="{
                    backgroundColor: isSelected(routeItem) ? '#f0fdf4' : '#ffffff',
                    borderLeft: isSelected(routeItem) ? '4px solid #16a34a' : '4px solid #fed7aa'
                  }"
                >
                  <div class="flex justify-between items-start">
                    <div>
                      <h3 :class="['font-semibold', isSelected(routeItem) ? 'text-green-800' : 'text-gray-800']">{{ routeItem.name }}</h3>
                      <p class="text-xs text-gray-500 mt-1">
                        {{ routeItem.origin_station?.name }} → {{ routeItem.destination_station?.name }}
                      </p>
                    </div>
                    <span :class="[
                      'px-2 py-0.5 rounded-full text-[10px] font-medium',
                      routeItem.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    ]">
                      {{ routeItem.active ? 'Active' : 'Inactive' }}
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
          <div v-if="!selectedRoute" class="bg-white rounded-lg border border-orange-200 shadow-sm p-8 text-center h-full flex flex-col items-center justify-center text-gray-500">
            <MapMarkerRadius class="h-16 w-16 text-orange-200 mb-4" />
            <p class="text-lg">Sélectionnez une route pour voir les détails</p>
            <button @click="openCreateRouteModal" class="mt-4 text-green-600 hover:text-green-700 font-medium">
              ou créez une nouvelle route
            </button>
          </div>

          <!-- View Details & Related -->
          <div v-else class="space-y-4">
            <!-- Route Details Card -->
            <!-- Route Details Card -->
            <div class="bg-white rounded-lg border border-orange-200 shadow-sm p-6">
              <!-- Header Row -->
              <div class="flex justify-between items-start mb-6">
                <h2 class="text-2xl font-bold text-gray-800">{{ selectedRoute.name }}</h2>
                <div class="flex gap-2">
                  <button @click="openEditRouteModal" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Modifier">
                    <Pencil class="h-5 w-5" />
                  </button>
                  <button @click="deleteRoute(selectedRoute.id)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Supprimer">
                    <Trash2 class="h-5 w-5" />
                  </button>
                </div>
              </div>

              <!-- Details Row (6/6 Grid) -->
              <div class="grid grid-cols-12 gap-6 mb-6">
                <div class="col-span-6">
                  <span class="text-xs text-gray-500 uppercase tracking-wider font-bold block mb-2">DÉPART</span>
                  <div class="text-xl font-bold text-gray-900 leading-tight">
                    {{ selectedRoute.origin_station?.name }}
                  </div>
                </div>
                <div class="col-span-6">
                  <span class="text-xs text-gray-500 uppercase tracking-wider font-bold block mb-2">ARRIVÉE</span>
                  <div class="text-xl font-bold text-gray-900 leading-tight">
                    {{ selectedRoute.destination_station?.name }}
                  </div>
                </div>
              </div>

              <!-- Footer Row -->
              <div>
                 <span :class="[
                  'px-3 py-1 rounded-full text-xs font-semibold',
                  selectedRoute.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                ]">
                  {{ selectedRoute.active ? 'Active' : 'Inactive' }}
                </span>
              </div>
            </div>

            <!-- Stops Section -->
            <div class="bg-white rounded-lg border border-orange-200 shadow-sm overflow-hidden">
              <div @click="showStops = !showStops" class="p-3 bg-gray-50 flex items-center justify-between cursor-pointer hover:bg-gray-100">
                <div class="flex items-center gap-2">
                  <MapMarkerRadius class="h-5 w-5 text-orange-500" />
                  <h3 class="font-semibold text-gray-700">
                    Arrêts ({{ (selectedRoute.route_stop_orders || selectedRoute.routeStopOrders || []).length }})
                  </h3>
                </div>
                <div class="flex items-center gap-2">
                    <button @click.stop="openAddStopModal" class="p-1 bg-green-100 text-green-700 rounded hover:bg-green-200" title="Ajouter un arrêt">
                        <Plus class="h-4 w-4" />
                    </button>
                    <component :is="showStops ? ChevronDown : ChevronRight" class="h-5 w-5 text-gray-400" />
                </div>
              </div>
              
              <div v-if="showStops" class="p-4 border-t border-orange-100">
                <div class="space-y-2">
                  <div v-if="(selectedRoute.route_stop_orders || selectedRoute.routeStopOrders || []).length === 0" class="text-sm text-gray-500 text-center py-2">
                    Aucun arrêt configuré.
                  </div>
                  <div v-for="(order, idx) in (selectedRoute.route_stop_orders || selectedRoute.routeStopOrders || [])" :key="order.id" 
                    class="flex items-center justify-between p-2 bg-gray-50 rounded-md border border-gray-100">
                    <div class="flex items-center gap-3">
                      <span class="w-6 h-6 flex items-center justify-center bg-orange-100 text-orange-800 text-xs font-bold rounded-full">
                        {{ idx + 1 }}
                      </span>
                      <div>
                        <p class="text-sm font-medium text-gray-800">{{ order.stop?.name }}</p>
                        <p class="text-xs text-gray-500">{{ order.stop?.station?.city || 'Ville inconnue' }}</p>
                      </div>
                    </div>
                    <button @click="removeStop(order)" class="text-red-400 hover:text-red-600">
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Fares Section -->
            <div class="bg-white rounded-lg border border-orange-200 shadow-sm overflow-hidden">
              <div @click="showFares = !showFares" class="p-3 bg-gray-50 flex items-center justify-between cursor-pointer hover:bg-gray-100">
                <div class="flex items-center gap-2">
                  <Cash class="h-5 w-5 text-green-600" />
                  <h3 class="font-semibold text-gray-700">
                    Tarifs ({{ (selectedRoute.route_fares || selectedRoute.routeFares || []).length }})
                  </h3>
                </div>
                <div class="flex items-center gap-2">
                    <button @click.stop="openAddFareModal" class="p-1 bg-green-100 text-green-700 rounded hover:bg-green-200" title="Ajouter un tarif">
                        <Plus class="h-4 w-4" />
                    </button>
                    <component :is="showFares ? ChevronDown : ChevronRight" class="h-5 w-5 text-gray-400" />
                </div>
              </div>
              
              <div v-if="showFares" class="p-4 border-t border-orange-100">
                <div class="space-y-2">
                  <div v-if="(selectedRoute.route_fares || selectedRoute.routeFares || []).length === 0" class="text-sm text-gray-500 text-center py-2">
                    Aucun tarif configuré.
                  </div>
                  <div v-for="fare in (selectedRoute.route_fares || selectedRoute.routeFares || [])" :key="fare.id" 
                    class="flex items-center justify-between p-2 bg-gray-50 rounded-md border border-gray-100">
                    <div class="text-sm">
                      <span class="font-medium">{{ fare.from_stop?.name }}</span>
                      <span class="text-gray-400 mx-1">→</span>
                      <span class="font-medium">{{ fare.to_stop?.name }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                      <span class="font-bold text-green-700">{{ fare.amount }} FCFA</span>
                      <button @click="removeFare(fare.id)" class="text-red-400 hover:text-red-600">
                        <Trash2 class="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Route Modal -->
    <DialogModal :show="showRouteModal" @close="closeRouteModal">
      <template #title>
        {{ isEditingRoute ? 'Modifier la Route' : 'Nouvelle Route' }}
      </template>
      <template #content>
        <div class="space-y-4">
          <div>
            <InputLabel for="name" value="Nom de la route" />
            <TextInput v-model="routeForm.name" id="name" class="w-full" />
            <InputError :message="errors.name" />
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <InputLabel for="origin" value="Départ" />
              <select v-model="routeForm.origin_station_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
                <option value="">Choisir...</option>
                <option v-for="s in stations" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
              <InputError :message="errors.origin_station_id" />
            </div>
            <div>
              <InputLabel for="dest" value="Arrivée" />
              <select v-model="routeForm.destination_station_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
                <option value="">Choisir...</option>
                <option v-for="s in stations" :key="s.id" :value="s.id">{{ s.name }}</option>
              </select>
              <InputError :message="errors.destination_station_id" />
            </div>
          </div>

          <div class="flex items-center">
            <input type="checkbox" v-model="routeForm.active" id="active" class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500">
            <label for="active" class="ml-2 text-sm text-gray-600">Route Active</label>
          </div>
        </div>
      </template>
      <template #footer>
        <SecondaryButton @click="closeRouteModal">Annuler</SecondaryButton>
        <PrimaryButton class="ml-3" @click="submitRoute" :disabled="processing">
          {{ isEditingRoute ? 'Mettre à jour' : 'Enregistrer' }}
        </PrimaryButton>
      </template>
    </DialogModal>

    <!-- Stop Modal -->
    <DialogModal :show="showStopModal" @close="closeStopModal">
      <template #title>Ajouter un Arrêt</template>
      <template #content>
        <div>
          <InputLabel for="stop" value="Sélectionner un arrêt" />
          <select v-model="stopForm.stop_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500 mt-1">
            <option value="">Choisir...</option>
            <option v-for="stop in stops" :key="stop.id" :value="stop.id">
              {{ stop.name }} ({{ stop.city }})
            </option>
          </select>
          <InputError :message="errors.stop_id" class="mt-2" />
        </div>
      </template>
      <template #footer>
        <SecondaryButton @click="closeStopModal">Annuler</SecondaryButton>
        <PrimaryButton class="ml-3" @click="addStop" :disabled="processing">Ajouter</PrimaryButton>
      </template>
    </DialogModal>

    <!-- Fare Modal -->
    <DialogModal :show="showFareModal" @close="closeFareModal">
      <template #title>Ajouter un Tarif</template>
      <template #content>
        <div class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <InputLabel for="from" value="De" />
              <select v-model="fareForm.from_stop_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
                <option value="">Choisir...</option>
                <option v-for="stop in stops" :key="stop.id" :value="stop.id">{{ stop.name }}</option>
              </select>
              <InputError :message="errors.from_stop_id" />
            </div>
            <div>
              <InputLabel for="to" value="À" />
              <select v-model="fareForm.to_stop_id" class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
                <option value="">Choisir...</option>
                <option v-for="stop in stops" :key="stop.id" :value="stop.id">{{ stop.name }}</option>
              </select>
              <InputError :message="errors.to_stop_id" />
            </div>
          </div>
          <div>
            <InputLabel for="amount" value="Montant (FCFA)" />
            <TextInput v-model="fareForm.amount" type="number" class="w-full" />
            <InputError :message="errors.amount" />
          </div>
        </div>
      </template>
      <template #footer>
        <SecondaryButton @click="closeFareModal">Annuler</SecondaryButton>
        <PrimaryButton class="ml-3" @click="addFare" :disabled="processing">Ajouter</PrimaryButton>
      </template>
    </DialogModal>

  </MainNavLayout>
</template>
