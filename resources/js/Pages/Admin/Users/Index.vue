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
import Account from 'vue-material-design-icons/Account.vue';

const props = defineProps({
  users: {
    type: Object,
    default: () => ({ data: [] })
  }
});

// State
const search = ref('');
const selectedUser = ref(null);
const processing = ref(false);
const errors = ref({});
const showModal = ref(false);
const isEditing = ref(false);

const form = ref({
  name: '',
  email: '',
  telephone: '',
  role: 'seller',
  password: '',
  password_confirmation: ''
});

// Computed
const filteredUsers = computed(() => {
  const users = props.users?.data || [];
  if (!search.value) return users;

  const searchTerm = search.value.toLowerCase();
  return users.filter(user =>
    user.name.toLowerCase().includes(searchTerm) ||
    user.email.toLowerCase().includes(searchTerm) ||
    user.telephone?.toLowerCase().includes(searchTerm) ||
    user.role.toLowerCase().includes(searchTerm)
  );
});

// Watchers
watch(() => props.users, (newUsers) => {
  if (selectedUser.value) {
    const updatedUser = newUsers.data.find(u => u.id === selectedUser.value.id);
    if (updatedUser) {
      selectedUser.value = updatedUser;
    }
  }
}, { deep: true });

// Methods
const isSelected = (user) => {
  if (!selectedUser.value) return false;
  return selectedUser.value.id === user.id;
};

const selectUser = (user) => {
  selectedUser.value = user;
};

const openCreateModal = () => {
  isEditing.value = false;
  form.value = {
    name: '',
    email: '',
    telephone: '',
    role: 'seller',
    password: '',
    password_confirmation: ''
  };
  errors.value = {};
  showModal.value = true;
};

const openEditModal = () => {
  if (!selectedUser.value) return;
  isEditing.value = true;
  form.value = {
    name: selectedUser.value.name,
    email: selectedUser.value.email,
    telephone: selectedUser.value.telephone || '',
    role: selectedUser.value.role,
    password: '',
    password_confirmation: ''
  };
  errors.value = {};
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.value = {
    name: '',
    email: '',
    telephone: '',
    role: 'seller',
    password: '',
    password_confirmation: ''
  };
  errors.value = {};
};

const submit = () => {
  processing.value = true;
  errors.value = {};

  const url = isEditing.value
    ? route('admin.users.update', selectedUser.value.id)
    : route('admin.users.store');

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

const deleteUser = (id) => {
  if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
    router.delete(route('admin.users.destroy', id), {
      onSuccess: () => {
        if (selectedUser.value?.id === id) {
          selectedUser.value = null;
        }
      },
    });
  }
};

const getRoleLabel = (role) => {
  const labels = {
    admin: 'Administrateur',
    supervisor: 'Superviseur',
    seller: 'Vendeur'
  };
  return labels[role] || role;
};

const getRoleColor = (role) => {
  const colors = {
    admin: 'bg-red-100 text-red-800',
    supervisor: 'bg-blue-100 text-blue-800',
    seller: 'bg-green-100 text-green-800'
  };
  return colors[role] || 'bg-gray-100 text-gray-800';
};
</script>

<template>
  <MainNavLayout>
    <div class="w-full px-4 h-[calc(100vh-80px)]">
      <!-- Header -->
      <div class="bg-gradient-to-r from-green-50 to-orange-50/30 border-b border-orange-200 px-4 py-2 mb-4">
        <h1 class="text-2xl font-bold text-green-700">Paramètres</h1>
        <p class="mt-1 text-sm text-green-600">Gestion des Utilisateurs</p>
      </div>

      <!-- Three Column Layout -->
      <div class="grid grid-cols-12 gap-4 h-full">
        <!-- Left Column - Navigation -->
        <div class="col-span-12 md:col-span-2">
          <SettingsMenu />
        </div>

        <!-- Middle Column - Users List -->
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
                <button @click="openCreateModal" class="p-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors" title="Nouvel Utilisateur">
                  <Plus class="h-5 w-5" />
                </button>
              </div>
            </div>

            <!-- List Content -->
            <div class="overflow-y-auto flex-1">
              <div v-if="filteredUsers.length === 0" class="p-4 text-center text-gray-500">
                Aucun utilisateur trouvé.
              </div>
              <div v-else>
                <div v-for="user in filteredUsers" :key="user.id" 
                  @click="selectUser(user)"
                  class="p-3 cursor-pointer transition-colors"
                  :style="{
                    backgroundColor: isSelected(user) ? '#f0fdf4' : '#ffffff',
                    borderLeft: isSelected(user) ? '4px solid #16a34a' : '4px solid #fed7aa'
                  }"
                >
                  <div class="flex justify-between items-start">
                    <div>
                      <h3 :class="['font-semibold', isSelected(user) ? 'text-green-800' : 'text-gray-800']">{{ user.name }}</h3>
                      <p class="text-xs text-gray-500 mt-1">{{ user.email }}</p>
                    </div>
                    <span :class="[
                      'px-2 py-0.5 rounded-full text-[10px] font-medium',
                      user.role === 'admin' ? 'bg-purple-100 text-purple-800' : 
                      user.role === 'supervisor' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
                    ]">
                      {{ user.role }}
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
          <div v-if="!selectedUser" class="bg-white rounded-lg border border-orange-200 shadow-sm p-8 text-center h-full flex flex-col items-center justify-center text-gray-500">
            <Account class="h-16 w-16 text-orange-200 mb-4" />
            <p class="text-lg">Sélectionnez un utilisateur pour voir les détails</p>
            <button @click="openCreateModal" class="mt-4 text-green-600 hover:text-green-700 font-medium">
              ou créez un nouvel utilisateur
            </button>
          </div>

          <!-- View Details -->
          <div v-else class="space-y-4">
            <!-- Details Card -->
            <div class="bg-white rounded-lg border border-orange-200 shadow-sm p-6">
              <!-- Header Row -->
              <div class="flex justify-between items-start mb-6">
                <h2 class="text-2xl font-bold text-gray-800">{{ selectedUser.name }}</h2>
                <div class="flex gap-2">
                  <button @click="openEditModal" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Modifier">
                    <Pencil class="h-5 w-5" />
                  </button>
                  <button @click="deleteUser(selectedUser.id)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Supprimer">
                    <Trash2 class="h-5 w-5" />
                  </button>
                </div>
              </div>

              <!-- Details Row -->
              <div class="grid grid-cols-12 gap-6 mb-6">
                <div class="col-span-6">
                  <span class="text-xs text-gray-500 uppercase tracking-wider font-bold block mb-2">EMAIL</span>
                  <div class="text-lg font-medium text-gray-900 break-all">
                    {{ selectedUser.email }}
                  </div>
                </div>
                <div class="col-span-6">
                  <span class="text-xs text-gray-500 uppercase tracking-wider font-bold block mb-2">TÉLÉPHONE</span>
                  <div class="text-lg font-medium text-gray-900">
                    {{ selectedUser.telephone || 'Non renseigné' }}
                  </div>
                </div>
                <div class="col-span-12">
                  <span class="text-xs text-gray-500 uppercase tracking-wider font-bold block mb-2">RÔLE</span>
                  <div>
                    <span :class="[
                      'inline-flex items-center px-3 py-1 rounded-full text-sm font-medium',
                      getRoleColor(selectedUser.role)
                    ]">
                      {{ getRoleLabel(selectedUser.role) }}
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
        {{ isEditing ? 'Modifier l\'Utilisateur' : 'Nouvel Utilisateur' }}
      </template>
      <template #content>
        <div class="space-y-4">
          <div>
            <InputLabel for="name" value="Nom complet" />
            <TextInput v-model="form.name" id="name" class="w-full" placeholder="Ex: Jean Dupont" />
            <InputError :message="errors.name" />
          </div>

          <div>
            <InputLabel for="email" value="Adresse email" />
            <TextInput v-model="form.email" id="email" type="email" class="w-full" placeholder="Ex: jean.dupont@example.com" />
            <InputError :message="errors.email" />
          </div>

          <div>
            <InputLabel for="telephone" value="Numéro de téléphone" />
            <TextInput v-model="form.telephone" id="telephone" type="tel" class="w-full" placeholder="Ex: 06 12 34 56 78" />
            <InputError :message="errors.telephone" />
          </div>

          <div>
            <InputLabel for="role" value="Rôle" />
            <select
              id="role"
              v-model="form.role"
              class="w-full px-3 py-1.5 border border-orange-200 rounded-lg focus:border-green-500 focus:ring-green-500 text-sm"
              required
            >
              <option value="seller">Vendeur</option>
              <option value="supervisor">Superviseur</option>
              <option value="admin">Administrateur</option>
            </select>
            <InputError :message="errors.role" />
          </div>

          <div class="border-t border-gray-100 pt-4 mt-4">
            <h3 class="text-sm font-medium text-gray-900 mb-3">Sécurité</h3>
            <div class="space-y-4">
              <div>
                <InputLabel for="password" :value="isEditing ? 'Nouveau mot de passe (optionnel)' : 'Mot de passe'" />
                <TextInput v-model="form.password" id="password" type="password" class="w-full"
                  :placeholder="isEditing ? 'Laisser vide pour ne pas changer' : 'Minimum 8 caractères'" />
                <InputError :message="errors.password" />
              </div>

              <div v-if="form.password">
                <InputLabel for="password_confirmation" value="Confirmer le mot de passe" />
                <TextInput v-model="form.password_confirmation" id="password_confirmation" type="password" class="w-full" 
                  placeholder="Répéter le mot de passe" />
                <InputError :message="errors.password_confirmation" />
              </div>
            </div>
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
