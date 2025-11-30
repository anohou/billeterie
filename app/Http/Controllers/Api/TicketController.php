<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\Trip;
use App\Models\TripSeatOccupancy;
use App\Services\SeatAllocator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::with(['trip', 'seller'])
            ->where('seller_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($tickets);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'trip_id' => 'required|uuid|exists:trips,id',
            'from_stop_id' => 'required|uuid|exists:stops,id',
            'to_stop_id' => 'required|uuid|exists:stops,id',
            'seats' => 'required|array|min:1',
            'seats.*' => 'integer|min:1',
            'passenger_name' => 'nullable|string|max:255',
            'passenger_phone' => 'nullable|string|max:20',
            'amount' => 'required|integer|min:0',
        ]);

        $trip = Trip::with('vehicle')->findOrFail($validated['trip_id']);
        
        // Vérifier que les places sont disponibles
        $occupiedSeats = TripSeatOccupancy::where('trip_id', $trip->id)
            ->pluck('seat_number')
            ->toArray();

        $conflictingSeats = array_intersect($validated['seats'], $occupiedSeats);
        if (!empty($conflictingSeats)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Certaines places sont déjà occupées: ' . implode(', ', $conflictingSeats)
                ], 422);
            }
            return back()->withErrors([
                'general' => 'Certaines places sont déjà occupées: ' . implode(', ', $conflictingSeats)
            ]);
        }

        // Vérifier que les places existent
        if (max($validated['seats']) > $trip->vehicle->seat_count) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Certaines places n\'existent pas'
                ], 422);
            }
            return back()->withErrors([
                'general' => 'Certaines places n\'existent pas'
            ]);
        }

        try {
            DB::beginTransaction();

            $tickets = [];
            foreach ($validated['seats'] as $seatNumber) {
                $ticket = Ticket::create([
                    'ticket_number' => 'TKT-' . strtoupper(Str::random(8)),
                    'trip_id' => $trip->id,
                    'vehicle_id' => $trip->vehicle_id,
                    'from_stop_id' => $validated['from_stop_id'],
                    'to_stop_id' => $validated['to_stop_id'],
                    'seat_number' => $seatNumber,
                    'passenger_name' => $validated['passenger_name'] ?? 'Passager',
                    'passenger_phone' => $validated['passenger_phone'] ?? '',
                    'price' => $validated['amount'] / count($validated['seats']), // Prix par place
                    'seller_id' => auth()->id(),
                    'station_id' => auth()->user()->station_id ?? null,
                    'qr_code' => 'QR-' . strtoupper(Str::random(12)),
                ]);

                // Marquer la place comme occupée
                TripSeatOccupancy::create([
                    'trip_id' => $trip->id,
                    'seat_number' => $seatNumber,
                    'ticket_id' => $ticket->id,
                ]);

                $tickets[] = $ticket;
            }

            DB::commit();

            // TODO: Broadcast seat map update via Reverb
            // event(new SeatMapUpdated($trip->id));

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tickets créés avec succès',
                    'tickets' => $tickets,
                    'total_amount' => $validated['amount'],
                    'print_url' => route('tickets.print-multiple'),
                    'ticket_ids' => collect($tickets)->pluck('id')->toArray()
                ], 201);
            }
            
            
            // Retourner une réponse Inertia avec flash pour impression
            return redirect()->back()->with([
                'flash' => [
                    'ticket_id' => $tickets[0]->id, // Premier ticket pour impression
                    'ticket_ids' => collect($tickets)->pluck('id')->toArray(),
                    'message' => 'Ticket créé avec succès'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Erreur création ticket: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Erreur lors de la création des tickets: ' . $e->getMessage()
                ], 500);
            }
            
            return back()->withErrors([
                'general' => 'Erreur lors de la création des tickets: ' . $e->getMessage()
            ]);
        }
    }

    public function show(Ticket $ticket)
    {
        try {
            $ticket->load(['trip.route', 'trip.vehicle', 'fromStop', 'toStop', 'seller']);
            
            $settings = null;
            try {
                $settings = \App\Models\TicketSetting::getSettings();
            } catch (\Exception $e) {
                \Log::error('Failed to get settings: ' . $e->getMessage());
                // If settings retrieval fails, use defaults
                $settings = [
                    'company_name' => 'TSR CI',
                    'phone_numbers' => ['+225 XX XX XX XX XX', '+225 XX XX XX XX XX'],
                    'footer_messages' => ['Valable pour ce voyage', 'Non remboursable'],
                    'print_qr_code' => false,
                    'qr_code_base_url' => null,
                ];
            }
            
            $ticketArray = $ticket->toArray();
            $ticketArray['settings'] = $settings;
            
            return response()->json($ticketArray);
        } catch (\Exception $e) {
            \Log::error('Error in TicketController@show: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Ticket $ticket)
    {
        if ($ticket->seller_id !== auth()->id()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        try {
            DB::beginTransaction();

            // Libérer la place
            TripSeatOccupancy::where('ticket_id', $ticket->id)->delete();
            
            // Supprimer le ticket
            $ticket->delete();

            DB::commit();

            // TODO: Broadcast seat map update via Reverb
            // event(new SeatMapUpdated($ticket->trip_id));

            return response()->json(['message' => 'Ticket annulé avec succès']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de l\'annulation'], 500);
        }
    }
}
