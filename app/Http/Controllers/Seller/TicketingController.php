<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\RouteFare;
use App\Models\UserRouteAssignment;
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
                ->where('departure_at', '>=', now()->startOfDay())
                ->orderBy('departure_at')
                ->get();

            $routeFares = RouteFare::with(['fromStop', 'toStop'])
                ->get();
        } else {
            // Les vendeurs ne voient que ce qui leur est assigné OU ce qui part de leur station
            $assignedRouteIds = UserRouteAssignment::where('user_id', $user->id)
                ->where('active', true)
                ->pluck('route_id')
                ->toArray();

            // Si le vendeur est rattaché à une gare, ajouter les routes qui partent de cette gare
            if ($user->station_id) {
                $stationRouteIds = \App\Models\Route::where('origin_station_id', $user->station_id)
                    ->where('active', true)
                    ->pluck('id')
                    ->toArray();
                $assignedRouteIds = array_unique(array_merge($assignedRouteIds, $stationRouteIds));
            }

            $trips = Trip::with(['route.originStation', 'vehicle.vehicleType'])
                ->whereIn('route_id', $assignedRouteIds)
                ->where('departure_at', '>=', now()->startOfDay())
                ->orderBy('departure_at')
                ->get();

            // Filter fares to only show those starting from the seller's station
            $routeFaresQuery = RouteFare::with(['fromStop', 'toStop', 'route'])
                ->whereIn('route_id', $assignedRouteIds);
            
            // If seller has a station, only show fares that start from their station
            if ($user->station_id) {
                $routeFaresQuery->whereHas('fromStop', function($query) use ($user) {
                    $query->where('station_id', $user->station_id);
                });
            }
            
            $routeFares = $routeFaresQuery->get();
        }

        // Enrichir les tronçons avec la couleur de destination
        // Pour cela, on a besoin de l'ordre des arrêts pour chaque route
        $routeIds = $routeFares->pluck('route_id')->unique();
        $routeStopOrders = \App\Models\RouteStopOrder::whereIn('route_id', $routeIds)->get()->groupBy('route_id');

        foreach ($routeFares as $fare) {
            $orders = $routeStopOrders[$fare->route_id] ?? collect();
            $totalStops = $orders->count();
            $stopOrder = $orders->where('stop_id', $fare->to_stop_id)->first();
            
            if ($stopOrder && $totalStops > 0) {
                $fare->color = $this->getStopColor($stopOrder->stop_index, $totalStops);
            } else {
                $fare->color = '#EF4444'; // Fallback
            }
        }

        // Récupérer toutes les routes et véhicules pour la création
        $routes = \App\Models\Route::orderBy('name')->get(['id', 'name']);
        $vehicles = \App\Models\Vehicle::with('vehicleType')->orderBy('identifier')->get(['id', 'identifier', 'seat_count', 'vehicle_type_id']);

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
     * Génère une couleur rose dégradée en fonction de la distance (index)
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
