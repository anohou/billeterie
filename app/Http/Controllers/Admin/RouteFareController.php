<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RouteFare;
use App\Models\Route;
use App\Models\Stop;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RouteFareController extends Controller
{
    public function index()
    {
        $fares = RouteFare::with(['route', 'fromStop', 'toStop'])
            ->latest()
            ->get();

        $routes = Route::select('id', 'name')->get();
        $stops = Stop::select('id', 'name')->get();

        return Inertia::render('Admin/RouteFares/Index', [
            'fares' => $fares,
            'routes' => $routes,
            'stops' => $stops,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'route_id' => 'required|exists:routes,id',
            'from_stop_id' => 'required|exists:stops,id',
            'to_stop_id' => 'required|exists:stops,id|different:from_stop_id',
            'amount' => 'required|integer|min:0',
        ]);

        // Check for duplicate
        $exists = RouteFare::where('route_id', $request->route_id)
            ->where('from_stop_id', $request->from_stop_id)
            ->where('to_stop_id', $request->to_stop_id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['from_stop_id' => 'Ce tarif existe déjà pour ce trajet.']);
        }

        RouteFare::create($validated);

        return redirect()->back()->with('success', 'Tarif créé avec succès');
    }

    public function update(Request $request, $id)
    {
        $routeFare = RouteFare::findOrFail($id);

        $validated = $request->validate([
            'route_id' => 'required|exists:routes,id',
            'from_stop_id' => 'required|exists:stops,id',
            'to_stop_id' => 'required|exists:stops,id|different:from_stop_id',
            'amount' => 'required|integer|min:0',
        ]);

        // Check for duplicate excluding current
        $exists = RouteFare::where('route_id', $request->route_id)
            ->where('from_stop_id', $request->from_stop_id)
            ->where('to_stop_id', $request->to_stop_id)
            ->where('id', '!=', $routeFare->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['from_stop_id' => 'Ce tarif existe déjà pour ce trajet.']);
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
