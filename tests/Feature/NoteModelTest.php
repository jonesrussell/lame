<?php

use App\Models\Note;
use Illuminate\Validation\ValidationException;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Note Model Database Operations', function () {
    it('can create a note using createNote method', function () {
        $note = Note::createNote('Test note content');
        
        expect($note)->toBeInstanceOf(Note::class);
        expect($note->content)->toBe('Test note content');
        expect($note->done)->toBeFalse();
        expect($note->id)->not->toBeEmpty();
        expect($note->created_at)->not->toBeNull();
        expect($note->updated_at)->not->toBeNull();
        
        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'content' => 'Test note content',
            'done' => false,
        ]);
    });

    it('validates note content on creation', function () {
        expect(fn() => Note::createNote(''))->toThrow(ValidationException::class);
        expect(fn() => Note::createNote(str_repeat('a', 1001)))->toThrow(ValidationException::class);
    });

    it('can toggle done status and persist to database', function () {
        $note = Note::createNote('Test note');
        expect($note->done)->toBeFalse();
        
        $note->toggleDone();
        $note->save();
        expect($note->done)->toBeTrue();
        
        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'done' => true,
        ]);
        
        $note->toggleDone();
        $note->save();
        expect($note->done)->toBeFalse();
        
        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'done' => false,
        ]);
    });

    it('can mark note as done and persist to database', function () {
        $note = Note::createNote('Test note');
        expect($note->done)->toBeFalse();
        
        $note->markDone();
        $note->save();
        expect($note->done)->toBeTrue();
        
        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'done' => true,
        ]);
    });

    it('can mark note as undone and persist to database', function () {
        $note = Note::createNote('Test note');
        $note->markDone();
        $note->save();
        expect($note->done)->toBeTrue();
        
        $note->markUndone();
        $note->save();
        expect($note->done)->toBeFalse();
        
        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'done' => false,
        ]);
    });

    it('can update note content and persist to database', function () {
        $note = Note::createNote('Original content');
        expect($note->content)->toBe('Original content');
        
        $note->updateContent('Updated content');
        $note->save();
        expect($note->content)->toBe('Updated content');
        
        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'content' => 'Updated content',
        ]);
    });

    it('validates content when updating', function () {
        $note = Note::createNote('Valid content');
        
        expect(fn() => $note->updateContent(''))->toThrow(ValidationException::class);
        expect(fn() => $note->updateContent(str_repeat('a', 1001)))->toThrow(ValidationException::class);
    });

    it('updates timestamp when content is updated', function () {
        $note = Note::createNote('Test content');
        $originalUpdatedAt = $note->updated_at;
        
        sleep(1); // Ensure timestamp difference
        $note->updateContent('Updated content');
        $note->save();
        
        expect($note->updated_at)->not->toBe($originalUpdatedAt);
        
        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'updated_at' => $note->updated_at->format('Y-m-d H:i:s'),
        ]);
    });

    it('updates timestamp when status is toggled', function () {
        $note = Note::createNote('Test content');
        $originalUpdatedAt = $note->updated_at;
        
        sleep(1); // Ensure timestamp difference
        $note->toggleDone();
        $note->save();
        
        expect($note->updated_at)->not->toBe($originalUpdatedAt);
        
        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'updated_at' => $note->updated_at->format('Y-m-d H:i:s'),
        ]);
    });

    it('validates content on model creation', function () {
        expect(fn() => Note::create(['content' => '']))->toThrow(ValidationException::class);
        expect(fn() => Note::create(['content' => str_repeat('a', 1001)]))->toThrow(ValidationException::class);
    });

    it('validates content on model update', function () {
        $note = Note::createNote('Valid content');
        
        expect(fn() => $note->update(['content' => '']))->toThrow(ValidationException::class);
        expect(fn() => $note->update(['content' => str_repeat('a', 1001)]))->toThrow(ValidationException::class);
    });

    it('can delete a note', function () {
        $note = Note::createNote('Test note');
        
        $this->assertDatabaseHas('notes', ['id' => $note->id]);
        
        $note->delete();
        
        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    });

    it('can find a note by id', function () {
        $note = Note::createNote('Test note');
        
        $foundNote = Note::find($note->id);
        
        expect($foundNote)->not->toBeNull();
        expect($foundNote->id)->toBe($note->id);
        expect($foundNote->content)->toBe('Test note');
    });

    it('returns null for non-existent note', function () {
        $note = Note::find('non-existent-id');
        
        expect($note)->toBeNull();
    });
});
