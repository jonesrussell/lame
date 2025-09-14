<?php

namespace App\Events;

use App\Models\Note;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NoteCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public Note $note;

    /**
     * Create a new event instance.
     */
    public function __construct(Note $note)
    {
        $this->note = $note;
        
        // Debug logging - use structured logging
        logger()->info('NoteCreated event constructed', [
            'note_id' => $note->id,
            'note_content' => substr($note->content, 0, 50) . '...', // Truncate for logs
            'broadcast_channel' => $this->broadcastOn()[0]->name ?? 'unknown',
            'broadcast_event' => $this->broadcastAs(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
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
        $data = [
            'note' => $this->note->toArray(),
            'action' => 'created',
            'timestamp' => now()->toISOString(),
        ];

        // Debug log the broadcast data
        logger()->debug('Broadcasting NoteCreated with data', $data);

        return $data;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'NoteCreated';
    }

    /**
     * Determine if this event should broadcast.
     */
    public function shouldBroadcast(): bool
    {
        // Add any conditions where you don't want to broadcast
        return true;
    }

    /**
     * Get the broadcast connection to use.
     */
    public function broadcastVia(): array
    {
        return ['reverb'];
    }
}
