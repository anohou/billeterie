<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Trip extends Model
{
    use HasUuids;
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['route_id','vehicle_id','departure_at','status','booking_type'];

    protected $casts = [
        'departure_at' => 'datetime',
    ];

    /**
     * Vérifie si le voyage utilise le placement intelligent des sièges
     */
    public function isSeatAssignment(): bool
    {
        return $this->booking_type === 'seat_assignment';
    }

    /**
     * Vérifie si le voyage est en mode vrac (sans placement intelligent)
     */
    public function isBulk(): bool
    {
        return $this->booking_type === 'bulk';
    }

    /**
     * Vérifie si le voyage est en mode semi-intelligent (réutilisation des sièges)
     * Permet de vendre des tickets pour des trajets différents en réutilisant les sièges
     * qui se libèrent aux arrêts intermédiaires
     */
    public function isSemiIntelligent(): bool
    {
        return $this->booking_type === 'semi_intelligent';
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function route()
    {
        return $this->belongsTo(Route::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function tripSeatOccupancies()
    {
        return $this->hasMany(TripSeatOccupancy::class);
    }
}
