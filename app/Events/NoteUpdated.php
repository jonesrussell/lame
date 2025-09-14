<?php

namespace App\Events;

use App\Models\Note;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NoteUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Note $note;

    /**
     * Create a new event instance.
     */
    public function __construct(Note $note)
    {
        $this->note = $note;
        
        // Debug logging
        \Log::info('NoteUpdated event fired', [
            'note_id' => $note->id,
            'note_content' => $note->content,
            'note_done' => $note->done,
            'broadcast_channel' => 'notes',
            'broadcast_event' => 'note.updated'
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
            'note' => $this->note->toArray(),
            'action' => 'updated',
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'note.updated';
    }
}
