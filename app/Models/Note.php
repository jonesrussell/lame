<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\ValidationException;

class Note extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'content',
        'done',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'done' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The primary key associated with the table.
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the auto-incrementing ID.
     */
    protected $keyType = 'string';

    /**
     * Create a new note instance.
     */
    public static function createNote(string $content): self
    {
        $note = new self([
            'content' => $content,
            'done' => false,
        ]);

        $note->validate();
        $note->save();

        return $note;
    }

    /**
     * Toggle the done status of the note.
     */
    public function toggleDone(): void
    {
        $this->done = !$this->done;
        $this->touch();
    }

    /**
     * Update the content of the note.
     */
    public function updateContent(string $content): void
    {
        $this->content = $content;
        $this->validate();
        $this->touch();
    }

    /**
     * Mark the note as done.
     */
    public function markDone(): void
    {
        $this->done = true;
        $this->touch();
    }

    /**
     * Mark the note as not done.
     */
    public function markUndone(): void
    {
        $this->done = false;
        $this->touch();
    }

    /**
     * Validate the note content.
     */
    public function validate(): void
    {
        if (empty($this->content)) {
            throw ValidationException::withMessages([
                'content' => ['Note content cannot be empty.'],
            ]);
        }

        if (strlen($this->content) > 1000) {
            throw ValidationException::withMessages([
                'content' => ['Note content cannot exceed 1000 characters.'],
            ]);
        }
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($note) {
            $note->validate();
        });

        static::updating(function ($note) {
            $note->validate();
        });
    }
}
