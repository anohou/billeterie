<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Route extends Model
{
    use HasUuids;
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name', 'origin_station_id', 'destination_station_id', 'active',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function userAssignments()
    {
        return $this->hasMany(\App\Models\UserRouteAssignment::class);
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }

    public function originStation()
    {
        return $this->belongsTo(Station::class, 'origin_station_id');
    }

    public function destinationStation()
    {
        return $this->belongsTo(Station::class, 'destination_station_id');
    }

    public function routeStopOrders()
    {
        return $this->hasMany(RouteStopOrder::class);
    }

    public function routeFares()
    {
        return $this->hasMany(RouteFare::class);
    }

    public function stops()
    {
        return $this->belongsToMany(Stop::class, 'route_stop_orders')->orderBy('stop_index');
    }
}
