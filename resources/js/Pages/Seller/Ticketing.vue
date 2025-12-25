<script setup>
import { ref, computed, watch, nextTick, onMounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import MainNavLayout from '@/Layouts/MainNavLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import VehicleSeatMapSVG from '@/Components/VehicleSeatMapSVG.vue';
import Bus from 'vue-material-design-icons/Bus.vue';
import Calendar from 'vue-material-design-icons/Calendar.vue';
import Clock from 'vue-material-design-icons/Clock.vue';
import MapMarker from 'vue-material-design-icons/MapMarker.vue';
import Ticket from 'vue-material-design-icons/Ticket.vue';
import Cash from 'vue-material-design-icons/Cash.vue';
import Check from 'vue-material-design-icons/Check.vue';
import Printer from 'vue-material-design-icons/Printer.vue';
import Close from 'vue-material-design-icons/Close.vue';
import Seat from 'vue-material-design-icons/Seat.vue';
import Routes from 'vue-material-design-icons/Routes.vue';
import ChevronDown from 'vue-material-design-icons/ChevronDown.vue';
import SwapHorizontal from 'vue-material-design-icons/SwapHorizontal.vue';
import Bluetooth from 'vue-material-design-icons/Bluetooth.vue';
import axios from 'axios';
import BluetoothPrinter from '@/Services/BluetoothPrinter.js';

const props = defineProps({
  trips: Array,
  routeFares: Array,
  routes: Array,
  vehicles: Array,
});

// Get page props for auth user
const page = usePage();

// State
const trips = ref([...props.trips]); // Reactive copy for real-time updates
const selectedTripId = ref(null);
const selectedFare = ref(null);
const ticketQuantity = ref(1);
const searchQuery = ref('');
const seatMap = ref(null);
const seatMapLoading = ref(false);
const suggestedSeats = ref([]);
const bookingType = ref(null);
const occupancyStats = ref(null);
const processing = ref(false);
const errors = ref({});
const showCreateTripModal = ref(false);
const createTripForm = ref({
  route_id: '',
  vehicle_id: '',
  departure_at: '',
  status: 'scheduled'
});
const createTripErrors = ref({});
const createTripProcessing = ref(false);
const showZoomModal = ref(false);
const autoSelectOptimal = ref(true); // Auto-select optimal seat by default
const showPassengerFields = ref(false); // Hide passenger fields by default
const isMobile = ref(window.innerWidth < 768);

// Update isMobile on resize
onMounted(() => {
  window.addEventListener('resize', () => {
    isMobile.value = window.innerWidth < 768;
  });

  // Listen for real-time trip additions
  if (page.props.auth.user.station_assignments) {
    page.props.auth.user.station_assignments.forEach(assignment => {
      Echo.private(`station.${assignment.station_id}`)
          .listen('TripCreated', (e) => {
              // Check if trip already exists
              if (!trips.value.find(t => t.id === e.trip.id)) {
                  // Add new trip to list
                  trips.value.unshift(e.trip);
                  // Optional: Show notification
              }
          });
    });
  }
});
const showTripSelectionModal = ref(false); // Modal for selecting a trip

// Bluetooth Printer state
const bluetoothPrinter = new BluetoothPrinter();
const useBluetoothPrinter = ref(localStorage.getItem('use_bluetooth_printer') === 'true');
const bluetoothPrinterConnected = ref(false);
const bluetoothPrinterName = ref(null);

// Zoom and Pan state for Places section
const zoomLevel = ref(1);
const panX = ref(0);
const panY = ref(0);
const isDragging = ref(false);
const dragStartX = ref(0);
const dragStartY = ref(0);

// Passenger form modal
const showPassengerModal = ref(false);
const selectedSeatNumber = ref(null);
const selectedSeatInfo = computed(() => {
  if (!selectedSeatNumber.value || !seatMap.value) return null;
  
  // Find seat info from seat map
  for (const row of seatMap.value.seat_map) {
    const seat = row.find(s => s.number === selectedSeatNumber.value);
    if (seat) return seat;
  }
  return null;
});

const selectedSeatSuggestion = computed(() => {
  if (!selectedSeatNumber.value || !suggestedSeats.value) return null;
  return suggestedSeats.value.find(s => s.seat_number === selectedSeatNumber.value);
});
const passengerForm = ref({
  name: '',
  phone: ''
});
const passengerFormErrors = ref({});

// Computed
const currentTrip = computed(() => {
  return trips.value.find(trip => trip.id === selectedTripId.value);
});

const filteredTrips = computed(() => {
  if (!searchQuery.value) return trips.value;
  const query = searchQuery.value.toLowerCase();
  return trips.value.filter(trip => 
    trip.route?.name?.toLowerCase().includes(query) ||
    trip.vehicle?.identifier?.toLowerCase().includes(query)
  );
});

const availableFares = computed(() => {
    if (!currentTrip.value) return [];
    return props.routeFares.filter(fare => fare.route_id === currentTrip.value.route_id);
});

// Seat statistics computed from seatMap response
const seatStats = computed(() => {
  if (!seatMap.value) {
    return { total: 0, sold: 0, available: 0 };
  }
  // Use the API response fields directly
  const total = seatMap.value.total_seats || 0;
  const sold = seatMap.value.occupied_seats || seatMap.value.occupied_seats_count || 0;
  const available = seatMap.value.available_seats || seatMap.value.available_seats_count || (total - sold);
  return { total, sold, available };
});

const totalAmount = computed(() => {
  if (!selectedFare.value) return 0;
  return selectedFare.value.amount;
});

const canBookTickets = computed(() => {
  return selectedTripId.value && 
         selectedFare.value && 
         !processing.value;
});

// Watch for prop updates (e.g. inertia navigation)
watch(() => props.trips, (newTrips) => {
    trips.value = [...newTrips];
});

// Methods
const selectTrip = (tripId) => {
  selectedTripId.value = tripId;
  selectedFare.value = null;
  seatMap.value = null;
  suggestedSeats.value = [];
  showTripSelectionModal.value = false;
};

const fetchSeatMap = async () => {
  if (!selectedTripId.value) return;
  seatMapLoading.value = true;
  
  const params = {
    _t: new Date().getTime()
  };
  
  if (selectedFare.value) {
    params.from_stop_id = selectedFare.value.from_stop_id;
    params.to_stop_id = selectedFare.value.to_stop_id;
  }
  
  try {
    const response = await axios.get(route('trips.seatmap', { 
      trip: selectedTripId.value
    }), { params });
    seatMap.value = response.data;
  } catch (error) {
    console.error("Erreur lors de la récupération du plan de salle:", error);
    errors.value.seatmap = "Impossible de charger le plan de salle.";
  } finally {
    seatMapLoading.value = false;
  }
};

const fetchSeatSuggestions = async () => {
    if (!selectedTripId.value || !selectedFare.value) return;
    try {
        const response = await axios.get(route('api.trips.suggest-seats', { 
            trip: selectedTripId.value 
        }), {
            params: {
                destination_stop_id: selectedFare.value.to_stop_id,
                quantity: 1
            }
        });
        suggestedSeats.value = response.data.suggested_seats || [];
        bookingType.value = response.data.booking_type;
        occupancyStats.value = response.data.occupancy;
    } catch (error) {
        console.error("Erreur lors de la récupération des suggestions:", error);
        suggestedSeats.value = [];
        bookingType.value = null;
        occupancyStats.value = null;
    }
};

const bookSeat = (seatNumber) => {
  if (!selectedFare.value) {
    alert("Veuillez d'abord sélectionner une destination.");
    return;
  }

  // Open passenger form modal
  selectedSeatNumber.value = seatNumber;
  passengerForm.value = { name: '', phone: '' };
  passengerFormErrors.value = {};
  showPassengerFields.value = false; // Reset to hidden by default
  showPassengerModal.value = true;
};

const autoSelectOptimalSeat = () => {
  if (!selectedFare.value) {
    return;
  }
  
  if (!suggestedSeats.value || suggestedSeats.value.length === 0) {
    return;
  }
  
  // Auto-select the first (best) suggested seat and open modal
  const optimalSeat = suggestedSeats.value[0];
  bookSeat(optimalSeat.seat_number); // Pass seat_number, not the whole object
};

const confirmBooking = () => {
  // Validate passenger form
  passengerFormErrors.value = {};
  
  if (showPassengerFields.value && passengerForm.value.name && passengerForm.value.name.trim().length < 2) {
    passengerFormErrors.value.name = 'Le nom doit contenir au moins 2 caractères';
  }
  
  if (showPassengerFields.value && passengerForm.value.phone && !/^[0-9]{9,15}$/.test(passengerForm.value.phone.replace(/\s/g, ''))) {
    passengerFormErrors.value.phone = 'Numéro de téléphone invalide (9-15 chiffres)';
  }
  
  if (Object.keys(passengerFormErrors.value).length > 0) {
    return;
  }

  processing.value = true;
  errors.value = {};

  const ticketData = {
    trip_id: selectedTripId.value,
    from_stop_id: selectedFare.value.from_stop_id,
    to_stop_id: selectedFare.value.to_stop_id,
    seats: [selectedSeatNumber.value],
    amount: selectedFare.value.amount, // Use selectedFare.value.amount directly
    seller_id: page.props.auth.user.id,
  };

  if (showPassengerFields.value && passengerForm.value.name) {
    ticketData.passenger_name = passengerForm.value.name.trim();
  }
  if (showPassengerFields.value && passengerForm.value.phone) {
    ticketData.passenger_phone = passengerForm.value.phone.replace(/\s/g, '');
  }

  router.post(route('tickets.store'), ticketData, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      showPassengerModal.value = false;
      fetchSeatMap(); // Refresh seat map
      
      // Clear selection
      selectedFare.value = null;
      selectedSeatNumber.value = null;
      suggestedSeats.value = [];

      if (page.props.flash?.ticket_id) {
        // Try Bluetooth printing first if enabled
        if (useBluetoothPrinter.value && bluetoothPrinterConnected.value) {
          printWithBluetooth(page.props.flash.ticket_id).catch(error => {
            console.error('Bluetooth print failed, falling back to browser print:', error);
            fallbackToBrowserPrint(page.props.flash.ticket_id);
          });
        } else {
          fallbackToBrowserPrint(page.props.flash.ticket_id);
        }
      }
    },
    onError: (errs) => {
      errors.value = errs;
      alert('Erreur lors de la création du ticket.');
    },
    onFinish: () => {
      processing.value = false;
    }
  });
};

// Bluetooth Printer Methods
const connectBluetoothPrinter = async () => {
  try {
    await bluetoothPrinter.connect();
    bluetoothPrinterConnected.value = true;
    const status = bluetoothPrinter.getStatus();
    bluetoothPrinterName.value = status.deviceName;
    alert(`Imprimante connectée: ${status.deviceName}`);
  } catch (error) {
    console.error('Failed to connect Bluetooth printer:', error);
    alert('Échec de la connexion à l\'imprimante Bluetooth. Veuillez réessayer.');
  }
};

const disconnectBluetoothPrinter = () => {
  bluetoothPrinter.disconnect();
  bluetoothPrinterConnected.value = false;
  bluetoothPrinterName.value = null;
};

const toggleBluetoothPrinter = () => {
  useBluetoothPrinter.value = !useBluetoothPrinter.value;
  localStorage.setItem('use_bluetooth_printer', useBluetoothPrinter.value.toString());
  
  if (useBluetoothPrinter.value && !bluetoothPrinterConnected.value) {
    connectBluetoothPrinter();
  }
};

const printWithBluetooth = async (ticketId) => {
  try {
    // Fetch ticket data
    const response = await axios.get(route('api.tickets.show', ticketId));
    const ticket = response.data;
    
    console.log('Ticket data received:', ticket);
    
    // Extract settings from response
    const settings = response.data.settings || {
      company_name: 'TSR CI',
      phone_numbers: ['+225 XX XX XX XX XX'],
      footer_messages: ['Valable pour ce voyage', 'Non remboursable'],
      print_qr_code: false,
      qr_code_base_url: null
    };
    
    // Format ticket data for thermal printer
    const ticketData = {
      ticket_number: ticket.ticket_number || 'N/A',
      route_name: ticket.trip?.route?.name || 'N/A',
      from_stop: ticket.from_stop?.name || 'N/A',
      to_stop: ticket.to_stop?.name || 'N/A',
      date: ticket.trip?.departure_at ? new Date(ticket.trip.departure_at).toLocaleDateString('fr-FR') : 'N/A',
      time: ticket.trip?.departure_at ? new Date(ticket.trip.departure_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) : 'N/A',
      class: ticket.trip?.vehicle?.vehicle_type?.name || 'Standard',
      seat_number: ticket.seat_number || 'N/A',
      price: String(ticket.price || 0),
      vehicle_number: ticket.trip?.vehicle?.registration_number || 'N/A',
      qr_code: ticket.qr_code || null,
      timestamp: new Date().toLocaleString('fr-FR')
    };
    
    console.log('Formatted ticket data:', ticketData);
    
    await bluetoothPrinter.printTicket(ticketData, settings);
  } catch (error) {
    console.error('Bluetooth print error:', error);
    throw error;
  }
};

const fallbackToBrowserPrint = (ticketId) => {
  const printUrl = route('tickets.print', { ticket: ticketId });
  const printWindow = window.open(printUrl, '_blank', 'width=400,height=600');
  if (!printWindow) {
    alert('Veuillez autoriser les popups pour imprimer le ticket.');
  }
};

const cancelBooking = () => {
  showPassengerModal.value = false;
  selectedFare.value = null;
  selectedSeatNumber.value = null;
  suggestedSeats.value = [];
};

const createTrip = () => {
  createTripProcessing.value = true;
  createTripErrors.value = {};
  
  router.post(route('seller.trips.store'), createTripForm.value, {
    preserveState: true,
    onSuccess: () => {
      showCreateTripModal.value = false;
      createTripForm.value = {
        route_id: '',
        vehicle_id: '',
        departure_at: '',
        status: 'scheduled'
      };
    },
    onError: (errors) => {
      createTripErrors.value = errors;
    },
    onFinish: () => {
      createTripProcessing.value = false;
    }
  });
};

// Zoom and Pan methods
const handleWheel = (event) => {
  // Disable wheel zoom on mobile - use +/- buttons only
  if (isMobile.value) return;
  
  event.preventDefault();
  const delta = event.deltaY > 0 ? -0.1 : 0.1;
  zoomLevel.value = Math.max(0.5, Math.min(3, zoomLevel.value + delta));
};

const zoomIn = () => {
  zoomLevel.value = Math.min(3, zoomLevel.value + 0.2);
};

const zoomOut = () => {
  zoomLevel.value = Math.max(0.5, zoomLevel.value - 0.2);
};

const handleMouseDown = (event) => {
  isDragging.value = true;
  dragStartX.value = event.clientX - panX.value;
  dragStartY.value = event.clientY - panY.value;
};

const handleMouseMove = (event) => {
  if (isDragging.value) {
    panX.value = event.clientX - dragStartX.value;
    panY.value = event.clientY - dragStartY.value;
  }
};

const handleMouseUp = () => {
  isDragging.value = false;
};

const resetZoom = () => {
  zoomLevel.value = 1;
  panX.value = 0;
  panY.value = 0;
};

// Watchers
watch(selectedTripId, (newVal) => {
  if (newVal) {
    selectedFare.value = null;
    seatMap.value = null;
    suggestedSeats.value = [];
    fetchSeatMap();
    resetZoom(); // Reset zoom when changing trips
  }
});

watch(selectedFare, (newVal) => {
    if(newVal) {
        // Fetch specific seat map for this segment
        fetchSeatMap();
        
        fetchSeatSuggestions().then(() => {
            // Auto-select optimal seat if enabled
            if (autoSelectOptimal.value && suggestedSeats.value && suggestedSeats.value.length > 0) {
                autoSelectOptimalSeat();
            }
        });
    } else {
        // Reload full seat map (conservative view)
        fetchSeatMap();
        suggestedSeats.value = [];
    }
});

// Auto-reconnect to Bluetooth printer on page load
onMounted(async () => {
  if (useBluetoothPrinter.value && bluetoothPrinter.isSupported()) {
    try {
      // Try to get previously paired devices
      const devices = await navigator.bluetooth.getDevices();
      if (devices && devices.length > 0) {
        // Reconnect to the first device (most recent)
        bluetoothPrinter.device = devices[0];
        const server = await bluetoothPrinter.device.gatt.connect();
        const service = await server.getPrimaryService('000018f0-0000-1000-8000-00805f9b34fb');
        bluetoothPrinter.characteristic = await service.getCharacteristic('00002af1-0000-1000-8000-00805f9b34fb');
        bluetoothPrinter.connected = true;
        bluetoothPrinterConnected.value = true;
        bluetoothPrinterName.value = bluetoothPrinter.device.name;
        console.log('Auto-reconnected to Bluetooth printer:', bluetoothPrinter.device.name);
      }
    } catch (error) {
      console.log('Auto-reconnect failed:', error);
      // Silently fail - user can manually reconnect
    }
  }

  // Auto-select closest trip
  if (!selectedTripId.value && props.trips.length > 0) {
    const now = new Date();
    const sortedTrips = [...props.trips].sort((a, b) => {
      const diffA = new Date(a.departure_at) - now;
      const diffB = new Date(b.departure_at) - now;
      // Prefer future trips, closest first
      if (diffA >= 0 && diffB >= 0) return diffA - diffB;
      // If one is past and one is future, future wins
      if (diffA >= 0 && diffB < 0) return -1;
      if (diffA < 0 && diffB >= 0) return 1;
      // Both are past, most recent first
      return diffB - diffA;
    });
    selectTrip(sortedTrips[0].id);
  }
});

</script>

<template>
  <MainNavLayout :show-nav="!isMobile">
    <template #header-actions>
      <!-- Bluetooth Printer Toggle moved to Header -->
      <button 
        @click="toggleBluetoothPrinter" 
        :class="[
          'p-2 border rounded-full text-sm font-medium flex items-center justify-center transition-all',
          useBluetoothPrinter && bluetoothPrinterConnected 
            ? 'border-blue-500 bg-blue-100 text-blue-700' 
            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
        ]"
        :title="bluetoothPrinterConnected ? `Connecté: ${bluetoothPrinterName}` : 'Connecter imprimante Bluetooth'"
      >
        <Bluetooth :class="bluetoothPrinterConnected ? 'text-blue-600' : 'text-gray-500'" class="w-5 h-5" />
      </button>
    </template>

    <div class="w-full min-h-screen md:h-screen flex flex-col overflow-y-auto md:overflow-hidden bg-gray-100">
      <!-- Main Grid Container -->
      <div class="flex-1 flex flex-col lg:grid lg:grid-cols-12 gap-4 p-2 md:p-4 min-h-0">
        <!-- Left Side: Sub-header + Content (Voyages + Tronçons) -->
        <div class="lg:col-span-8 xl:col-span-9 flex flex-col gap-3 md:gap-4 min-h-0">
          <!-- Sub-header with Title and Buttons -->
          <div class="bg-gradient-to-r from-green-50 to-orange-50/30 border-b border-orange-200 px-3 py-2 rounded-lg shadow-sm">
            <div class="flex items-center justify-between">
              <div>
                <h1 class="text-lg font-bold text-green-700">Billetterie</h1>
                <p class="text-[10px] text-green-600 hidden sm:block">Vente de tickets en temps réel</p>
              </div>
              <div class="flex items-center gap-2">
                <button @click="showCreateTripModal = true" class="px-4 py-2 bg-green-600 text-white text-sm font-bold rounded-lg flex items-center gap-2 shadow-md hover:bg-green-700 active:scale-95 transition-all">
                  <Calendar class="w-5 h-5" />
                  <span>Créer un voyage</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Content Area: Voyages + Tronçons + Places (on mobile when Auto OFF) -->
          <div :class="[
            'flex-1 flex flex-col lg:grid lg:grid-cols-3 gap-3 md:gap-4 min-h-0'
          ]">
            <!-- Voyages -->
            <div class="lg:col-span-2 flex flex-col min-h-0 overflow-hidden">
              <div class="bg-white rounded-lg border border-orange-200 shadow-sm flex flex-col h-full">
                <div class="px-4 py-3 border-b border-gray-200 bg-green-50 flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <h2 class="text-base font-semibold text-green-700 flex items-center">
                      <Bus class="mr-2 w-5 h-5" />
                      Voyage
                    </h2>
                    <!-- Auto toggle moved here -->
                    <label class="flex items-center gap-1.5 cursor-pointer bg-white px-2 py-1 rounded-lg border border-gray-200 shadow-sm">
                      <input 
                        type="checkbox" 
                        v-model="autoSelectOptimal"
                        class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded focus:ring-green-500"
                      />
                      <span class="text-xs text-gray-700 font-medium">⚡ Auto</span>
                    </label>
                  </div>
                  <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 bg-green-600 text-white rounded-full text-sm font-black shadow-sm">
                      {{ trips.length }} en cours
                    </span>
                    <button 
                      @click="showTripSelectionModal = true"
                      class="px-3 py-1 bg-white border border-green-500 text-green-700 rounded-md text-xs font-bold hover:bg-green-50 transition-colors shadow-sm"
                    >
                      {{ selectedTripId ? 'Changer' : 'Sélectionner' }}
                    </button>
                  </div>
                </div>
                <div class="flex-1 p-3 overflow-y-auto">
                  <!-- Mobile: Show only selected trip -->
                  <div v-if="isMobile && currentTrip" class="bg-white rounded-xl border border-green-200 p-3 shadow-sm relative overflow-hidden">
                    <div class="flex items-start justify-between mb-2">
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                          <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse shrink-0"></div>
                          <div class="text-[10px] uppercase font-bold text-green-600 tracking-wider">En cours</div>
                        </div>
                        <div class="text-base font-black text-gray-900 leading-tight truncate">{{ currentTrip.route?.name }}</div>
                      </div>
                      <div class="text-right shrink-0 ml-3">
                        <div class="text-xl font-black text-gray-900">
                          {{ new Date(currentTrip.departure_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}
                        </div>
                        <div class="text-[10px] text-gray-500">{{ currentTrip.vehicle?.identifier }}</div>
                      </div>
                    </div>
                    <!-- Seat Stats Row -->
                    <div v-if="seatStats.total > 0" class="flex items-center gap-2 pt-2 border-t border-gray-100">
                      <div class="flex-1 flex items-center justify-center gap-1 py-1 bg-red-50 rounded-lg">
                        <span class="text-lg font-black text-red-600">{{ seatStats.available }}</span>
                        <span class="text-[10px] text-red-600 font-medium">restantes</span>
                      </div>
                      <div class="flex-1 flex items-center justify-center gap-1 py-1 bg-green-50 rounded-lg">
                        <span class="text-lg font-black text-green-600">{{ seatStats.total }}</span>
                        <span class="text-[10px] text-green-600 font-medium">total</span>
                      </div>
                    </div>
                  </div>

                  <!-- Desktop: Show all trips with highlighted selected -->
                  <div v-if="!isMobile && trips.length > 0" class="space-y-2">
                    <div 
                      v-for="trip in trips" 
                      :key="trip.id"
                      @click="selectTrip(trip.id)"
                      :class="[
                        'p-3 rounded-lg cursor-pointer transition-all border-2',
                        selectedTripId === trip.id 
                          ? 'bg-green-50 border-green-500 shadow-md' 
                          : 'bg-white border-gray-200 hover:border-green-300 hover:shadow-sm'
                      ]"
                    >
                      <div class="flex items-center justify-between">
                        <div class="flex-1 min-w-0">
                          <div class="flex items-center gap-2">
                            <div v-if="selectedTripId === trip.id" class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                            <div class="font-bold text-gray-900 truncate">{{ trip.route?.name }}</div>
                          </div>
                          <div class="text-xs text-gray-500 mt-0.5">{{ trip.vehicle?.identifier }} • {{ trip.vehicle?.vehicle_type?.name }}</div>
                        </div>
                        <div class="text-right shrink-0 ml-3">
                          <div class="text-lg font-black text-gray-900">
                            {{ new Date(trip.departure_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}
                          </div>
                          <div class="text-[10px] text-gray-500">
                            {{ new Date(trip.departure_at).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' }) }}
                          </div>
                        </div>
                      </div>
                      <!-- Seat Stats for all trips -->
                      <div class="flex items-center gap-2 mt-2 pt-2 border-t" :class="selectedTripId === trip.id ? 'border-green-200' : 'border-gray-100'">
                        <div class="flex-1 flex items-center justify-center gap-1 py-1 bg-red-50 rounded-lg">
                          <span class="text-sm font-black text-red-600">{{ trip.available_seats || 0 }}</span>
                          <span class="text-[10px] text-red-600 font-medium">restantes</span>
                        </div>
                        <div class="flex-1 flex items-center justify-center gap-1 py-1 rounded-lg" :class="selectedTripId === trip.id ? 'bg-green-100' : 'bg-gray-50'">
                          <span class="text-sm font-black" :class="selectedTripId === trip.id ? 'text-green-600' : 'text-gray-600'">{{ trip.total_seats || 0 }}</span>
                          <span class="text-[10px] font-medium" :class="selectedTripId === trip.id ? 'text-green-600' : 'text-gray-500'">total</span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- No trip selected / No trips -->
                  <div v-if="isMobile && !currentTrip" class="h-full flex flex-col items-center justify-center bg-gray-50 rounded-xl border-2 border-dashed border-gray-200 py-10">
                    <div class="bg-white p-6 rounded-full shadow-md mb-6 relative">
                       <Bus class="w-16 h-16 text-green-600" />
                       <div class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full border-2 border-white shadow-sm">
                         {{ trips.length }}
                       </div>
                    </div>
                    <h3 class="text-2xl font-black text-gray-900 mb-2">{{ trips.length }} voyages en cours</h3>
                    <p class="text-gray-500 text-sm max-w-[250px] text-center mb-6">Sélectionnez le voyage pour lequel vous souhaitez vendre des billets.</p>
                    <button 
                      @click="showTripSelectionModal = true"
                      class="px-8 py-3 bg-green-600 text-white rounded-xl text-lg font-black shadow-lg shadow-green-600/20 hover:bg-green-700 hover:scale-105 transition-all transform active:scale-95"
                    >
                      Choisir un voyage
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Tronçons / Destinations -->
            <div :class="[
              'lg:col-span-1 flex flex-col min-h-0 overflow-hidden',
              isMobile && !autoSelectOptimal && selectedFare ? 'order-3' : 'order-2'
            ]">
              <div class="bg-white rounded-lg border border-blue-200 shadow-sm flex flex-col h-full">
                <div class="px-3 py-2 border-b border-blue-100 bg-blue-50">
                  <h2 class="text-sm font-bold text-blue-700 flex items-center">
                    <Routes class="mr-1.5 w-4 h-4" />
                    Destination
                  </h2>
                </div>
                <div class="flex-1 overflow-y-auto p-2">
                  <div v-if="currentTrip" class="space-y-2">
                    <div v-for="fare in availableFares" :key="fare.id"
                         @click="selectedFare = fare"
                         :class="[
                           'relative overflow-hidden rounded-xl cursor-pointer transition-all duration-200 active:scale-[0.98]',
                           selectedFare?.id === fare.id 
                             ? 'ring-2 ring-offset-2 shadow-lg' 
                             : 'hover:shadow-lg'
                         ]"
                         :style="{
                           backgroundColor: fare.color,
                           '--tw-ring-color': fare.color
                         }"
                    >
                      <!-- Horizontal Layout: Destination Left, Price Right -->
                      <div class="p-3 flex items-center justify-between">
                        <div class="flex-1 min-w-0 mr-3">
                          <div class="text-white text-base font-bold truncate">
                            {{ fare.to_stop?.name }}
                          </div>
                          <div class="text-white/70 text-[10px] font-medium">
                            → depuis {{ fare.from_stop?.name?.split(' - ')[1] || fare.from_stop?.name }}
                          </div>
                        </div>
                        <div class="text-right shrink-0 flex items-center gap-2">
                          <div>
                            <div class="text-2xl font-black text-white">
                              {{ fare.amount.toLocaleString('fr-FR') }}
                            </div>
                            <div class="text-white/70 text-[10px] font-bold">FCFA</div>
                          </div>
                          <div v-if="selectedFare?.id === fare.id" class="w-6 h-6 bg-white/30 rounded-full flex items-center justify-center">
                            <Check class="w-4 h-4 text-white" />
                          </div>
                        </div>
                      </div>
                      <div v-if="ticketQuantity > 1" class="bg-black/10 px-3 py-1 text-white/90 text-[10px] font-bold">
                        ×{{ ticketQuantity }} = {{ (fare.amount * ticketQuantity).toLocaleString('fr-FR') }} F
                      </div>
                    </div>
                  </div>
                  <div v-else class="h-32 flex flex-col items-center justify-center text-gray-400">
                    <Routes class="w-8 h-8 mb-2 opacity-50" />
                    <p class="text-xs">Sélectionnez un voyage</p>
                  </div>
                </div>
              </div>
            </div>
            <!-- Places Section (inline on mobile) -->
            <div :class="[
              'lg:hidden flex flex-col min-h-[350px]',
              isMobile && !autoSelectOptimal && selectedFare ? 'order-2' : 'order-3'
            ]">
              <div class="bg-white rounded-lg border-2 border-red-500 shadow-lg flex flex-col h-full overflow-hidden">
                <div class="px-3 py-2 border-b-2 border-red-500 bg-gradient-to-r from-red-50 to-orange-50">
                  <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-red-700 flex items-center">
                      <Seat class="mr-2 w-4 h-4" />
                      Places
                    </h2>
                    <div v-if="currentTrip && seatMap" class="flex items-center gap-2">
                      <div class="flex items-center gap-1 bg-white rounded border border-red-300 p-1">
                        <button @click="zoomOut" class="w-6 h-6 flex items-center justify-center text-red-600 hover:bg-red-50 rounded">−</button>
                        <span class="text-xs text-gray-600 min-w-[40px] text-center">{{ Math.round(zoomLevel * 100) }}%</span>
                        <button @click="zoomIn" class="w-6 h-6 flex items-center justify-center text-red-600 hover:bg-red-50 rounded">+</button>
                      </div>
                      <button @click="resetZoom" class="px-2 py-1 text-xs bg-red-600 text-white rounded">↺</button>
                    </div>
                  </div>
                </div>
                <div class="flex-1 overflow-hidden p-2 flex items-center justify-center bg-gray-50 relative" :class="{ 'cursor-grab': !isDragging, 'cursor-grabbing': isDragging }">
                  <div v-if="seatMapLoading" class="text-center">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-green-600 mx-auto mb-2"></div>
                    <p class="text-xs text-gray-600">Chargement...</p>
                  </div>
                  <div v-else-if="currentTrip && seatMap" class="w-full h-full flex items-center justify-center" :style="{ transform: `translate(${panX}px, ${panY}px) scale(${zoomLevel})`, transition: isDragging ? 'none' : 'transform 0.1s ease-out' }">
                    <VehicleSeatMapSVG
                      v-if="currentTrip.vehicle?.vehicle_type"
                      :vehicle-type="currentTrip.vehicle.vehicle_type"
                      :seat-map="seatMap"
                      :suggested-seats="suggestedSeats"
                      :selected-seat="selectedSeatNumber"
                      :selected-color="selectedFare?.color"
                      :show-suggestions="!autoSelectOptimal"
                      @seat-click="bookSeat"
                    />
                  </div>
                  <div v-else class="text-center text-gray-400">
                    <Seat class="w-10 h-10 mx-auto mb-2" />
                    <p class="text-xs">Sélectionnez un voyage</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Side: Places - Desktop Only -->
        <div class="hidden lg:flex lg:col-span-4 xl:col-span-3 flex-col min-h-0">
          <div class="bg-white rounded-lg border-2 border-red-500 shadow-lg flex flex-col h-full overflow-hidden">
            <div class="px-3 py-2 border-b-2 border-red-500 bg-gradient-to-r from-red-50 to-orange-50">
              <div class="flex items-center justify-between mb-2">
                <h2 class="text-sm font-semibold text-red-700 flex items-center">
                  <Seat class="mr-2 w-4 h-4" />
                  Places
                </h2>
                <div v-if="currentTrip && seatMap" class="flex items-center gap-2">
                  <!-- Zoom Controls -->
                  <div class="flex items-center gap-1 bg-white rounded border border-red-300 p-1">
                    <button
                      @click="zoomOut"
                      class="w-6 h-6 flex items-center justify-center text-red-600 hover:bg-red-50 rounded transition-colors"
                      title="Zoom arrière"
                    >
                      −
                    </button>
                    <span class="text-xs text-gray-600 min-w-[45px] text-center">{{ Math.round(zoomLevel * 100) }}%</span>
                    <button
                      @click="zoomIn"
                      class="w-6 h-6 flex items-center justify-center text-red-600 hover:bg-red-50 rounded transition-colors"
                      title="Zoom avant"
                    >
                      +
                    </button>
                  </div>
                  <!-- Reset Button -->
                  <button
                    @click="resetZoom"
                    class="px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
                    title="Réinitialiser"
                  >
                    ↺
                  </button>
                  <!-- Modal Button -->
                  <button
                    @click="showZoomModal = true"
                    class="px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
                    title="Agrandir en plein écran"
                  >
                    🔍
                  </button>
                </div>
              </div>
              <!-- Legend Removed -->
            </div>
            
            <div 
              class="flex-1 overflow-hidden p-3 flex items-center justify-center bg-gray-50 relative"
              @wheel="handleWheel"
              @mousedown="handleMouseDown"
              @mousemove="handleMouseMove"
              @mouseup="handleMouseUp"
              @mouseleave="handleMouseUp"
              :class="{ 'cursor-grab': !isDragging, 'cursor-grabbing': isDragging }"
            >
              <div v-if="seatMapLoading" class="text-center">
                <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-green-600 mx-auto mb-2"></div>
                <p class="text-xs text-gray-600">Chargement...</p>
              </div>
              <div 
                v-else-if="currentTrip && seatMap" 
                class="w-full h-full flex items-center justify-center"
                :style="{ 
                  transform: `translate(${panX}px, ${panY}px) scale(${zoomLevel})`,
                  transition: isDragging ? 'none' : 'transform 0.1s ease-out'
                }"
              >
                <!-- SVG Vehicle Seat Map -->
                <VehicleSeatMapSVG
                  v-if="currentTrip.vehicle?.vehicle_type"
                  :vehicle-type="currentTrip.vehicle.vehicle_type"
                  :seat-map="seatMap"
                  :suggested-seats="suggestedSeats"
                  :selected-seat="selectedSeatNumber"
                  :selected-color="selectedFare?.color"
                  :show-suggestions="!autoSelectOptimal"
                  @seat-click="bookSeat"
                />
                <InputError v-if="errors.general" class="mt-2" :message="errors.general" />
              </div>
              <div v-else class="text-center text-gray-400">
                <Seat class="w-12 h-12 mx-auto mb-2" />
                <p class="text-xs">Sélectionnez un voyage</p>
              </div>
              
              <!-- Zoom Instructions Overlay -->
              <div 
                v-if="currentTrip && selectedFare && seatMap" 
                class="absolute bottom-2 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-60 text-white text-xs px-3 py-1 rounded-full pointer-events-none"
              >
                Molette: Zoom | Glisser: Déplacer
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Passenger Information Modal -->
    <div v-if="showPassengerModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4">
      <div class="relative bg-white rounded-t-2xl sm:rounded-lg shadow-xl w-full max-w-md mx-0 sm:mx-4 transform transition-all duration-300">
          <div class="p-4 md:p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-xl font-bold text-gray-900">Informations Passager</h3>
              <button @click="cancelBooking" class="text-gray-400 hover:text-gray-600">
                <Close class="w-6 h-6" />
              </button>
            </div>
            
            <!-- Seat Information -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
              <div class="text-center">
                <div class="text-3xl font-bold text-blue-600 mb-2">Place {{ selectedSeatNumber }}</div>
                <div v-if="selectedSeatSuggestion" class="text-sm text-gray-700 mb-2">
                  <span class="font-semibold">Score:</span> {{ selectedSeatSuggestion.score }}<br>
                  <span class="font-semibold">Raison:</span> {{ selectedSeatSuggestion.reason }}
                </div>
                <div class="text-sm text-gray-600">{{ currentTrip.route.name }}</div>
                <div class="text-sm text-gray-600">{{ selectedFare.from_stop.name }} → {{ selectedFare.to_stop.name }}</div>
                <div class="text-2xl font-bold text-green-600 mt-2">{{ selectedFare.amount }} FCFA</div>
                
                <!-- Quantity Input Moved Here -->
                <div class="mt-4 flex items-center justify-center gap-3">
                   <span class="text-sm font-medium text-gray-700">Quantité:</span>
                   <div class="flex items-center bg-white rounded-lg border border-gray-300">
                      <button 
                        type="button"
                        @click="ticketQuantity = Math.max(1, ticketQuantity - 1)"
                        class="px-3 py-1 text-gray-600 hover:bg-gray-100 rounded-l-lg border-r border-gray-300"
                      >-</button>
                      <input 
                        v-model.number="ticketQuantity"
                        type="number"
                        min="1"
                        max="10"
                        class="w-12 py-1 text-center border-0 focus:ring-0 text-gray-900 font-bold"
                      />
                      <button 
                        type="button"
                        @click="ticketQuantity = Math.min(10, ticketQuantity + 1)"
                        class="px-3 py-1 text-gray-600 hover:bg-gray-100 rounded-r-lg border-l border-gray-300"
                      >+</button>
                   </div>
                </div>
                <div v-if="ticketQuantity > 1" class="text-sm font-bold text-blue-700 mt-2">
                   Total: {{ (selectedFare.amount * ticketQuantity).toLocaleString('fr-FR') }} FCFA
                </div>
              </div>
            </div>
            
            <!-- Toggle for passenger fields -->
            <button
              @click="showPassengerFields = !showPassengerFields"
              class="w-full flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg mb-4 transition-colors"
            >
              <span class="text-sm font-medium text-gray-700">Informations passager (optionnel)</span>
              <ChevronDown :class="{ 'rotate-180': showPassengerFields }" class="w-5 h-5 text-gray-500 transition-transform" />
            </button>
            
            <!-- Passenger fields (collapsible) -->
            <div v-show="showPassengerFields" class="space-y-4 mb-4">
              <div>
                <InputLabel for="passenger_name" value="Nom du passager" />
                <TextInput
                  id="passenger_name"
                  v-model="passengerForm.name"
                  type="text"
                  class="mt-1 block w-full"
                  placeholder="Nom complet"
                />
                <InputError class="mt-2" :message="passengerFormErrors.name" />
              </div>
              
              <div>
                <InputLabel for="passenger_phone" value="Téléphone" />
                <TextInput
                  id="passenger_phone"
                  v-model="passengerForm.phone"
                  type="tel"
                  class="mt-1 block w-full"
                  placeholder="0612345678"
                />
                <InputError class="mt-2" :message="passengerFormErrors.phone" />
              </div>
            </div>
            
            <form @submit.prevent="confirmBooking">
              <div class="flex items-center justify-end space-x-3 pt-4">
              <button
                type="button"
                @click="cancelBooking"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="processing"
                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
              >
                <Printer class="w-5 h-5 mr-2" />
                {{ processing ? '...' : 'Imprimer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal de création de voyage -->
    <div v-if="showCreateTripModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
      <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md">
        <div class="p-5">
          <h3 class="text-lg leading-6 font-medium text-gray-900">Créer un nouveau voyage</h3>
          <form @submit.prevent="createTrip" class="mt-2 space-y-4">
            <div>
              <InputLabel for="route_id" value="Route" />
              <select
                id="route_id"
                v-model="createTripForm.route_id"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                required
              >
                <option value="">Sélectionner une route</option>
                <option v-for="route in routes" :key="route.id" :value="route.id">
                  {{ route.name }}
                </option>
              </select>
              <InputError class="mt-2" :message="createTripErrors.route_id" />
            </div>

            <div>
              <InputLabel for="vehicle_id" value="Véhicule" />
              <select
                id="vehicle_id"
                v-model="createTripForm.vehicle_id"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500"
                required
              >
                <option value="">Sélectionner un véhicule</option>
                <option v-for="vehicle in vehicles" :key="vehicle.id" :value="vehicle.id">
                  {{ vehicle.identifier }} ({{ vehicle.seat_count }} places)
                </option>
              </select>
              <InputError class="mt-2" :message="createTripErrors.vehicle_id" />
            </div>

            <div>
              <InputLabel for="departure_at" value="Heure de départ" />
              <TextInput
                id="departure_at"
                v-model="createTripForm.departure_at"
                type="datetime-local"
                class="mt-1 block w-full"
                required
              />
              <InputError class="mt-2" :message="createTripErrors.departure_at" />
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4">
              <button
                type="button"
                @click="showCreateTripModal = false"
                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50"
              >
                Annuler
              </button>
              <button
                type="submit"
                :disabled="createTripProcessing"
                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
              >
                {{ createTripProcessing ? 'Création...' : 'Créer' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Zoom Modal for Seat Map -->
    <div v-if="showZoomModal" class="fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
      <div class="relative bg-white rounded-lg shadow-2xl w-full h-full max-w-7xl max-h-[90vh] flex flex-col">
        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-red-50 to-orange-50">
          <div>
            <h3 class="text-xl font-bold text-gray-900">Plan des Places</h3>
            <p class="text-sm text-gray-600 mt-1">
              {{ currentTrip?.route?.name }} - {{ currentTrip?.vehicle?.identifier }}
            </p>
          </div>
          <button @click="showZoomModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">
            <Close class="w-8 h-8" />
          </button>
        </div>

        <!-- Legend Removed -->

        <!-- Seat Map with Scroll -->
        <div class="flex-1 overflow-auto p-6 bg-gray-100 flex items-center justify-center">
          <div class="transform rotate-90">
            <VehicleSeatMapSVG
              v-if="currentTrip?.vehicle?.vehicle_type"
              :vehicle-type="currentTrip.vehicle.vehicle_type"
              :seat-map="seatMap"
              :suggested-seats="suggestedSeats"
              @seat-click="bookSeat"
              class="scale-125"
            />
          </div>
        </div>

        <!-- Instructions -->
        <div class="px-6 py-3 border-t border-gray-200 bg-gray-50 text-center text-sm text-gray-600">
          Le véhicule est affiché horizontalement. Utilisez le défilement pour voir toutes les places. Cliquez sur une place pour réserver.
        </div>
      </div>
    </div>

    <!-- Trip Selection Modal -->
    <div v-if="showTripSelectionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
      <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden transform transition-all">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-100 bg-green-50 flex items-center justify-between">
          <div>
            <h3 class="text-xl font-bold text-green-700">Sélectionner un voyage</h3>
            <p class="text-sm text-green-600">Choisissez le départ pour lequel vous vendez des billets</p>
          </div>
          <button @click="showTripSelectionModal = false" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors">
            <Close class="w-6 h-6" />
          </button>
        </div>

        <!-- Search Bar -->
        <div class="p-4 border-b border-gray-100 bg-white">
          <div class="relative">
            <input 
              v-model="searchQuery"
              type="text"
              placeholder="Rechercher par destination ou numéro de bus..."
              class="w-full pl-10 pr-4 py-3 bg-gray-50 border-0 focus:ring-2 focus:ring-green-500 rounded-xl text-sm transition-all"
            />
            <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
               <Bus class="w-5 h-5" />
            </div>
          </div>
        </div>

        <!-- Trip List -->
        <div class="flex-1 overflow-y-auto p-4 bg-gray-50">
          <div v-if="filteredTrips.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="trip in filteredTrips" :key="trip.id"
                 @click="selectTrip(trip.id)"
                 :class="[
                   'group p-4 rounded-xl border-2 cursor-pointer transition-all duration-200 bg-white hover:shadow-lg',
                   selectedTripId === trip.id 
                     ? 'border-green-500 bg-green-50 shadow-md ring-4 ring-green-500/10' 
                     : 'border-transparent hover:border-green-200'
                 ]">
              <div class="flex items-start justify-between mb-3">
                <div class="bg-green-100 p-2 rounded-lg group-hover:bg-green-200 transition-colors">
                  <Bus class="w-6 h-6 text-green-600" />
                </div>
                <div v-if="selectedTripId === trip.id" class="bg-green-500 text-white p-1 rounded-full">
                  <Check class="w-4 h-4" />
                </div>
              </div>
              
              <div class="font-bold text-gray-900 text-base mb-2 leading-tight">
                {{ trip.route?.name }}
              </div>
              
              <div class="space-y-2">
                <div class="flex items-center text-sm text-gray-600">
                  <Clock class="w-4 h-4 mr-2 text-green-500" />
                  <span>{{ new Date(trip.departure_at).toLocaleString('fr-FR', {
                    day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'
                  }) }}</span>
                </div>
                <div class="flex items-center text-sm text-gray-600">
                  <MapMarker class="w-4 h-4 mr-2 text-green-500" />
                  <span>Bus: {{ trip.vehicle?.identifier }}</span>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="h-64 flex flex-col items-center justify-center text-gray-400">
            <Bus class="w-16 h-16 mb-4 opacity-20" />
            <p class="text-lg font-medium">Aucun voyage trouvé</p>
            <p class="text-sm">Essayez une autre recherche ou créez un nouveau voyage</p>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-gray-100 bg-white flex justify-end">
           <button 
             @click="showTripSelectionModal = false"
             class="px-6 py-2 bg-gray-100 text-gray-700 font-bold rounded-lg hover:bg-gray-200 transition-colors"
           >
             Fermer
           </button>
        </div>
      </div>
    </div>
  </MainNavLayout>
</template>

<style scoped>
/* Custom scrollbar for better UX */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
