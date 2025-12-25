<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('trip.{tripId}', function ($user, $tripId) {
    return !is_null($user);
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('station.{stationId}', function ($user, $stationId) {
    if ($user->role === 'admin') return true;
    // Check if user is assigned to this station
    return $user->stationAssignments()->where('station_id', $stationId)->exists();
});
