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

Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channels for real-time updates (authenticated users only)
Broadcast::channel('sales', function ($user) {
    return $user !== null;
});

Broadcast::channel('products', function ($user) {
    return $user !== null;
});

Broadcast::channel('inventory', function ($user) {
    return $user !== null;
});

Broadcast::channel('categories', function ($user) {
    return $user !== null;
});