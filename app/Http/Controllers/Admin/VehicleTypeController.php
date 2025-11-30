<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VehicleTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vehicleTypes = VehicleType::orderBy('name')->paginate(20);
        return Inertia::render('Admin/VehicleTypes/Index', [
            'vehicleTypes' => $vehicleTypes,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/VehicleTypes/Form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:vehicle_types,name',
            'seat_count' => 'required|integer|min:1',
            'seat_configuration' => 'required|string', // e.g., "2+2"
            'door_positions' => 'nullable|array', // e.g., [1, 23, 24]
            'door_positions.*' => 'integer',
            'last_row_seats' => 'nullable|integer|min:1',
        ]);
        $data['seat_map'] = $this->generateSeatMap($data);
        VehicleType::create($data);
        return redirect()->route('admin.vehicle-types.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(VehicleType $vehicleType)
    {
        return redirect()->route('admin.vehicle-types.edit', $vehicleType);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VehicleType $vehicleType)
    {
        return Inertia::render('Admin/VehicleTypes/Form', [
            'vehicleType' => $vehicleType,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VehicleType $vehicleType)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:vehicle_types,name,'.$vehicleType->id.',id',
            'seat_count' => 'required|integer|min:1',
            'seat_configuration' => 'required|string',
            'door_positions' => 'nullable|array',
            'door_positions.*' => 'integer',
            'last_row_seats' => 'nullable|integer|min:1',
        ]);
        $data['seat_map'] = $this->generateSeatMap($data);
        $vehicleType->update($data);
        return redirect()->route('admin.vehicle-types.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VehicleType $vehicleType)
    {
        $vehicleType->delete();
        return back();
    }
    private function generateSeatMap($data)
    {
        $seatCount = (int) $data['seat_count'];
        $configStr = $data['seat_configuration']; // e.g., "2+2"
        $doorPositions = $data['door_positions'] ?? [];
        $lastRowSeats = (int) ($data['last_row_seats'] ?? 5);

        // Parse configuration
        $parts = explode('+', $configStr);
        $leftCount = (int) ($parts[0] ?? 2);
        $rightCount = (int) ($parts[1] ?? 2);

        $seatMap = [];
        $currentSeatNum = 1;
        $rowIndex = 0;

        // Calculate seats to fill before the last row
        $seatsToFill = $seatCount - $lastRowSeats;
        $filledSeats = 0;
        $slotsPerRow = $leftCount + $rightCount;
        
        // Keep generating rows until we have filled all seats
        while ($filledSeats < $seatsToFill) {
            $row = [];
            
            // Calculate start slot for this row (1-based)
            // Row 0 is driver, so first passenger row (rowIndex 1) starts at slot 1
            $rowStartSlot = ($rowIndex - 1) * $slotsPerRow + 1;

            // Left Side
            if ($rowIndex === 0) {
                // Driver Row
                $row[] = ['type' => 'driver', 'label' => 'Chauffeur'];
                // Fill remaining left slots with empty space if any
                for ($i = 1; $i < $leftCount; $i++) {
                    $row[] = ['type' => 'empty'];
                }
            } else {
                // Standard Row Left
                for ($i = 0; $i < $leftCount; $i++) {
                    $currentSlot = $rowStartSlot + $i;
                    // Check if this slot is a door
                    if (in_array($currentSlot, $doorPositions)) {
                        $row[] = ['type' => 'door'];
                    } else {
                        // Always generate seats with sequential numbers until we reach the target
                        if ($filledSeats < $seatsToFill) {
                            $row[] = ['type' => 'seat', 'number' => (string)$currentSeatNum++];
                            $filledSeats++;
                        } else {
                            $row[] = ['type' => 'empty'];
                        }
                    }
                }
            }

            // Aisle
            $row[] = ['type' => 'aisle'];

            // Right Side
            if ($rowIndex === 0) {
                 // Driver Row Right Side (Front Door or Co-Driver)
                 // Front door is slot 0
                 if (in_array(0, $doorPositions)) {
                     $row[] = ['type' => 'door', 'label' => 'Porte'];
                     // Fill remaining right slots
                     for ($i = 1; $i < $rightCount; $i++) {
                         $row[] = ['type' => 'empty'];
                     }
                 } else {
                     // Co-Driver / Standard
                     for ($i = 0; $i < $rightCount; $i++) {
                        if ($filledSeats < $seatsToFill) {
                            $row[] = ['type' => 'seat', 'number' => (string)$currentSeatNum++];
                            $filledSeats++;
                        } else {
                            $row[] = ['type' => 'empty'];
                        }
                     }
                 }
            } else {
                // Standard Row Right
                for ($i = 0; $i < $rightCount; $i++) {
                    $currentSlot = $rowStartSlot + $leftCount + $i;
                    // Check if this slot is a door
                    if (in_array($currentSlot, $doorPositions)) {
                        $row[] = ['type' => 'door'];
                    } else {
                        // Always generate seats with sequential numbers until we reach the target
                        if ($filledSeats < $seatsToFill) {
                            $row[] = ['type' => 'seat', 'number' => (string)$currentSeatNum++];
                            $filledSeats++;
                        } else {
                            $row[] = ['type' => 'empty'];
                        }
                    }
                }
            }

            $seatMap[] = $row;
            $rowIndex++;
        }

        // Last Row - only add if we still have seats remaining after standard rows
        $remainingSeats = $seatCount - $filledSeats;
        if ($remainingSeats > 0) {
            $lastRow = [];
            for ($i = 0; $i < $remainingSeats; $i++) {
                $lastRow[] = ['type' => 'seat', 'number' => (string)$currentSeatNum++];
            }
            $seatMap[] = $lastRow;
        }

        return $seatMap;
    }
}
