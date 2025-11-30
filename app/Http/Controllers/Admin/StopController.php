<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stop;
use App\Models\Station;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StopController extends Controller
{
    public function index()
    {
        $stops = Stop::with('station')->orderBy('name')->paginate(20);
        $stations = Station::orderBy('name')->get(['id', 'name']);
        return Inertia::render('Admin/Stops/Index', [
            'stops' => $stops,
            'stations' => $stations,
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Stops/Form', [
            'stations' => Station::orderBy('name')->get(['id', 'name'])
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'station_id' => 'nullable|exists:stations,id',
        ]);
        Stop::create($data);
        return redirect()->route('admin.stops.index');
    }

    public function show(Stop $stop)
    {
        return redirect()->route('admin.stops.edit', $stop);
    }

    public function edit(Stop $stop)
    {
        return Inertia::render('Admin/Stops/Form', [
            'stop' => $stop,
            'stations' => Station::orderBy('name')->get(['id', 'name'])
        ]);
    }

    public function update(Request $request, Stop $stop)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'station_id' => 'nullable|exists:stations,id',
        ]);
        $stop->update($data);
        return redirect()->route('admin.stops.index');
    }

    public function destroy(Stop $stop)
    {
        $stop->delete();
        return back();
    }
}

