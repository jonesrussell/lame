<?php

use Illuminate\Support\Facades\Broadcast;
use App\Broadcasting\NoteChannel;

Broadcast::channel('notes', NoteChannel::class);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
