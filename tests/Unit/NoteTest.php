<?php

use App\Models\Note;

beforeEach(function () {
    $this->note = new Note();
});

describe('Note Model Configuration', function () {
    it('has the correct fillable attributes', function () {
        expect($this->note->getFillable())->toBe(['content', 'done']);
    });

    it('has the correct casts', function () {
        expect($this->note->getCasts())->toHaveKey('done');
        expect($this->note->getCasts())->toHaveKey('created_at');
        expect($this->note->getCasts())->toHaveKey('updated_at');
    });

    it('uses UUID as primary key', function () {
        expect($this->note->getKeyName())->toBe('id');
        expect($this->note->getIncrementing())->toBeFalse();
        expect($this->note->getKeyType())->toBe('string');
    });

    it('has the correct table name', function () {
        expect($this->note->getTable())->toBe('notes');
    });
});

describe('Note Model Validation', function () {
    it('validates empty content', function () {
        $note = new Note(['content' => 'Valid content']);
        
        expect(fn() => $note->validate())->not->toThrow(Exception::class);
        
        $note->content = '';
        expect(fn() => $note->validate())->toThrow(Exception::class);
    });

    it('validates content length', function () {
        $note = new Note(['content' => 'Valid content']);
        
        expect(fn() => $note->validate())->not->toThrow(Exception::class);
        
        $note->content = str_repeat('a', 1001);
        expect(fn() => $note->validate())->toThrow(Exception::class);
    });

    it('accepts content at max length', function () {
        $note = new Note(['content' => str_repeat('a', 1000)]);
        
        expect(fn() => $note->validate())->not->toThrow(Exception::class);
    });

});
