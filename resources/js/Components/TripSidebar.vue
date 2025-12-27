<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Bus from 'vue-material-design-icons/Bus.vue';
import Clock from 'vue-material-design-icons/Clock.vue';
import MapMarker from 'vue-material-design-icons/MapMarker.vue';
import Seat from 'vue-material-design-icons/Seat.vue';
import ChevronRight from 'vue-material-design-icons/ChevronRight.vue';
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue';
import Refresh from 'vue-material-design-icons/Refresh.vue';
import VehicleSeatMapSVG from '@/Components/VehicleSeatMapSVG.vue';
import { ticketingStore } from '@/Stores/ticketingStore.js';

const props = defineProps({
    initialSelectedTripId: {
        type: [String, Number],
        default: null
    }
});

const trips = ref([]);
const loading = ref(false);
const selectedTripId = ref(props.initialSelectedTripId);
const seatMap = ref(null);
const seatMapLoading = ref(false);

const page = usePage();
const isTicketingPage = computed(() => route().current('seller.ticketing'));

const emit = defineEmits(['seat-click']);

const fetchTrips = async () => {
    loading.value = true;
    try {
        const response = await axios.get(route('trips.index'));
        // Filter for today or future trips
        const now = new Date().setHours(0, 0, 0, 0);
        trips.value = response.data.filter(trip => {
            const departure = new Date(trip.departure_at).getTime();
            return departure >= now;
        });
    } catch (error) {
        console.error("Erreur lors de la récupération des voyages:", error);
    } finally {
        loading.value = false;
    }
};

const fetchSeatMap = async (tripId) => {
    if (!tripId) return;
    seatMapLoading.value = true;
    
    // If on ticketing page, we might need stop filters from the parent
    // For now, fetch standard seat map
    try {
        const response = await axios.get(route('trips.seatmap', { trip: tripId }));
        // Keep the whole object to stay consistent with Ticketing.vue
        seatMap.value = response.data;
    } catch (error) {
        console.error("Erreur lors de la récupération du plan de salle:", error);
        seatMap.value = null;
    } finally {
        seatMapLoading.value = false;
    }
};

const selectTrip = (trip) => {
    if (selectedTripId.value === trip.id) {
        // Only deselect if not on ticketing page
        if (!isTicketingPage.value) {
            selectedTripId.value = null;
            seatMap.value = null;
        }
    } else {
        selectedTripId.value = trip.id;
        fetchSeatMap(trip.id);
        
        // If on ticketing page, notify parent to sync
        if (isTicketingPage.value) {
            router.visit(route('seller.ticketing', { trip_id: trip.id }), {
                preserveState: true,
                preserveScroll: true,
                replace: true
            });
        }
    }
};

const handleSeatClick = (seat) => {
    if (isTicketingPage.value) {
        ticketingStore.selectSeat(seat.number);
        emit('seat-click', seat);
    }
};

const formatTime = (dateString) => {
    return new Date(dateString).toLocaleTimeString('fr-FR', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

onMounted(() => {
    fetchTrips();
});

watch(() => ticketingStore.selectedTripId, (newId) => {
    if (newId && newId !== selectedTripId.value) {
        selectedTripId.value = newId;
        fetchSeatMap(newId);
    }
}, { immediate: true });

watch(() => {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('trip_id');
}, (newId) => {
    if (newId && newId !== selectedTripId.value) {
        selectedTripId.value = newId;
        fetchSeatMap(newId);
    }
}, { immediate: true });

// Refresh every 30 seconds for real-time-ish feel
let refreshInterval;
onMounted(() => {
    refreshInterval = setInterval(fetchTrips, 30000);
});

import { onUnmounted } from 'vue';
onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});

// Stats for the selected trip
const seatStats = computed(() => {
    if (!seatMap.value || !seatMap.value.seat_map) return null;
    return {
        total: seatMap.value.total_seats || 0,
        occupied: seatMap.value.occupied_seats_count || seatMap.value.occupied_seats || 0,
        available: seatMap.value.available_seats_count || seatMap.value.available_seats || 0
    };
});
</script>

<template>
    <div class="flex flex-col h-full bg-white border-l border-orange-100 overflow-hidden shadow-xl w-[320px]">
        <!-- Header -->
        <div class="p-5 bg-gradient-to-br from-green-50 to-orange-50/30 border-b border-orange-50 flex items-center justify-between shrink-0">
            <div>
                <h2 class="text-base font-black text-gray-800 flex items-center gap-2">
                    <Bus :size="20" class="text-green-600" />
                    Voyages
                </h2>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">Plan & Occupations</p>
            </div>
            <button @click="fetchTrips" :disabled="loading" class="p-2 hover:bg-white rounded-xl shadow-sm border border-transparent hover:border-orange-100 transition-all text-gray-400 hover:text-green-600 disabled:opacity-50">
                <Refresh :size="18" :class="{ 'animate-spin': loading }" />
            </button>
        </div>

        <!-- Trips List -->
        <div class="flex-1 overflow-y-auto p-3 space-y-3">
            <div v-if="loading && trips.length === 0" class="flex flex-col items-center justify-center py-10 text-gray-400">
                <div class="animate-spin mb-2"><Refresh :size="32" /></div>
                <span>Chargement des voyages...</span>
            </div>

            <div v-else-if="trips.length === 0" class="text-center py-10 text-gray-500 italic px-4">
                Aucun voyage disponible pour le moment.
            </div>

            <div v-else v-for="trip in trips" :key="trip.id" 
                class="border-2 rounded-2xl overflow-hidden transition-all duration-300"
                :class="selectedTripId === trip.id ? 'border-green-500 shadow-lg scale-[1.02]' : 'border-transparent bg-gray-50 hover:border-orange-200 hover:bg-white hover:shadow-md'"
            >
                <!-- Trip Summary Header -->
                <div @click="selectTrip(trip)" 
                    class="p-4 cursor-pointer flex items-center justify-between gap-3"
                    :class="selectedTripId === trip.id ? 'bg-green-50/50' : ''"
                >
                    <div class="p-2 bg-white rounded-xl shadow-sm flex-shrink-0">
                        <Bus :size="20" :class="selectedTripId === trip.id ? 'text-green-600' : 'text-gray-400'" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <div class="text-sm font-bold text-gray-900 truncate tracking-tight">{{ trip.route?.name }}</div>
                            <span 
                                :title="trip.sales_control === 'open' ? 'Ventes intermédiaires autorisées' : 'Ventes origine uniquement'"
                                class="text-xs shrink-0"
                            >{{ trip.sales_control === 'open' ? '🔓' : '🔒' }}</span>
                            <span v-if="selectedTripId === trip.id" class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></span>
                        </div>
                        <div class="flex items-center gap-3 mt-0.5">
                            <span class="text-[10px] font-black text-orange-600 uppercase tracking-widest">
                                {{ trip.vehicle?.identifier }}
                            </span>
                            <span class="text-[10px] font-bold text-gray-400">
                                {{ formatTime(trip.departure_at) }}
                            </span>
                        </div>
                    </div>
                    <component :is="selectedTripId === trip.id ? ChevronDown : ChevronRight" :size="18" class="text-gray-400" />
                </div>

                <!-- Expanded Content (Seat Map) -->
                <div v-if="selectedTripId === trip.id" class="p-3 border-t border-green-100 bg-white">
                    <!-- Loading State for Seat Map -->
                    <div v-if="seatMapLoading" class="flex flex-col items-center justify-center py-8">
                        <div class="animate-spin mb-2"><Refresh :size="24" class="text-green-600" /></div>
                        <span class="text-xs text-gray-500">Chargement du plan...</span>
                    </div>

                    <!-- Seat Map and Stats -->
                    <div v-else-if="seatMap">
                        <!-- Stats Mini Grid -->
                        <div class="grid grid-cols-3 gap-2 mb-4">
                            <div class="bg-white p-2 rounded-xl text-center border border-orange-100 shadow-sm">
                                <div class="text-[9px] uppercase font-bold text-gray-400 tracking-tighter">Cap</div>
                                <div class="text-base font-black text-gray-900 leading-tight">{{ seatStats.total }}</div>
                            </div>
                            <div class="bg-white p-2 rounded-xl text-center border border-orange-100 shadow-sm">
                                <div class="text-[9px] uppercase font-bold text-red-600 tracking-tighter">Occ</div>
                                <div class="text-base font-black text-red-700 leading-tight">{{ seatStats.occupied }}</div>
                            </div>
                            <div class="bg-white p-2 rounded-xl text-center border border-orange-100 shadow-sm">
                                <div class="text-[9px] uppercase font-bold text-green-600 tracking-tighter">Lib</div>
                                <div class="text-base font-black text-green-700 leading-tight">{{ seatStats.available }}</div>
                            </div>
                        </div>

                        <!-- Interactive Seat Map -->
                        <div class="bg-gray-50 rounded-xl p-2 border border-gray-100 relative overflow-hidden flex items-center justify-center h-[350px]">
                            <div class="w-full h-full flex items-center justify-center p-2">
                                <VehicleSeatMapSVG 
                                    :seat-map="seatMap"
                                    :vehicle-type="trip.vehicle?.vehicle_type"
                                    :suggested-seats="ticketingStore.suggestedSeats"
                                    :selected-seat="ticketingStore.selectedSeat"
                                    :selected-color="ticketingStore.selectedFareColor"
                                    :show-suggestions="ticketingStore.showSuggestions"
                                    @seat-click="handleSeatClick"
                                    class="max-w-full max-h-full"
                                />
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button 
                                @click="router.visit(route('seller.ticketing', { trip_id: trip.id }))"
                                class="w-full bg-green-600 text-white text-xs font-bold py-2 rounded-lg hover:bg-green-700 transition-all shadow-md active:scale-95 flex items-center justify-center gap-2"
                            >
                                <Seat :size="14" />
                                Vendre sur ce voyage
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer Info -->
        <div class="p-3 bg-gray-50 border-t border-orange-100 text-[10px] text-gray-400 text-center">
            Vue actualisée automatiquement
        </div>
    </div>
</template>

<style scoped>
/* Translucent scrollbar for modern feel */
div::-webkit-scrollbar {
    width: 4px;
}
div::-webkit-scrollbar-track {
    background: transparent;
}
div::-webkit-scrollbar-thumb {
    background: rgba(234, 88, 12, 0.1);
    border-radius: 10px;
}
div:hover::-webkit-scrollbar-thumb {
    background: rgba(234, 88, 12, 0.2);
}
</style>
