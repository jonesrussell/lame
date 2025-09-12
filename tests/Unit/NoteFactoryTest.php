<?php

use App\Models\Note;
use Database\Factories\NoteFactory;

describe('Note Factory', function () {
    it('can make notes without persisting to database', function () {
        $note = Note::factory()->make();
        
        expect($note)->toBeInstanceOf(Note::class);
        expect($note->exists)->toBeFalse();
        expect($note->content)->not->toBeEmpty();
    });

    it('can create raw note data', function () {
        $data = Note::factory()->raw();
        
        expect($data)->toBeArray();
        expect($data)->toHaveKey('content');
        expect($data)->toHaveKey('done');
        expect($data['content'])->not->toBeEmpty();
        expect($data['done'])->toBeIn([true, false]);
    });

    it('respects the model property', function () {
        $factory = new NoteFactory();
        
        expect($factory->modelName())->toBe(Note::class);
    });
});
