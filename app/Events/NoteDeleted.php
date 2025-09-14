<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NoteDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $noteId;

    /**
     * Create a new event instance.
     */
    public function __construct(string $noteId)
    {
        $this->noteId = $noteId;
        
        // Debug logging
        \Log::info('NoteDeleted event fired', [
            'note_id' => $noteId,
            'broadcast_channel' => 'notes',
            'broadcast_event' => 'note.deleted'
        ]);

        $this->broadcastVia('reverb');
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('notes'),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'noteId' => $this->noteId,
            'action' => 'deleted',
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'note.deleted';
    }
}
