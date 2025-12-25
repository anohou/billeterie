<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RouteFare;
use App\Models\Stop;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RouteFareController extends Controller
{
    public function index()
    {
        $fares = RouteFare::with(['fromStop.station', 'toStop.station'])
            ->latest()
            ->get();

        $stops = Stop::with('station')->get()->map(function ($stop) {
            return [
                'id' => $stop->id,
                'name' => $stop->name,
                'station_name' => $stop->station?->name,
                'city' => $stop->station?->city,
            ];
        });

        return Inertia::render('Admin/RouteFares/Index', [
            'fares' => $fares,
            'stops' => $stops,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_stop_id' => 'required|exists:stops,id',
            'to_stop_id' => 'required|exists:stops,id|different:from_stop_id',
            'amount' => 'required|integer|min:0',
            'is_bidirectional' => 'boolean',
        ]);

        // Check for duplicate (direct or reverse if bidirectional)
        $exists = RouteFare::where('from_stop_id', $request->from_stop_id)
            ->where('to_stop_id', $request->to_stop_id)
            ->exists();

        // Also check reverse direction
        $reverseExists = RouteFare::where('from_stop_id', $request->to_stop_id)
            ->where('to_stop_id', $request->from_stop_id)
            ->exists();

        if ($exists || $reverseExists) {
            return back()->withErrors(['from_stop_id' => 'Ce tarif existe déjà pour ce trajet (ou son inverse).']);
        }

        RouteFare::create($validated);

        return redirect()->back()->with('success', 'Tarif créé avec succès');
    }

    public function update(Request $request, $id)
    {
        $routeFare = RouteFare::findOrFail($id);

        $validated = $request->validate([
            'from_stop_id' => 'required|exists:stops,id',
            'to_stop_id' => 'required|exists:stops,id|different:from_stop_id',
            'amount' => 'required|integer|min:0',
            'is_bidirectional' => 'boolean',
        ]);

        // Check for duplicate excluding current (direct)
        $exists = RouteFare::where('from_stop_id', $request->from_stop_id)
            ->where('to_stop_id', $request->to_stop_id)
            ->where('id', '!=', $routeFare->id)
            ->exists();

        // Also check reverse direction excluding current
        $reverseExists = RouteFare::where('from_stop_id', $request->to_stop_id)
            ->where('to_stop_id', $request->from_stop_id)
            ->where('id', '!=', $routeFare->id)
            ->exists();

        if ($exists || $reverseExists) {
            return back()->withErrors(['from_stop_id' => 'Ce tarif existe déjà pour ce trajet (ou son inverse).']);
        }

        $routeFare->update($validated);

        return redirect()->back()->with('success', 'Tarif mis à jour avec succès');
    }

    public function destroy($id)
    {
        $routeFare = RouteFare::findOrFail($id);
        $routeFare->delete();

        return redirect()->back()->with('success', 'Tarif supprimé avec succès');
    }
}
