<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\Route;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TripController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $trips = Trip::with(['route', 'vehicle'])->orderBy('departure_at')->paginate(20);
        $routes = Route::orderBy('name')->get(['id', 'name']);
        $vehicles = Vehicle::orderBy('identifier')->get(['id', 'identifier']);
        return Inertia::render('Admin/Trips/Index', [
            'trips' => $trips,
            'routes' => $routes,
            'vehicles' => $vehicles,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Trips/Form', [
            'routes' => Route::orderBy('name')->get(['id', 'name']),
            'vehicles' => Vehicle::orderBy('identifier')->get(['id', 'identifier'])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'route_id' => 'required|uuid|exists:routes,id',
            'vehicle_id' => 'required|uuid|exists:vehicles,id',
            'departure_at' => 'required|date',
            'status' => 'required|in:scheduled,boarding,departed,arrived,cancelled',
        ]);
        
        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'scheduled';
        }
        
        $trip = Trip::create($data);

        return redirect()->route('seller.ticketing')->with('success', 'Voyage créé avec succès!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Trip $trip)
    {
        return redirect()->route('admin.trips.edit', $trip);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Trip $trip)
    {
        return Inertia::render('Admin/Trips/Form', [
            'trip' => $trip,
            'routes' => Route::orderBy('name')->get(['id', 'name']),
            'vehicles' => Vehicle::orderBy('identifier')->get(['id', 'identifier'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Trip $trip)
    {
        $data = $request->validate([
            'route_id' => 'required|uuid|exists:routes,id',
            'vehicle_id' => 'required|uuid|exists:vehicles,id',
            'departure_at' => 'required|date',
            'status' => 'required|in:scheduled,boarding,departed,arrived,cancelled',
        ]);
        $trip->update($data);
        return redirect()->route('admin.trips.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trip $trip)
    {
        $trip->delete();
        return back();
    }
}
