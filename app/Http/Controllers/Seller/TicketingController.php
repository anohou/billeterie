<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\RouteFare;
use App\Models\UserStationAssignment;
use App\Models\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TicketingController extends Controller
{
    public function index()
    {
        $data = $this->getTicketingData();
        return Inertia::render('Seller/Ticketing', $data);
    }

    public function horizontal()
    {
        $data = $this->getTicketingData();
        return Inertia::render('Seller/TicketingHorizontal', $data);
    }

    private function getTicketingData()
    {
        $user = auth()->user();
        
        // Récupérer les voyages assignés à l'utilisateur
        if ($user->role === 'admin' || $user->role === 'supervisor') {
            // Les admins et superviseurs voient tout
            $trips = Trip::with(['route.originStation', 'vehicle.vehicleType'])
                ->withCount('tripSeatOccupancies as occupied_seats')
                ->where('departure_at', '>=', now()->startOfDay())
                ->orderBy('departure_at')
                ->get();

            $routeFares = RouteFare::with(['fromStop.station', 'toStop.station'])
                ->get();
        } else {
            // Les vendeurs ne voient que les voyages dont les routes partent de leurs stations assignées
            $assignedStationIds = UserStationAssignment::where('user_id', $user->id)
                ->where('active', true)
                ->pluck('station_id')
                ->toArray();

            // Récupérer les routes qui partent des stations assignées
            $assignedRouteIds = Route::whereIn('origin_station_id', $assignedStationIds)
                ->where('active', true)
                ->pluck('id')
                ->toArray();

            $trips = Trip::with(['route.originStation', 'vehicle.vehicleType'])
                ->withCount('tripSeatOccupancies as occupied_seats')
                ->whereIn('route_id', $assignedRouteIds)
                ->where('departure_at', '>=', now()->startOfDay())
                ->orderBy('departure_at')
                ->get();

            // Get fares where the from_stop is in one of the assigned stations
            $routeFares = RouteFare::with(['fromStop.station', 'toStop.station'])
                ->whereHas('fromStop', function($query) use ($assignedStationIds) {
                    $query->whereIn('station_id', $assignedStationIds);
                })
                ->get();
        }

        // Récupérer toutes les routes et véhicules pour la création
        $routes = \App\Models\Route::orderBy('name')->get(['id', 'name']);
        $vehicles = \App\Models\Vehicle::with('vehicleType')->orderBy('identifier')->get(['id', 'identifier', 'seat_count', 'vehicle_type_id']);

        // Calculate real seat counts for each trip from seat_map
        foreach ($trips as $trip) {
            $seatMap = $trip->vehicle?->vehicleType?->seat_map ?? [];
            $totalSeats = 0;
            foreach ($seatMap as $row) {
                foreach ($row as $cell) {
                    if (isset($cell['type']) && $cell['type'] === 'seat') {
                        $totalSeats++;
                    }
                }
            }
            $trip->total_seats = $totalSeats;
            $trip->available_seats = $totalSeats - ($trip->occupied_seats ?? 0);
        }

        return [
            'trips' => $trips,
            'routeFares' => $routeFares,
            'routes' => $routes,
            'vehicles' => $vehicles,
        ];
    }

    public function getSeatMap($tripId)
    {
        $trip = Trip::with(['vehicle.vehicleType', 'tripSeatOccupancies.ticket.toStop', 'route.routeStopOrders'])->findOrFail($tripId);
        
        $vehicleType = $trip->vehicle->vehicleType;
        $seatCount = $trip->vehicle->seat_count;
        $occupiedSeats = $trip->tripSeatOccupancies->pluck('seat_number')->toArray();
        
        // Préparer la map des ordres d'arrêt pour ce trajet
        $stopOrders = $trip->route->routeStopOrders->pluck('stop_index', 'stop_id');
        $totalStops = $stopOrders->count();

        // Utiliser la configuration du type de véhicule
        $seatMap = $vehicleType->seat_map ?? [];
        
        // Enrichir chaque siège avec les informations d'occupation
        foreach ($seatMap as &$row) {
            foreach ($row as &$seat) {
                $occupancy = $trip->tripSeatOccupancies->firstWhere('seat_number', $seat['number']);
                $seat['isOccupied'] = in_array($seat['number'], $occupiedSeats);
                
                if ($seat['isOccupied'] && $occupancy) {
                    $stopId = $occupancy->ticket->to_stop_id;
                    $stopIndex = $stopOrders[$stopId] ?? 0;
                    $seat['color'] = $this->getStopColor($stopIndex, $totalStops);
                    $seat['destination_name'] = $occupancy->ticket->toStop->name;
                } else {
                    $seat['color'] = '#94A3B8';
                    $seat['destination_name'] = null;
                }
            }
        }
        
        return response()->json([
            'seat_map' => $seatMap,
            'vehicle_type' => $vehicleType,
            'total_seats' => $seatCount,
            'occupied_seats' => count($occupiedSeats),
            'available_seats' => $seatCount - count($occupiedSeats)
        ]);
    }
    
    /**
     * Génère une couleur en fonction de la distance (index)
     * Plus c'est loin, plus c'est foncé/intense
     */
    private function getStopColor($stopIndex, $totalStops)
    {
        if ($totalStops <= 1) return '#3B82F6'; // Blue-500 default

        // Normaliser l'index entre 0 et 1
        $ratio = $stopIndex / ($totalStops - 1); // 0 = début, 1 = fin
        
        // Dégradé de Bleu
        // HSL: 220 (Blue)
        // Saturation: 100%
        // Lightness: De 85% (très très clair/proche) à 30% (foncé/loin)
        
        $lightness = 85 - ($ratio * 55); // 85 -> 30
        
        return "hsl(220, 100%, {$lightness}%)";
    }
}
