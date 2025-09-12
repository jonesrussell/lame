<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    $noteStats = [
        'total' => \App\Models\Note::count(),
        'completed' => \App\Models\Note::where('done', true)->count(),
        'pending' => \App\Models\Note::where('done', false)->count(),
        'recent' => \App\Models\Note::orderBy('created_at', 'desc')->limit(5)->get(),
    ];

    return Inertia::render('Dashboard', [
        'noteStats' => $noteStats,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('notes', function () {
    $notes = \App\Models\Note::orderBy('created_at', 'desc')->get();
    
    return Inertia::render('Notes', [
        'notes' => $notes,
    ]);
})->middleware(['auth', 'verified'])->name('notes');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
