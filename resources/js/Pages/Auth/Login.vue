<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    users: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const getRoleColor = (role) => {
    switch(role) {
        case 'admin': return 'bg-red-500';
        case 'supervisor': return 'bg-blue-500';
        case 'seller': return 'bg-green-500';
        default: return 'bg-gray-500';
    }
};

const fillCredentials = (user) => {
    form.email = user.email;
    form.password = 'password';
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Login Form -->
            <div class="flex-1">
                <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                    {{ status }}
                </div>

                <form @submit.prevent="submit">
                    <div>
                        <InputLabel for="email" value="Email" />

                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                        />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="mt-4">
                        <InputLabel for="password" value="Password" />

                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                        />

                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="mt-4 block">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" />
                            <span class="ms-2 text-sm text-gray-600"
                                >Remember me</span
                            >
                        </label>
                    </div>

                    <div class="mt-4 flex items-center justify-end">
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Forgot your password?
                        </Link>

                        <PrimaryButton
                            class="ms-4"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Log in
                        </PrimaryButton>
                    </div>
                </form>
            </div>

            <!-- Users Panel -->
            <div class="lg:w-64 border-t lg:border-t-0 lg:border-l border-gray-200 pt-6 lg:pt-0 lg:pl-8">
                <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <span class="inline-block w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    Utilisateurs Actifs
                </h3>
                <div class="space-y-2 max-h-[60vh] overflow-y-auto pr-1">
                    <button
                        v-for="user in users"
                        :key="user.id"
                        type="button"
                        @click="fillCredentials(user)"
                        class="w-full text-left p-3 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-all duration-150 group"
                    >
                        <div class="flex items-center gap-3">
                            <span :class="[getRoleColor(user.role), 'w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold uppercase shrink-0']">
                                {{ user.name.charAt(0) }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-sm text-gray-900 truncate">{{ user.name }}</div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium capitalize"
                                          :class="{
                                              'bg-red-100 text-red-700': user.role === 'admin',
                                              'bg-blue-100 text-blue-700': user.role === 'supervisor',
                                              'bg-green-100 text-green-700': user.role === 'seller'
                                          }">
                                        {{ user.role }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </button>
                    <div v-if="users.length === 0" class="text-sm text-gray-500 italic text-center py-4">
                        Aucun utilisateur trouvé.
                    </div>
                </div>
                <p class="mt-4 text-xs text-gray-400 italic">
                    Cliquez pour remplir l'email
                </p>
            </div>
        </div>
    </GuestLayout>
</template>
