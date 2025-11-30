<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stop;
use App\Models\Trip;
use App\Services\OptimisationService;
use Illuminate\Http\Request;

class TripController extends Controller
{
    protected $optimisationService;

    public function __construct(OptimisationService $optimisationService)
    {
        $this->optimisationService = $optimisationService;
    }

    public function index(Request $request)
    {
        // Basic index method, can be expanded with filtering
        return Trip::with(['route', 'vehicle'])->get();
    }

    public function suggestSeats(Request $request, Trip $trip)
    {
        $validated = $request->validate([
            'destination_stop_id' => 'required|uuid|exists:stops,id',
            'boarding_stop_id' => 'sometimes|uuid|exists:stops,id', // For semi-intelligent mode
            'quantity' => 'sometimes|integer|min:1',
        ]);

        $destinationStopId = $validated['destination_stop_id'];
        $boardingStopId = $validated['boarding_stop_id'] ?? null;
        $quantity = $validated['quantity'] ?? 1;

        // Utiliser le service d'optimisation
        $suggestions = $this->optimisationService->getSuggestedSeats(
            $trip->id, 
            $destinationStopId, 
            $quantity,
            $boardingStopId
        );
        $stats = $this->optimisationService->getTripOccupancyStats($trip->id);

        return response()->json([
            'suggested_seats' => $suggestions,
            'booking_type' => $stats['booking_type'],
            'occupancy' => [
                'total_seats' => $stats['total_seats'],
                'occupied_seats' => $stats['occupied_seats'],
                'available_seats' => $stats['available_seats'],
                'occupancy_rate' => $stats['occupancy_rate'],
            ]
        ]);
    }
    
    public function seatMap(Trip $trip)
    {
        $trip->load(['vehicle.vehicleType', 'tripSeatOccupancies.ticket.toStop', 'route.stops']);

        $vehicleType = $trip->vehicle->vehicleType;
        $seatCount = $vehicleType->seat_count;
        $config = $vehicleType->seat_configuration ?? '2+2';
        $parts = array_map('intval', explode('+', $config));
        $seatsPerRow = array_sum($parts);
        
        $stopsOnRoute = $trip->route->stops->pluck('id')->toArray();
        $totalStops = count($stopsOnRoute);

        $occupiedSeatsLookup = $trip->tripSeatOccupancies->keyBy('seat_number')->map(function ($occupancy) use ($stopsOnRoute, $totalStops) {
            if (!$occupancy->ticket) {
                return null;
            }
            $stopIndex = array_search($occupancy->ticket->to_stop_id, $stopsOnRoute);
            $stopOrder = $stopIndex !== false ? $stopIndex + 1 : $totalStops;
            
            return [
                'destination_name' => $occupancy->ticket->toStop->name,
                'color' => $this->getStopColor($stopOrder, $totalStops)
            ];
        });

        // Door positions from DB
        // '0' represents the front door aligned with driver (doesn't consume a seat)
        // Any other number represents a seat replaced by a door
        $dbDoorPositions = $vehicleType->door_positions ?? [];
        $doorPositions = array_filter($dbDoorPositions, function($pos) {
            return $pos > 0;
        });
        
        // Use the stored seat map from VehicleType as the source of truth
        $storedSeatMap = $vehicleType->seat_map ?? [];
        
        $seatMap = [];
        $processedSeatsCount = 0;
        
        foreach ($storedSeatMap as $row) {
            $processedRow = [];
            foreach ($row as $seat) {
                // If it's a seat, check occupancy
                if (isset($seat['type']) && $seat['type'] === 'seat') {
                    $seatNumber = (int) $seat['number'];
                    $isOccupied = $occupiedSeatsLookup->has($seatNumber);
                    $seatData = $occupiedSeatsLookup->get($seatNumber);
                    
                    $processedRow[] = array_merge($seat, [
                        'isOccupied' => $isOccupied,
                        'destination_name' => $isOccupied ? $seatData['destination_name'] : null,
                        'color' => $isOccupied ? $seatData['color'] : '#94A3B8',
                    ]);
                    $processedSeatsCount++;
                } else {
                    // Pass through other types (aisle, empty, driver, door)
                    $processedRow[] = $seat;
                }
            }
            $seatMap[] = $processedRow;
        }
        
        return response()->json([
            'seat_map' => $seatMap,
            'total_seats' => $seatCount, // Total capacity
            'occupied_seats_count' => $occupiedSeatsLookup->count(),
            'available_seats_count' => $seatCount - $occupiedSeatsLookup->count()
        ]);
    }

    /**
     * Determines the color for a stop based on its order in the route.
     * Uses blue gradient: light blue for close destinations, dark blue for far destinations
     */
    private function getStopColor(int $stopOrder, int $totalStops): string
    {
        if ($totalStops <= 1) return '#3B82F6'; // Blue-500 default

        // Normalize the order between 0 and 1
        $ratio = ($stopOrder - 1) / ($totalStops - 1); // 0 = first stop, 1 = last stop
        
        // Blue gradient
        // HSL: 220 (Blue)
        // Saturation: 100%
        // Lightness: From 85% (very very light/close) to 30% (dark/far)
        
        $lightness = 85 - ($ratio * 55); // 85 -> 30
        
        return "hsl(220, 100%, {$lightness}%)";
    }
}
