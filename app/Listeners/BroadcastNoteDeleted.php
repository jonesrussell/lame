<?php

namespace App\Listeners;

use App\Events\NoteDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class BroadcastNoteDeleted implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(NoteDeleted $event): void
    {
        // The broadcasting is already handled by the event itself
        // This listener can be used for additional side effects if needed
        // For example: sending notifications, logging, etc.
    }
}
