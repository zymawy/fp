<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('donations', function () {
    return true; // Public channel for all donations
});

Broadcast::channel('donations:cause.{causeId}', function ($user, $causeId) {
    return true; // Public channel for specific cause donations
}); 