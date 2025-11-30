<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Ticket;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Models\Station;
use App\Models\Route;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Get today's date
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        
        // Calculate statistics
        $totalSales = Ticket::count();
        $todaySales = Ticket::whereDate('created_at', $today)->count();
        $monthlySales = Ticket::where('created_at', '>=', $thisMonth)->count();
        
        // Revenue calculations (assuming each ticket has a price)
        $totalRevenue = Ticket::sum('price') ?? 0;
        $todayRevenue = Ticket::whereDate('created_at', $today)->sum('price') ?? 0;
        $monthlyRevenue = Ticket::where('created_at', '>=', $thisMonth)->sum('price') ?? 0;
        
        // Vehicle and trip statistics
        $totalVehicles = Vehicle::count();
        $activeTrips = Trip::where('departure_at', '>=', now())->count();
        $totalStations = Station::count();
        $totalRoutes = Route::count();
        
        // User statistics
        $totalUsers = User::count();
        $sellers = User::where('role', 'seller')->count();
        $supervisors = User::where('role', 'supervisor')->count();
        
        // Sales trend for the last 7 days
        $salesTrend = Ticket::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count
                ];
            });
        
        // Top selling routes
        $topRoutes = Route::withCount('trips')
            ->orderBy('trips_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($route) {
                return [
                    'name' => $route->name,
                    'trips' => $route->trips_count
                ];
            });
        
        // Vehicle occupancy rate
        $vehicleOccupancy = Vehicle::withCount(['trips as total_trips', 'trips as active_trips' => function ($query) {
            $query->where('departure_at', '>=', now());
        }])->get()->map(function ($vehicle) {
            return [
                'name' => $vehicle->identifier,
                'occupancy' => $vehicle->total_trips > 0 ? round(($vehicle->active_trips / $vehicle->total_trips) * 100, 2) : 0
            ];
        });

        return Inertia::render('Dashboards/Admin', [
            'stats' => [
                'totalSales' => $totalSales,
                'todaySales' => $todaySales,
                'monthlySales' => $monthlySales,
                'totalRevenue' => $totalRevenue,
                'todayRevenue' => $todayRevenue,
                'monthlyRevenue' => $monthlyRevenue,
                'totalVehicles' => $totalVehicles,
                'activeTrips' => $activeTrips,
                'totalStations' => $totalStations,
                'totalRoutes' => $totalRoutes,
                'totalUsers' => $totalUsers,
                'sellers' => $sellers,
                'supervisors' => $supervisors,
            ],
            'charts' => [
                'salesTrend' => $salesTrend,
                'topRoutes' => $topRoutes,
                'vehicleOccupancy' => $vehicleOccupancy,
            ],
            'links' => [
                ['label' => 'Stations', 'href' => '/admin/stations'],
                ['label' => 'Routes', 'href' => '/admin/routes'],
                ['label' => 'Vehicles', 'href' => '/admin/vehicles'],
                ['label' => 'Trips', 'href' => '/admin/trips'],
                ['label' => 'Assignments', 'href' => '/admin/assignments'],
            ],
        ]);
    }
}
