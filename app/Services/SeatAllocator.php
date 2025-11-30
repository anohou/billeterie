<?php

namespace App\Services;

use App\Models\Stop;
use App\Models\Trip;
use Illuminate\Support\Collection;

class SeatAllocator
{
    /**
     * Suggest the best available seats for a given trip and destination.
     *
     * @param Trip $trip The trip for which to suggest seats.
     * @param Stop $destinationStop The passenger's destination stop.
     * @param int $quantity The number of seats to suggest.
     * @return array An array of suggested seat numbers.
     */
    public function suggestSeats(Trip $trip, Stop $destinationStop, int $quantity = 1): array
    {
        // 1. Load vehicle with vehicle type to get door positions
        $trip->load(['vehicle.vehicleType', 'route.stops']);
        $vehicle = $trip->vehicle;
        $vehicleType = $vehicle->vehicleType;
        $route = $trip->route;
        
        $seatCount = $vehicle->seat_count;
        
        // Use vehicle type door positions (more accurate)
        $doorPositions = $vehicleType->door_positions ?? $vehicle->door_positions ?? [1];
        
        // Get seats that are already occupied for any segment of this trip
        $occupiedSeats = $trip->tripSeatOccupancies()->pluck('seat_number')->toArray();

        // 2. Determine the destination rank
        $stopsOrder = $route->stops->pluck('id')->toArray();
        $destinationIndex = array_search($destinationStop->id, $stopsOrder);
        $totalStops = count($stopsOrder);
        
        // Rank is from 0 (first stop) to 1 (last stop)
        $destinationRank = ($totalStops > 1) ? ($destinationIndex / ($totalStops - 1)) : 1;

        // 3. Generate a list of all available seats with their scores
        $availableSeats = collect(range(1, $seatCount))
            ->diff($occupiedSeats)
            ->map(function ($seatNumber) use ($doorPositions, $destinationRank, $vehicleType) {
                $distanceToDoor = $this->calculateMinDistanceToDoor($seatNumber, $doorPositions);
                $score = $this->calculateSeatScore($seatNumber, $distanceToDoor, $destinationRank, $vehicleType);
                
                return [
                    'number' => $seatNumber,
                    'distance_to_door' => $distanceToDoor,
                    'score' => $score,
                ];
            });

        // 4. Sort seats by score (higher is better)
        $sortedSeats = $availableSeats->sortByDesc('score');

        // 5. Return the top N suggested seats
        return $sortedSeats->pluck('number')->take($quantity)->values()->toArray();
    }
    
    /**
     * Calculate a composite score for a seat based on multiple factors.
     *
     * @param int $seatNumber
     * @param int $distanceToDoor
     * @param float $destinationRank (0 = first stop, 1 = last stop)
     * @param \App\Models\VehicleType $vehicleType
     * @return float
     */
    private function calculateSeatScore(int $seatNumber, int $distanceToDoor, float $destinationRank, $vehicleType): float
    {
        $score = 0;
        
        // Factor 1: Door proximity (weighted by destination rank)
        // Early stops (rank < 0.3): High weight on being close to door
        // Mid stops (0.3-0.7): Moderate weight
        // Late stops (rank > 0.7): Prefer seats away from door
        
        if ($destinationRank < 0.3) {
            // Early stop: closer to door is better (inverse distance)
            $doorScore = 100 - ($distanceToDoor * 2);
            $score += $doorScore * 0.8; // 80% weight
        } elseif ($destinationRank < 0.7) {
            // Mid stop: balanced approach
            $doorScore = 50 - abs($distanceToDoor - 10);
            $score += $doorScore * 0.5; // 50% weight
        } else {
            // Late stop: farther from door is better
            $doorScore = $distanceToDoor * 1.5;
            $score += $doorScore * 0.7; // 70% weight
        }
        
        // Factor 2: Seat position comfort (prefer middle rows for long trips)
        $seatCount = $vehicleType->seat_count;
        $middleSeat = $seatCount / 2;
        $distanceFromMiddle = abs($seatNumber - $middleSeat);
        
        if ($destinationRank > 0.5) {
            // For longer trips, prefer seats closer to middle
            $comfortScore = 30 - ($distanceFromMiddle * 0.5);
            $score += $comfortScore * 0.3; // 30% weight
        }
        
        return $score;
    }

    /**
     * Calculate the minimum distance from a seat to any door.
     *
     * @param int $seatNumber
     * @param array $doorPositions
     * @return int
     */
    private function calculateMinDistanceToDoor(int $seatNumber, array $doorPositions): int
    {
        if (empty($doorPositions)) {
            return PHP_INT_MAX; // Return a large number if no doors are defined
        }

        $minDistance = PHP_INT_MAX;
        foreach ($doorPositions as $door) {
            $distance = abs($seatNumber - $door);
            if ($distance < $minDistance) {
                $minDistance = $distance;
            }
        }

        return $minDistance;
    }
}
