<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stations = Station::with([
            'userAssignments.user',
            'originRoutes.destinationStation',
            'originRoutes.originStation',
            'originRoutes.routeStopOrders.stop.station',
            'destinationRoutes.originStation',
            'destinationRoutes.destinationStation',
            'destinationRoutes.routeStopOrders.stop.station',
            // Load all routes this station's stops are on, with full stop ordering
            'stops.routeStopOrders.route.routeStopOrders.stop.station',
            'stops.routeStopOrders.route.originStation',
            'stops.routeStopOrders.route.destinationStation'
        ])
        ->withCount(['userAssignments', 'originRoutes', 'destinationRoutes', 'stops'])
        ->orderBy('name')
        ->paginate(50);
        
        return Inertia::render('Admin/Stations/Index', [
            'stations' => $stations,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/Stations/Form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'code' => 'nullable|string|unique:stations,code',
            'city' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'active' => 'boolean',
        ]);
        Station::create($data);
        return redirect()->route('admin.stations.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(Station $station)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Station $station)
    {
        return Inertia::render('Admin/Stations/Form', [
            'station' => $station,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Station $station)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'code' => 'nullable|string|unique:stations,code,'.$station->id.',id',
            'city' => 'nullable|string',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'active' => 'boolean',
        ]);
        $station->update($data);
        return redirect()->route('admin.stations.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Station $station)
    {
        $station->delete();
        return back();
    }
}
