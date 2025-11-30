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
import CashMultiple from 'vue-material-design-icons/CashMultiple.vue';

const props = defineProps({
  fares: {
    type: Array,
    default: () => []
  },
  routes: {
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
const selectedFare = ref(null);
const processing = ref(false);
const errors = ref({});
const showModal = ref(false);
const isEditing = ref(false);

const form = ref({
  route_id: '',
  from_stop_id: '',
  to_stop_id: '',
  amount: ''
});

// Computed
const filteredFares = computed(() => {
  if (!search.value) return props.fares;

  const searchTerm = search.value.toLowerCase();
  return props.fares.filter(fare =>
    fare.route?.name.toLowerCase().includes(searchTerm) ||
    fare.from_stop?.name.toLowerCase().includes(searchTerm) ||
    fare.to_stop?.name.toLowerCase().includes(searchTerm)
  );
});

// Watchers
watch(() => props.fares, (newFares) => {
  if (selectedFare.value) {
    const updatedFare = newFares.find(f => f.id === selectedFare.value.id);
    if (updatedFare) {
      selectedFare.value = updatedFare;
    }
  }
}, { deep: true });

// Methods
const isSelected = (fare) => {
  if (!selectedFare.value) return false;
  return selectedFare.value.id === fare.id;
};

const selectFare = (fare) => {
  selectedFare.value = fare;
};

const openCreateModal = () => {
  isEditing.value = false;
  form.value = {
    route_id: '',
    from_stop_id: '',
    to_stop_id: '',
    amount: ''
  };
  errors.value = {};
  showModal.value = true;
};

const openEditModal = () => {
  if (!selectedFare.value) return;
  isEditing.value = true;
  form.value = {
    route_id: selectedFare.value.route_id,
    from_stop_id: selectedFare.value.from_stop_id,
    to_stop_id: selectedFare.value.to_stop_id,
    amount: selectedFare.value.amount
  };
  errors.value = {};
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.value = {
    route_id: '',
    from_stop_id: '',
    to_stop_id: '',
    amount: ''
  };
  errors.value = {};
};

const submit = () => {
  processing.value = true;
  errors.value = {};

  if (isEditing.value) {
    router.put(route('admin.route-fares.update', selectedFare.value.id), form.value, {
      onSuccess: () => {
        processing.value = false;
        closeModal();
      },
      onError: (newErrors) => {
        processing.value = false;
        errors.value = newErrors;
      }
    });
  } else {
    router.post(route('admin.route-fares.store'), form.value, {
      onSuccess: () => {
        processing.value = false;
        closeModal();
      },
      onError: (newErrors) => {
        processing.value = false;
        errors.value = newErrors;
      }
    });
  }
};

const deleteFare = (id) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer ce tarif ?')) {
    router.delete(route('admin.route-fares.destroy', id), {
      onSuccess: () => {
        if (selectedFare.value?.id === id) {
          selectedFare.value = null;
        }
      },
      onError: (errorResponse) => {
        alert('Impossible de supprimer ce tarif.');
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
        <p class="mt-1 text-sm text-green-600">Gestion des Tarifs</p>
      </div>

      <!-- Three Column Layout -->
      <div class="grid grid-cols-12 gap-4 h-full">
        <!-- Left Column - Navigation -->
        <div class="col-span-12 md:col-span-2">
          <SettingsMenu />
        </div>

        <!-- Middle Column - List -->
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
                <button @click="openCreateModal" class="p-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors" title="Nouveau Tarif">
                  <Plus class="h-5 w-5" />
                </button>
              </div>
            </div>

            <!-- List Content -->
            <div class="overflow-y-auto flex-1">
              <div v-if="filteredFares.length === 0" class="p-4 text-center text-gray-500">
                Aucun tarif trouvé.
              </div>
              <div v-else>
                <div v-for="fare in filteredFares" :key="fare.id" 
                  @click="selectFare(fare)"
                  class="p-3 cursor-pointer transition-colors"
                  :style="{
                    backgroundColor: isSelected(fare) ? '#f0fdf4' : '#ffffff',
                    borderLeft: isSelected(fare) ? '4px solid #16a34a' : '4px solid #fed7aa'
                  }"
                >
                  <div class="flex justify-between items-start">
                    <div>
                      <h3 :class="['font-semibold', isSelected(fare) ? 'text-green-800' : 'text-gray-800']">{{ fare.route?.name }}</h3>
                      <p class="text-xs text-gray-500 mt-1">{{ fare.from_stop?.name }} → {{ fare.to_stop?.name }}</p>
                    </div>
                    <span class="font-bold text-green-700">{{ fare.amount }} FCFA</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Workspace -->
        <div class="col-span-12 md:col-span-6 h-full overflow-y-auto pb-20">
          <!-- Empty State -->
          <div v-if="!selectedFare" class="bg-white rounded-lg border border-orange-200 shadow-sm p-8 text-center h-full flex flex-col items-center justify-center text-gray-500">
            <CashMultiple class="h-16 w-16 text-orange-200 mb-4" />
            <p class="text-lg">Sélectionnez un tarif pour voir les détails</p>
            <button @click="openCreateModal" class="mt-4 text-green-600 hover:text-green-700 font-medium">
              ou créez un nouveau tarif
            </button>
          </div>

          <!-- View Details -->
          <div v-else class="space-y-4">
            <!-- Details Card -->
            <div class="bg-white rounded-lg border border-orange-200 shadow-sm p-6">
              <!-- Header Row -->
              <div class="flex justify-between items-start mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Détails du Tarif</h2>
                <div class="flex gap-2">
                  <button @click="openEditModal" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Modifier">
                    <Pencil class="h-5 w-5" />
                  </button>
                  <button @click="deleteFare(selectedFare.id)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Supprimer">
                    <Trash2 class="h-5 w-5" />
                  </button>
                </div>
              </div>

              <!-- Details Row -->
              <div class="grid grid-cols-12 gap-6 mb-6">
                <div class="col-span-12">
                  <span class="text-xs text-gray-500 uppercase tracking-wider font-bold block mb-2">ROUTE</span>
                  <div class="text-xl font-bold text-gray-900 leading-tight">
                    {{ selectedFare.route?.name }}
                  </div>
                </div>
                <div class="col-span-6">
                  <span class="text-xs text-gray-500 uppercase tracking-wider font-bold block mb-2">DÉPART</span>
                  <div class="text-lg font-medium text-gray-900 leading-tight">
                    {{ selectedFare.from_stop?.name }}
                  </div>
                </div>
                <div class="col-span-6">
                  <span class="text-xs text-gray-500 uppercase tracking-wider font-bold block mb-2">ARRIVÉE</span>
                  <div class="text-lg font-medium text-gray-900 leading-tight">
                    {{ selectedFare.to_stop?.name }}
                  </div>
                </div>
                <div class="col-span-12">
                  <span class="text-xs text-gray-500 uppercase tracking-wider font-bold block mb-2">MONTANT</span>
                  <div class="text-3xl font-bold text-green-700">
                    {{ selectedFare.amount.toLocaleString() }} FCFA
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
        {{ isEditing ? 'Modifier le Tarif' : 'Nouveau Tarif' }}
      </template>
      <template #content>
        <div class="space-y-4">
          <div>
            <InputLabel for="route_id" value="Route" />
            <select v-model="form.route_id" id="route_id"
              class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
              :class="{ 'border-red-500': errors.route_id }">
              <option value="">Sélectionner une route</option>
              <option v-for="route in routes" :key="route.id" :value="route.id">
                {{ route.name }}
              </option>
            </select>
            <InputError :message="errors.route_id" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <InputLabel for="from_stop_id" value="Départ" />
              <select v-model="form.from_stop_id" id="from_stop_id"
                class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                :class="{ 'border-red-500': errors.from_stop_id }">
                <option value="">Sélectionner un arrêt</option>
                <option v-for="stop in stops" :key="stop.id" :value="stop.id">
                  {{ stop.name }}
                </option>
              </select>
              <InputError :message="errors.from_stop_id" />
            </div>

            <div>
              <InputLabel for="to_stop_id" value="Arrivée" />
              <select v-model="form.to_stop_id" id="to_stop_id"
                class="w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                :class="{ 'border-red-500': errors.to_stop_id }">
                <option value="">Sélectionner un arrêt</option>
                <option v-for="stop in stops" :key="stop.id" :value="stop.id">
                  {{ stop.name }}
                </option>
              </select>
              <InputError :message="errors.to_stop_id" />
            </div>
          </div>

          <div>
            <InputLabel for="amount" value="Montant (FCFA)" />
            <TextInput v-model="form.amount" id="amount" type="number" placeholder="Ex: 5000"
              :class="{ 'border-red-500': errors.amount }" />
            <InputError :message="errors.amount" />
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
