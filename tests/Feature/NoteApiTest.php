<?php

use App\Models\Note;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Note API Endpoints', function () {
    describe('GET /api/notes', function () {
        it('returns a list of notes', function () {
            $notes = Note::factory()->count(3)->create();
            
            $response = $this->getJson('/api/notes');
            
            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'content',
                            'done',
                            'created_at',
                            'updated_at',
                        ]
                    ]
                ]);
            
            expect($response->json('data'))->toHaveCount(3);
        });

        it('returns empty array when no notes exist', function () {
            $response = $this->getJson('/api/notes');
            
            $response->assertStatus(200)
                ->assertJson(['data' => []]);
        });

        it('returns notes ordered by created_at desc', function () {
            $note1 = Note::factory()->create(['created_at' => now()->subHour()]);
            $note2 = Note::factory()->create(['created_at' => now()]);
            
            $response = $this->getJson('/api/notes');
            
            $response->assertStatus(200);
            $data = $response->json('data');
            
            expect($data[0]['id'])->toBe($note2->id);
            expect($data[1]['id'])->toBe($note1->id);
        });
    });

    describe('POST /api/notes', function () {
        it('creates a new note', function () {
            $noteData = ['content' => 'Test note content'];
            
            $response = $this->postJson('/api/notes', $noteData);
            
            $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'content',
                        'done',
                        'created_at',
                        'updated_at',
                    ],
                    'message'
                ])
                ->assertJson([
                    'data' => [
                        'content' => 'Test note content',
                        'done' => false,
                    ],
                    'message' => 'Note created successfully.'
                ]);
            
            $this->assertDatabaseHas('notes', [
                'content' => 'Test note content',
                'done' => false,
            ]);
        });

        it('validates required content field', function () {
            $response = $this->postJson('/api/notes', []);
            
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['content']);
        });

        it('validates content is not empty', function () {
            $response = $this->postJson('/api/notes', ['content' => '']);
            
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['content']);
        });

        it('validates content max length', function () {
            $longContent = str_repeat('a', 1001);
            $response = $this->postJson('/api/notes', ['content' => $longContent]);
            
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['content']);
        });

        it('accepts content at max length', function () {
            $maxContent = str_repeat('a', 1000);
            $response = $this->postJson('/api/notes', ['content' => $maxContent]);
            
            $response->assertStatus(201);
        });
    });

    describe('GET /api/notes/{id}', function () {
        it('returns a specific note', function () {
            $note = Note::factory()->create(['content' => 'Specific note']);
            
            $response = $this->getJson("/api/notes/{$note->id}");
            
            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $note->id,
                        'content' => 'Specific note',
                        'done' => $note->done,
                    ]
                ]);
        });

        it('returns 404 for non-existent note', function () {
            $response = $this->getJson('/api/notes/non-existent-id');
            
            $response->assertStatus(404)
                ->assertJson(['message' => 'Note not found.']);
        });
    });

    describe('PATCH /api/notes/{id}', function () {
        it('updates note content', function () {
            $note = Note::factory()->create(['content' => 'Original content']);
            
            $response = $this->patchJson("/api/notes/{$note->id}", [
                'content' => 'Updated content'
            ]);
            
            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $note->id,
                        'content' => 'Updated content',
                    ],
                    'message' => 'Note updated successfully.'
                ]);
            
            $this->assertDatabaseHas('notes', [
                'id' => $note->id,
                'content' => 'Updated content',
            ]);
        });

        it('updates note done status', function () {
            $note = Note::factory()->create(['done' => false]);
            
            $response = $this->patchJson("/api/notes/{$note->id}", [
                'done' => true
            ]);
            
            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $note->id,
                        'done' => true,
                    ]
                ]);
            
            $this->assertDatabaseHas('notes', [
                'id' => $note->id,
                'done' => true,
            ]);
        });

        it('updates both content and done status', function () {
            $note = Note::factory()->create(['content' => 'Original', 'done' => false]);
            
            $response = $this->patchJson("/api/notes/{$note->id}", [
                'content' => 'Updated content',
                'done' => true
            ]);
            
            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $note->id,
                        'content' => 'Updated content',
                        'done' => true,
                    ]
                ]);
        });

        it('validates content when updating', function () {
            $note = Note::factory()->create();
            
            $response = $this->patchJson("/api/notes/{$note->id}", [
                'content' => ''
            ]);
            
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['content']);
        });

        it('returns 404 for non-existent note', function () {
            $response = $this->patchJson('/api/notes/non-existent-id', [
                'content' => 'Updated content'
            ]);
            
            $response->assertStatus(404)
                ->assertJson(['message' => 'Note not found.']);
        });
    });

    describe('PATCH /api/notes/{id}/toggle', function () {
        it('toggles note done status', function () {
            $note = Note::factory()->create(['done' => false]);
            
            $response = $this->patchJson("/api/notes/{$note->id}/toggle");
            
            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $note->id,
                        'done' => true,
                    ],
                    'message' => 'Note status toggled successfully.'
                ]);
            
            $this->assertDatabaseHas('notes', [
                'id' => $note->id,
                'done' => true,
            ]);
        });

        it('toggles from true to false', function () {
            $note = Note::factory()->create(['done' => true]);
            
            $response = $this->patchJson("/api/notes/{$note->id}/toggle");
            
            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $note->id,
                        'done' => false,
                    ]
                ]);
        });

        it('returns 404 for non-existent note', function () {
            $response = $this->patchJson('/api/notes/non-existent-id/toggle');
            
            $response->assertStatus(404)
                ->assertJson(['message' => 'Note not found.']);
        });
    });

    describe('PATCH /api/notes/{id}/mark-done', function () {
        it('marks note as done', function () {
            $note = Note::factory()->create(['done' => false]);
            
            $response = $this->patchJson("/api/notes/{$note->id}/mark-done");
            
            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $note->id,
                        'done' => true,
                    ],
                    'message' => 'Note marked as done.'
                ]);
        });

        it('keeps note done if already done', function () {
            $note = Note::factory()->create(['done' => true]);
            
            $response = $this->patchJson("/api/notes/{$note->id}/mark-done");
            
            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $note->id,
                        'done' => true,
                    ]
                ]);
        });
    });

    describe('PATCH /api/notes/{id}/mark-undone', function () {
        it('marks note as undone', function () {
            $note = Note::factory()->create(['done' => true]);
            
            $response = $this->patchJson("/api/notes/{$note->id}/mark-undone");
            
            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $note->id,
                        'done' => false,
                    ],
                    'message' => 'Note marked as undone.'
                ]);
        });

        it('keeps note undone if already undone', function () {
            $note = Note::factory()->create(['done' => false]);
            
            $response = $this->patchJson("/api/notes/{$note->id}/mark-undone");
            
            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $note->id,
                        'done' => false,
                    ]
                ]);
        });
    });

    describe('DELETE /api/notes/{id}', function () {
        it('deletes a note', function () {
            $note = Note::factory()->create();
            
            $response = $this->deleteJson("/api/notes/{$note->id}");
            
            $response->assertStatus(200)
                ->assertJson(['message' => 'Note deleted successfully.']);
            
            $this->assertDatabaseMissing('notes', ['id' => $note->id]);
        });

        it('returns 404 for non-existent note', function () {
            $response = $this->deleteJson('/api/notes/non-existent-id');
            
            $response->assertStatus(404)
                ->assertJson(['message' => 'Note not found.']);
        });
    });
});
