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
        'route_id',
        'from_stop_id',
        'to_stop_id',
        'amount'
    ];

    public function route(): BelongsTo
    {
        return $this->belongsTo(Route::class);
    }

    public function fromStop(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'from_stop_id');
    }

    public function toStop(): BelongsTo
    {
        return $this->belongsTo(Stop::class, 'to_stop_id');
    }
}
