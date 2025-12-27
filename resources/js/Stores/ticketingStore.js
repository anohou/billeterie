import { reactive } from 'vue';

export const ticketingStore = reactive({
    selectedSeat: null,
    suggestedSeats: [],
    selectedFareColor: '#3B82F6',
    showSuggestions: true,

    selectedTripId: null,

    selectSeat(seatNumber) {
        this.selectedSeat = seatNumber;
    },

    setSelectedTripId(id) {
        this.selectedTripId = id;
    },

    setSuggestions(suggestions) {
        this.suggestedSeats = suggestions || [];
    },

    setFareColor(color) {
        this.selectedFareColor = color || '#3B82F6';
    },

    setShowSuggestions(show) {
        this.showSuggestions = show;
    },

    clearSelection() {
        this.selectedSeat = null;
        this.suggestedSeats = [];
    }
});
