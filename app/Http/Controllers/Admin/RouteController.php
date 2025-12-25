<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Route as BusRoute;
use App\Models\Station;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RouteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $routes = BusRoute::with([
            'originStation', 
            'destinationStation',
            'routeStopOrders.stop.station',
            'trips.vehicle'
        ])
        ->withCount(['trips', 'routeStopOrders'])
        ->orderBy('name')
        ->paginate(50);
            
        $stations = Station::orderBy('name')->get(['id', 'name', 'city']);
        $stops = \App\Models\Stop::with('station')->orderBy('name')->get()->map(function ($stop) {
            return [
                'id' => $stop->id,
                'name' => $stop->name,
                'city' => $stop->station ? $stop->station->city : null,
            ];
        });
        
        // Get all fares for frontend to compute matched fares per route
        $fares = \App\Models\RouteFare::with(['fromStop', 'toStop'])->get();
        
        return Inertia::render('Admin/Routes/Index', [ 
            'routes' => $routes,
            'stations' => $stations,
            'stops' => $stops,
            'fares' => $fares
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Routes/Form', [
            'stations' => Station::orderBy('name')->get(['id','name','city','code'])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'origin_station_id' => 'required|uuid|exists:stations,id',
            'destination_station_id' => 'required|uuid|exists:stations,id',
            'active' => 'boolean',
        ]);
        BusRoute::create($data);
        return redirect()->route('admin.routes.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(BusRoute $route)
    {
        return redirect()->route('admin.routes.edit', $route);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BusRoute $route)
    {
        return Inertia::render('Admin/Routes/Form', [
            'routeItem' => $route,
            'stations' => Station::orderBy('name')->get(['id','name','city','code'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BusRoute $route)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'origin_station_id' => 'required|uuid|exists:stations,id',
            'destination_station_id' => 'required|uuid|exists:stations,id',
            'active' => 'boolean',
        ]);
        $route->update($data);
        return redirect()->route('admin.routes.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusRoute $route)
    {
        $route->delete();
        return back();
    }
}
