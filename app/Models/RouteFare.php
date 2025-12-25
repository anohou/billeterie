<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouteFare extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'from_stop_id',
        'to_stop_id',
        'amount',
        'is_bidirectional'
    ];

    protected $casts = [
        'is_bidirectional' => 'boolean',
    ];

    public function fromStop(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'from_stop_id');
    }

    public function toStop(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'to_stop_id');
    }

    /**
     * Get the fare amount for a specific journey.
     * Checks both directions if fare is bidirectional.
     */
    public static function getFare(string $fromStopId, string $toStopId): ?int
    {
        // First try direct match
        $fare = self::where('from_stop_id', $fromStopId)
            ->where('to_stop_id', $toStopId)
            ->first();

        if ($fare) {
            return $fare->amount;
        }

        // Try reverse direction if bidirectional
        $reverseFare = self::where('from_stop_id', $toStopId)
            ->where('to_stop_id', $fromStopId)
            ->where('is_bidirectional', true)
            ->first();

        return $reverseFare?->amount;
    }

    /**
     * Find fare for a journey, considering bidirectional fares.
     * Returns the fare object with direction info.
     */
    public static function findFare(string $fromStopId, string $toStopId): ?array
    {
        // First try direct match
        $fare = self::where('from_stop_id', $fromStopId)
            ->where('to_stop_id', $toStopId)
            ->first();

        if ($fare) {
            return [
                'fare' => $fare,
                'amount' => $fare->amount,
                'is_reversed' => false
            ];
        }

        // Try reverse direction if bidirectional
        $reverseFare = self::where('from_stop_id', $toStopId)
            ->where('to_stop_id', $fromStopId)
            ->where('is_bidirectional', true)
            ->first();

        if ($reverseFare) {
            return [
                'fare' => $reverseFare,
                'amount' => $reverseFare->amount,
                'is_reversed' => true
            ];
        }

        return null;
    }
}
