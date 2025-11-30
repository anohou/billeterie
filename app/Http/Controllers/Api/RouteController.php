<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Route;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    /**
     * Liste tous les trajets disponibles
     * GET /api/routes
     */
    public function index()
    {
        $routes = Route::with(['originStation', 'destinationStation', 'routeStopOrders.stop.station'])
            ->where('active', true)
            ->get()
            ->map(function ($route) {
                return [
                    'id' => $route->id,
                    'name' => $route->name,
                    'origin' => [
                        'id' => $route->originStation->id,
                        'name' => $route->originStation->name,
                        'code' => $route->originStation->code,
                        'latitude' => $route->originStation->latitude,
                        'longitude' => $route->originStation->longitude,
                    ],
                    'destination' => [
                        'id' => $route->destinationStation->id,
                        'name' => $route->destinationStation->name,
                        'code' => $route->destinationStation->code,
                        'latitude' => $route->destinationStation->latitude,
                        'longitude' => $route->destinationStation->longitude,
                    ],
                    'stops' => $route->routeStopOrders->sortBy('stop_index')->map(function ($order) {
                        return [
                            'id' => $order->stop->id,
                            'name' => $order->stop->name,
                            'station' => [
                                'id' => $order->stop->station->id,
                                'name' => $order->stop->station->name,
                                'latitude' => $order->stop->station->latitude,
                                'longitude' => $order->stop->station->longitude,
                            ],
                            'index' => $order->stop_index,
                        ];
                    })->values(),
                    'active' => $route->active,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $routes,
            'message' => 'Trajets récupérés avec succès'
        ]);
    }

    /**
     * Affiche un trajet spécifique avec tous ses détails
     * GET /api/routes/{id}
     */
    public function show(string $id)
    {
        $route = Route::with([
            'originStation',
            'destinationStation',
            'routeStopOrders.stop.station',
            'routeFares'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $route->id,
                'name' => $route->name,
                'origin' => [
                    'id' => $route->originStation->id,
                    'name' => $route->originStation->name,
                    'code' => $route->originStation->code,
                    'city' => $route->originStation->city,
                    'address' => $route->originStation->address,
                    'latitude' => $route->originStation->latitude,
                    'longitude' => $route->originStation->longitude,
                ],
                'destination' => [
                    'id' => $route->destinationStation->id,
                    'name' => $route->destinationStation->name,
                    'code' => $route->destinationStation->code,
                    'city' => $route->destinationStation->city,
                    'address' => $route->destinationStation->address,
                    'latitude' => $route->destinationStation->latitude,
                    'longitude' => $route->destinationStation->longitude,
                ],
                'stops' => $route->routeStopOrders->sortBy('stop_index')->map(function ($order) {
                    return [
                        'id' => $order->stop->id,
                        'name' => $order->stop->name,
                        'station' => [
                            'id' => $order->stop->station->id,
                            'name' => $order->stop->station->name,
                            'city' => $order->stop->station->city,
                            'latitude' => $order->stop->station->latitude,
                            'longitude' => $order->stop->station->longitude,
                        ],
                        'index' => $order->stop_index,
                    ];
                })->values(),
                'fares' => $route->routeFares->map(function ($fare) {
                    return [
                        'from_stop_id' => $fare->from_stop_id,
                        'to_stop_id' => $fare->to_stop_id,
                        'amount' => $fare->amount,
                    ];
                }),
                'active' => $route->active,
            ],
            'message' => 'Trajet récupéré avec succès'
        ]);
    }
}
