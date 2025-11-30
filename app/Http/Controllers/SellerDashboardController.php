<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Trip;
use App\Models\Route as BusRoute;

class SellerDashboardController extends Controller
{
    public function index()
    {
        $user = request()->user();
        $routeIds = $user->routeAssignments()->pluck('route_id');
        $trips = Trip::whereIn('route_id', $routeIds)
            ->orderBy('departure_at','asc')
            ->limit(10)
            ->get(['id','route_id','vehicle_id','departure_at','status']);
        return Inertia::render('Dashboards/Seller', [
            'trips' => $trips,
        ]);
    }
}
