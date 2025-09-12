<?php

use App\Models\Note;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Note Web Routes', function () {
    describe('GET /dashboard', function () {
        it('displays dashboard with note statistics', function () {
            // Create some test notes
            Note::factory()->count(3)->create(['done' => false]);
            Note::factory()->count(2)->create(['done' => true]);
            
            $response = $this->get('/dashboard');
            
            $response->assertStatus(200)
                ->assertInertia(fn ($page) => 
                    $page->component('Dashboard')
                        ->has('noteStats')
                        ->where('noteStats.total', 5)
                        ->where('noteStats.completed', 2)
                        ->where('noteStats.pending', 3)
                        ->has('noteStats.recent')
                );
        });

        it('displays dashboard with empty statistics when no notes exist', function () {
            $response = $this->get('/dashboard');
            
            $response->assertStatus(200)
                ->assertInertia(fn ($page) => 
                    $page->component('Dashboard')
                        ->has('noteStats')
                        ->where('noteStats.total', 0)
                        ->where('noteStats.completed', 0)
                        ->where('noteStats.pending', 0)
                        ->where('noteStats.recent', [])
                );
        });

        it('shows recent notes in dashboard', function () {
            $recentNote = Note::factory()->create(['content' => 'Recent note']);
            Note::factory()->create(['content' => 'Older note', 'created_at' => now()->subHour()]);
            
            $response = $this->get('/dashboard');
            
            $response->assertStatus(200)
                ->assertInertia(fn ($page) => 
                    $page->component('Dashboard')
                        ->has('noteStats.recent')
                        ->where('noteStats.recent.0.content', 'Recent note')
                );
        });

        it('limits recent notes to 5', function () {
            Note::factory()->count(7)->create();
            
            $response = $this->get('/dashboard');
            
            $response->assertStatus(200)
                ->assertInertia(fn ($page) => 
                    $page->component('Dashboard')
                        ->has('noteStats.recent')
                        ->where('noteStats.total', 7)
                );
            
            $recentNotes = $response->viewData('page')['props']['noteStats']['recent'];
            expect($recentNotes)->toHaveCount(5);
        });
    });

    describe('GET /notes', function () {
        it('displays notes page with all notes', function () {
            $notes = Note::factory()->count(3)->create();
            
            $response = $this->get('/notes');
            
            $response->assertStatus(200)
                ->assertInertia(fn ($page) => 
                    $page->component('Notes')
                        ->has('notes')
                        ->where('notes', $notes->toArray())
                );
        });

        it('displays notes page with empty notes when none exist', function () {
            $response = $this->get('/notes');
            
            $response->assertStatus(200)
                ->assertInertia(fn ($page) => 
                    $page->component('Notes')
                        ->has('notes')
                        ->where('notes', [])
                );
        });

        it('orders notes by created_at desc', function () {
            $olderNote = Note::factory()->create(['content' => 'Older note', 'created_at' => now()->subHour()]);
            $newerNote = Note::factory()->create(['content' => 'Newer note', 'created_at' => now()]);
            
            $response = $this->get('/notes');
            
            $response->assertStatus(200)
                ->assertInertia(fn ($page) => 
                    $page->component('Notes')
                        ->has('notes')
                        ->where('notes.0.content', 'Newer note')
                        ->where('notes.1.content', 'Older note')
                );
        });

        it('includes all note attributes', function () {
            $note = Note::factory()->create([
                'content' => 'Test note',
                'done' => true,
            ]);
            
            $response = $this->get('/notes');
            
            $response->assertStatus(200)
                ->assertInertia(fn ($page) => 
                    $page->component('Notes')
                        ->has('notes.0')
                        ->where('notes.0.id', $note->id)
                        ->where('notes.0.content', 'Test note')
                        ->where('notes.0.done', true)
                        ->has('notes.0.created_at')
                        ->has('notes.0.updated_at')
                );
        });
    });

    describe('Authentication', function () {
        it('allows authenticated users to access dashboard', function () {
            $this->actingAs(User::factory()->create())
                ->get('/dashboard')
                ->assertStatus(200);
        });

        it('allows authenticated users to access notes page', function () {
            $this->actingAs(User::factory()->create())
                ->get('/notes')
                ->assertStatus(200);
        });
    });
});
