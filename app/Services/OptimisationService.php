<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\Stop;
use App\Models\RouteStopOrder;
use App\Models\Ticket;
use Illuminate\Support\Collection;

class OptimisationService
{
    /**
     * Obtient les suggestions de sièges optimaux pour un voyage et une destination
     * 
     * @param string $tripId ID du voyage
     * @param string $destinationStopId ID de l'arrêt de destination
     * @param int $maxSuggestions Nombre maximum de suggestions (défaut: 5)
     * @return array Tableau de suggestions avec seat_number, score et reason
     */
    public function getSuggestedSeats(string $tripId, string $destinationStopId, int $maxSuggestions = 5, ?string $boardingStopId = null): array
    {
        $trip = Trip::with(['vehicle.vehicleType', 'route.routeStopOrders'])->findOrFail($tripId);
        
        // Si le voyage est en mode "bulk", retourner un tableau vide (pas de suggestions)
        if ($trip->isBulk()) {
            return [];
        }

        // Récupérer les sièges déjà occupés avec leurs destinations et origines
        $occupiedSeatsData = Ticket::where('trip_id', $tripId)
            ->where('status', '!=', 'cancelled')
            ->with(['toStop', 'fromStop'])
            ->get()
            ->keyBy('seat_number');

        // Récupérer la configuration du véhicule
        $vehicleType = $trip->vehicle->vehicleType;
        $totalSeats = $vehicleType->seat_count;
        $doorPositions = $vehicleType->door_positions ?? [0];
        
        // Calculer l'index de l'arrêt de destination
        $destinationIndex = $this->getStopIndex($trip->route_id, $destinationStopId);
        
        // Get boarding stop index (for semi-intelligent mode)
        $boardingIndex = $boardingStopId ? $this->getStopIndex($trip->route_id, $boardingStopId) : 0;
        
        // Get total stops on route
        $totalStops = RouteStopOrder::where('route_id', $trip->route_id)->count();
        
        // Calculate trip distance ratio (0 = first stop, 1 = last stop)
        $tripDistanceRatio = $totalStops > 1 ? $destinationIndex / ($totalStops - 1) : 0;
        
        // Define tronçons (route segments) based on total stops
        // Short: 0-33%, Medium: 33-66%, Long: 66-100%
        $troncon = 'long'; // default
        if ($tripDistanceRatio <= 0.33) {
            $troncon = 'short';
        } elseif ($tripDistanceRatio <= 0.66) {
            $troncon = 'medium';
        }
        
        // Create zones around each door
        // Each door has a "near zone" (within 5 seats) and "far zone" (beyond 5 seats)
        $seatZones = $this->createDoorBasedZones($totalSeats, $doorPositions);
        
        // Determine available seats based on booking type
        $availableSeats = [];
        
        if ($trip->isSemiIntelligent()) {
            // SEMI-INTELLIGENT MODE: Seats can be reused
            // A seat is available if:
            // 1. It's completely empty, OR
            // 2. It's occupied by someone who will disembark BEFORE the new passenger boards
            
            for ($seatNumber = 1; $seatNumber <= $totalSeats; $seatNumber++) {
                if (!$occupiedSeatsData->has($seatNumber)) {
                    // Seat is completely empty
                    $availableSeats[] = $seatNumber;
                } else {
                    // Seat is occupied - check if it will be free when new passenger boards
                    $currentOccupant = $occupiedSeatsData[$seatNumber];
                    $occupantDestIndex = $this->getStopIndex($trip->route_id, $currentOccupant->to_stop_id);
                    
                    // If current occupant gets off before or at the boarding stop, seat will be available
                    if ($occupantDestIndex < $boardingIndex) {
                        $availableSeats[] = $seatNumber;
                    }
                }
            }
        } else {
            // INTELLIGENT MODE (seat_assignment): Standard logic - only truly empty seats
            for ($seatNumber = 1; $seatNumber <= $totalSeats; $seatNumber++) {
                if (!$occupiedSeatsData->has($seatNumber)) {
                    $availableSeats[] = $seatNumber;
                }
            }
        }
        
        // Calculate scores for available seats
        $seatScores = [];
        foreach ($availableSeats as $seatNumber) {
            $score = $this->calculateTronconBasedScore(
                $seatNumber,
                $troncon,
                $destinationIndex,
                $seatZones,
                $doorPositions,
                $totalSeats,
                $vehicleType->seat_configuration,
                $occupiedSeatsData,
                $trip->route_id,
                $boardingIndex
            );

            $seatScores[] = [
                'seat_number' => $seatNumber,
                'score' => $score['score'],
                'reason' => $score['reason']
            ];
        }

        // Trier par score décroissant et prendre les N meilleurs
        usort($seatScores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return array_slice($seatScores, 0, $maxSuggestions);
    }

    /**
     * Create zones around each door
     * Returns array with 'nearest_door', 'distance_to_door', and 'zone_type' for each seat
     */
    private function createDoorBasedZones(int $totalSeats, array $doorPositions): array
    {
        $zones = [];
        
        for ($seatNumber = 1; $seatNumber <= $totalSeats; $seatNumber++) {
            $minDistance = PHP_INT_MAX;
            $nearestDoor = null;
            
            // Find nearest door for this seat
            foreach ($doorPositions as $doorPosition) {
                $effectiveDoorPos = $doorPosition === 0 ? 1 : $doorPosition;
                $distance = abs($seatNumber - $effectiveDoorPos);
                
                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $nearestDoor = $effectiveDoorPos;
                }
            }
            
            // Classify zone based on distance to nearest door
            $zoneType = 'far'; // default
            if ($minDistance <= 3) {
                $zoneType = 'very_near'; // Within 3 seats of door
            } elseif ($minDistance <= 6) {
                $zoneType = 'near'; // Within 6 seats of door
            } elseif ($minDistance <= 10) {
                $zoneType = 'medium'; // Within 10 seats of door
            }
            
            $zones[$seatNumber] = [
                'nearest_door' => $nearestDoor,
                'distance_to_door' => $minDistance,
                'zone_type' => $zoneType
            ];
        }
        
        return $zones;
    }

    /**
     * Calculate seat score based on tronçon (route segment) and door proximity
     */
    private function calculateTronconBasedScore(
        int $seatNumber,
        string $troncon,
        int $destinationIndex,
        array $seatZones,
        array $doorPositions,
        int $totalSeats,
        string $configuration,
        Collection $occupiedSeatsData,
        string $routeId,
        int $boardingIndex = 0
    ): array {
        $score = 100;
        $reason = '';

        $zone = $seatZones[$seatNumber];
        $zoneType = $zone['zone_type'];
        $distanceToDoor = $zone['distance_to_door'];

        // TRONÇON-BASED SCORING
        // Short trips: MUST be near doors (any door)
        // Medium trips: Can be medium distance from doors
        // Long trips: Can be far from doors (back of bus)
        
        if ($troncon === 'short') {
            // Short trip: Prioritize seats very close to ANY door
            if ($zoneType === 'very_near') {
                $score += 120; // Highest priority
                $reason = 'Très proche de la porte - optimal pour trajet court';
            } elseif ($zoneType === 'near') {
                $score += 80;
                $reason = 'Proche de la porte - bon pour trajet court';
            } elseif ($zoneType === 'medium') {
                $score += 20;
                $reason = 'Distance moyenne de la porte';
            } else {
                $score -= 60; // Heavy penalty for being far from door
                $reason = 'Loin de la porte - non optimal pour trajet court';
            }
            
        } elseif ($troncon === 'medium') {
            // Medium trip: Prefer near to medium zones
            if ($zoneType === 'very_near') {
                $score += 60;
                $reason = 'Proche de la porte - bon pour trajet moyen';
            } elseif ($zoneType === 'near') {
                $score += 80; // Best for medium
                $reason = 'Bonne distance de la porte pour trajet moyen';
            } elseif ($zoneType === 'medium') {
                $score += 60;
                $reason = 'Position acceptable pour trajet moyen';
            } else {
                $score += 10;
                $reason = 'Position arrière pour trajet moyen';
            }
            
        } else { // long trip
            // Long trip: Prefer far zones (back of bus), away from door traffic
            if ($zoneType === 'very_near') {
                $score += 10;
                $reason = 'Proche porte - plus de passage';
            } elseif ($zoneType === 'near') {
                $score += 30;
                $reason = 'Position acceptable pour trajet long';
            } elseif ($zoneType === 'medium') {
                $score += 70;
                $reason = 'Bonne position pour trajet long';
            } else {
                $score += 100; // Best for long trips
                $reason = 'Position arrière - optimal pour trajet long';
            }
        }

        // Check if this seat would block passengers who disembark before us
        $wouldBlockPassengers = false;
        $seatType = $this->getSeatType($seatNumber, $configuration, $totalSeats);
        
        if ($seatType === 'window') {
            // Check if aisle seat next to us is occupied by someone leaving earlier
            $aisleSeats = $this->getAdjacentAisleSeats($seatNumber, $configuration, $totalSeats);
            foreach ($aisleSeats as $aisleSeat) {
                if ($occupiedSeatsData->has($aisleSeat)) {
                    $occupantDestIndex = $this->getStopIndex($routeId, $occupiedSeatsData[$aisleSeat]->to_stop_id);
                    if ($occupantDestIndex < $destinationIndex) {
                        $wouldBlockPassengers = true;
                        break;
                    }
                }
            }
        }

        if ($wouldBlockPassengers) {
            $score -= 100; // Heavy penalty for blocking
            $reason = 'Bloquerait un passager qui descend avant vous';
        }

        // Seat type bonus (aisle is generally better for easy exit)
        if ($seatType === 'aisle') {
            $score += 20;
        } elseif ($seatType === 'window') {
            $score += 5;
        }

        return [
            'score' => round($score, 2),
            'reason' => $reason
        ];
    }

    /**
     * Get adjacent aisle seats for a window seat
     */
    private function getAdjacentAisleSeats(int $seatNumber, string $configuration, int $totalSeats): array
    {
        $parts = array_map('intval', explode('+', $configuration));
        $seatsPerRow = array_sum($parts);
        
        if ($seatsPerRow == 0) return [];

        $colIndex = ($seatNumber - 1) % $seatsPerRow;
        $rowStart = $seatNumber - $colIndex;
        
        $aisleSeats = [];
        $currentCol = 0;
        
        foreach ($parts as $partIndex => $partWidth) {
            if ($colIndex >= $currentCol && $colIndex < $currentCol + $partWidth) {
                // Found our block
                $posInPart = $colIndex - $currentCol;
                
                // If we're at the left edge of the block, aisle is to our right
                if ($posInPart === 0 && $partIndex > 0) {
                    $aisleSeats[] = $seatNumber + 1;
                }
                // If we're at the right edge of the block, aisle is to our left
                if ($posInPart === $partWidth - 1 && $partIndex < count($parts) - 1) {
                    $aisleSeats[] = $seatNumber - 1;
                }
                break;
            }
            $currentCol += $partWidth;
        }
        
        return array_filter($aisleSeats, fn($s) => $s >= 1 && $s <= $totalSeats);
    }

    /**
     * Calcule le score d'un siège en fonction de la destination
     * 
     * @param int $seatNumber Numéro du siège
     * @param int $destinationIndex Index de l'arrêt de destination (0 = premier arrêt)
     * @param array $doorPositions Positions des portes
     * @param int $totalSeats Nombre total de sièges
     * @param string $configuration Configuration du véhicule (ex: "2+2", "3+2")
     * @return array Score et raison
     */
    private function calculateSeatScore(
        int $seatNumber,
        int $destinationIndex,
        array $doorPositions,
        int $totalSeats,
        string $configuration
    ): array {
        $score = 100; // Score de base
        $reason = '';

        // --- Déterminer le type de siège ---
        $seatType = $this->getSeatType($seatNumber, $configuration, $totalSeats);
        
        // --- Calculer la distance minimale à TOUTES les portes (y compris avant) ---
        $minDistanceToDoor = PHP_INT_MAX;
        $nearestDoor = null;
        
        foreach ($doorPositions as $doorPosition) {
            // Traiter position 0 comme position 1 (porte avant)
            $effectiveDoorPos = $doorPosition === 0 ? 1 : $doorPosition;
            
            $distance = abs($seatNumber - $effectiveDoorPos);
            if ($distance < $minDistanceToDoor) {
                $minDistanceToDoor = $distance;
                $nearestDoor = $effectiveDoorPos;
            }
        }

        // --- Déterminer le côté du bus (gauche/droite) ---
        $parts = array_map('intval', explode('+', $configuration));
        $seatsPerRow = array_sum($parts);
        
        // Pour une config 2+2: 
        // Rangée 1: sièges 1,2 (gauche), 3,4 (droite)
        // Rangée 2: sièges 5,6 (gauche), 7,8 (droite)
        $positionInRow = ($seatNumber - 1) % $seatsPerRow;
        
        // Sièges impairs (1,3,5,7...) et pairs (2,4,6,8...) alternent
        // Pour 2+2: positions 0,1 = gauche, positions 2,3 = droite
        $isLeftSide = $positionInRow < ($seatsPerRow / 2);
        
        // Déterminer le côté de la porte la plus proche
        // Convention: portes aux numéros impairs = droite, pairs = gauche
        $nearestDoorIsRight = ($nearestDoor % 2 === 1);
        $seatIsRight = !$isLeftSide;
        $isSameSideAsDoor = ($nearestDoorIsRight === $seatIsRight);

        // --- LOGIQUE PRINCIPALE: Facteur de blocage pour trajets courts ---
        
        $isShortTrip = $destinationIndex <= 1;
        $isMediumTrip = $destinationIndex >= 2 && $destinationIndex <= 3;
        $isLongTrip = $destinationIndex >= 4;
        
        if ($isShortTrip) {
            // TRAJET COURT: Priorité absolue = ne pas bloquer d'autres passagers
            
            if ($seatType === 'aisle') {
                // AISLE SEAT: Vérifier si on est du même côté que la porte
                
                if ($isSameSideAsDoor) {
                    // OPTIMAL: Siège couloir du même côté que la porte
                    $score += 60; // Énorme bonus
                    $reason = 'Couloir côté porte - sortie directe';
                    
                    // Bonus supplémentaire si très proche de la porte
                    if ($minDistanceToDoor <= 2) {
                        $score += 40;
                        $reason = 'Couloir + très proche porte - sortie optimale';
                    } elseif ($minDistanceToDoor <= 5) {
                        $score += 20;
                    }
                } else {
                    // Siège couloir mais côté opposé à la porte
                    // Le passager doit traverser l'allée
                    $score += 10; // Petit bonus
                    $reason = 'Couloir côté opposé - doit traverser';
                }
                
            } elseif ($seatType === 'window') {
                // WINDOW SEAT: Nécessite de faire bouger le passager du couloir
                $score -= 40;
                $reason = 'Fenêtre - nécessite de déranger le voisin';
                
                if ($minDistanceToDoor > 5) {
                    $score -= 20;
                }
                
            } else {
                // MIDDLE SEAT
                $score -= 30;
                $reason = 'Milieu - nécessite de déranger plusieurs voisins';
            }
            
        } elseif ($isMediumTrip) {
            // TRAJET MOYEN
            
            if ($seatType === 'aisle') {
                $score += 20;
                $reason = 'Côté couloir - bon compromis';
            } elseif ($seatType === 'window') {
                $score += 15;
                $reason = 'Côté fenêtre - confortable';
            }
            
            if ($minDistanceToDoor <= 5) {
                $score += 10;
            }
            
        } else {
            // TRAJET LONG
            
            if ($seatType === 'window') {
                $score += 25;
                $reason = 'Fenêtre - confort pour long trajet';
            } elseif ($seatType === 'aisle') {
                $score += 10;
                $reason = 'Couloir - accès facile';
            }
            
            if ($minDistanceToDoor <= 3) {
                $score -= 15;
                $reason = 'Loin de la porte - plus calme';
            }
            
            $relativePosition = $seatNumber / $totalSeats;
            if ($relativePosition > 0.3 && $relativePosition < 0.7) {
                $score += 15;
            }
        }
        
        // --- MALUS MASSIF pour dernière rangée sur trajets courts ---
        $standardRows = floor($totalSeats / $seatsPerRow);
        $lastRowStartSeat = ($standardRows * $seatsPerRow) + 1;
        $isInLastRow = $seatNumber >= $lastRowStartSeat;
        
        if ($isInLastRow && $isShortTrip) {
            $score -= 50;
            $reason = 'Dernière rangée - éviter pour trajet court';
        }
        
        // --- Bonus/Malus position avant/arrière ---
        $relativePosition = $seatNumber / $totalSeats;
        
        if ($isShortTrip) {
            // Courts trajets: préférence FORTE pour l'avant
            if ($relativePosition < 0.25) {
                $score += 25;
            } elseif ($relativePosition > 0.75) {
                $score -= 15; // Malus pour l'arrière
            }
        }

        // Raison par défaut si pas encore définie
        if (empty($reason)) {
            if ($seatType === 'window') $reason = 'Place fenêtre';
            elseif ($seatType === 'aisle') $reason = 'Place couloir';
            else $reason = 'Place standard';
        }

        return [
            'score' => round($score, 2),
            'reason' => $reason
        ];
    }

    /**
     * Détermine le type de siège (window, aisle, middle)
     */
    private function getSeatType(int $seatNumber, string $configuration, int $totalSeats): string
    {
        $parts = array_map('intval', explode('+', $configuration));
        $seatsPerRow = array_sum($parts);
        
        if ($seatsPerRow == 0) return 'standard';

        // Détecter si on est dans la dernière rangée (qui peut avoir une config différente)
        // La dernière rangée commence après les rangées standard
        $standardRows = floor($totalSeats / $seatsPerRow);
        $lastRowStartSeat = ($standardRows * $seatsPerRow) + 1;
        $isLastRow = $seatNumber >= $lastRowStartSeat;
        
        if ($isLastRow) {
            // La dernière rangée a souvent 5 sièges (2+1+2 ou similaire)
            // Pour simplifier, on considère les sièges de la dernière rangée comme "middle"
            // car ils sont généralement moins désirables (surtout pour trajets courts)
            $lastRowSize = $totalSeats - $lastRowStartSeat + 1;
            $positionInLastRow = $seatNumber - $lastRowStartSeat;
            
            // Premier et dernier siège de la dernière rangée = fenêtre
            if ($positionInLastRow === 0 || $positionInLastRow === $lastRowSize - 1) {
                return 'window';
            }
            
            // Les autres sont considérés comme "middle" (moins désirables)
            return 'middle';
        }

        // Index de colonne (0 à seatsPerRow - 1) pour les rangées standard
        $colIndex = ($seatNumber - 1) % $seatsPerRow;
        
        // Parcourir les blocs pour trouver le type
        $currentCol = 0;
        foreach ($parts as $partIndex => $partWidth) {
            // Si le siège est dans ce bloc
            if ($colIndex < $currentCol + $partWidth) {
                $posInPart = $colIndex - $currentCol;
                
                // Bords extérieurs du véhicule (Fenêtre)
                if (($partIndex === 0 && $posInPart === 0) || 
                    ($partIndex === count($parts) - 1 && $posInPart === $partWidth - 1)) {
                    return 'window';
                }
                
                // Adjacence couloir (Aisle)
                // Fin d'un bloc (sauf le dernier) ou début d'un bloc (sauf le premier)
                if (($partIndex < count($parts) - 1 && $posInPart === $partWidth - 1) ||
                    ($partIndex > 0 && $posInPart === 0)) {
                    return 'aisle';
                }
                
                return 'middle';
            }
            $currentCol += $partWidth;
        }
        
        return 'standard';
    }

    /**
     * Obtient l'index d'un arrêt dans un trajet
     * 
     * @param string $routeId ID du trajet
     * @param string $stopId ID de l'arrêt
     * @return int Index de l'arrêt (0 pour le premier arrêt)
     */
    private function getStopIndex(string $routeId, string $stopId): int
    {
        $stopOrder = RouteStopOrder::where('route_id', $routeId)
            ->where('stop_id', $stopId)
            ->first();

        return $stopOrder ? $stopOrder->stop_index : 0;
    }

    /**
     * Obtient le nom de la porte (avant, milieu, arrière)
     * 
     * @param int $doorPosition Position de la porte
     * @param array $allDoorPositions Toutes les positions de portes
     * @return string Nom de la porte
     */
    private function getDoorName(int $doorPosition, array $allDoorPositions): string
    {
        if (count($allDoorPositions) == 1) {
            return 'avant';
        }

        $index = array_search($doorPosition, $allDoorPositions);
        
        if ($index === 0) {
            return 'avant';
        } elseif ($index === count($allDoorPositions) - 1) {
            return 'arrière';
        } else {
            return 'milieu';
        }
    }

    /**
     * Obtient des statistiques sur l'occupation d'un voyage
     * 
     * @param string $tripId ID du voyage
     * @return array Statistiques d'occupation
     */
    public function getTripOccupancyStats(string $tripId): array
    {
        $trip = Trip::with('vehicle.vehicleType')->findOrFail($tripId);
        
        $totalSeats = $trip->vehicle->vehicleType->seat_count;
        $occupiedSeats = Ticket::where('trip_id', $tripId)
            ->where('status', '!=', 'cancelled')
            ->count();

        $occupancyRate = $totalSeats > 0 ? ($occupiedSeats / $totalSeats) * 100 : 0;

        return [
            'total_seats' => $totalSeats,
            'occupied_seats' => $occupiedSeats,
            'available_seats' => $totalSeats - $occupiedSeats,
            'occupancy_rate' => round($occupancyRate, 2),
            'booking_type' => $trip->booking_type,
            'vehicle_type' => $trip->vehicle->vehicleType->name
        ];
    }
}
