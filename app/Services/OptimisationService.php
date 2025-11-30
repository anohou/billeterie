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
    public function getSuggestedSeats(string $tripId, string $destinationStopId, int $maxSuggestions = 5): array
    {
        $trip = Trip::with(['vehicle.vehicleType', 'route.routeStopOrders'])->findOrFail($tripId);
        
        // Si le voyage est en mode "bulk", retourner un tableau vide (pas de suggestions)
        if ($trip->isBulk()) {
            return [];
        }

        // Récupérer les sièges déjà occupés
        $occupiedSeats = Ticket::where('trip_id', $tripId)
            ->where('status', '!=', 'cancelled')
            ->pluck('seat_number')
            ->toArray();

        // Récupérer la configuration du véhicule
        $vehicleType = $trip->vehicle->vehicleType;
        $totalSeats = $vehicleType->seat_count;
        $doorPositions = $vehicleType->door_positions ?? [0];
        
        // Calculer l'index de l'arrêt de destination (plus l'index est petit, plus c'est proche)
        $destinationIndex = $this->getStopIndex($trip->route_id, $destinationStopId);
        
        // Calculer le score pour chaque siège disponible
        $seatScores = [];
        for ($seatNumber = 1; $seatNumber <= $totalSeats; $seatNumber++) {
            // Ignorer les sièges occupés
            if (in_array($seatNumber, $occupiedSeats)) {
                continue;
            }

            $score = $this->calculateSeatScore(
                $seatNumber,
                $destinationIndex,
                $doorPositions,
                $totalSeats,
                $vehicleType->seat_configuration
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
