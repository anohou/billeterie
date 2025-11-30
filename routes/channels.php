<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('trip.{tripId}', function ($user, $tripId) {
    return !is_null($user);
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
