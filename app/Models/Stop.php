<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Stop extends Model
{
    use HasUuids;
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name', 'station_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function station()
    {
        return $this->belongsTo(Station::class);
    }

    public function routeStopOrders()
    {
        return $this->hasMany(RouteStopOrder::class);
    }

    public function fromFares()
    {
        return $this->hasMany(RouteFare::class, 'from_stop_id');
    }

    public function toFares()
    {
        return $this->hasMany(RouteFare::class, 'to_stop_id');
    }
}
