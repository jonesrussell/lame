<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class NoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $notes = Note::orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $notes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'content' => 'required|string|max:1000',
            ]);

            $note = Note::createNote($validated['content']);

            return response()->json([
                'data' => $note,
                'message' => 'Note created successfully.',
            ], Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $note = Note::find($id);

        if (!$note) {
            return response()->json([
                'message' => 'Note not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $note,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $note = Note::find($id);

            if (!$note) {
                return response()->json([
                    'message' => 'Note not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            $validated = $request->validate([
                'content' => 'sometimes|string|max:1000',
                'done' => 'sometimes|boolean',
            ]);

            if (isset($validated['content'])) {
                $note->updateContent($validated['content']);
            }

            if (isset($validated['done'])) {
                if ($validated['done']) {
                    $note->markDone();
                } else {
                    $note->markUndone();
                }
            }

            $note->save();

            return response()->json([
                'data' => $note,
                'message' => 'Note updated successfully.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $note = Note::find($id);

        if (!$note) {
            return response()->json([
                'message' => 'Note not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $note->delete();

        return response()->json([
            'message' => 'Note deleted successfully.',
        ]);
    }

    /**
     * Toggle the done status of the specified note.
     */
    public function toggle(string $id): JsonResponse
    {
        $note = Note::find($id);

        if (!$note) {
            return response()->json([
                'message' => 'Note not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $note->toggleDone();
        $note->save();

        return response()->json([
            'data' => $note,
            'message' => 'Note status toggled successfully.',
        ]);
    }

    /**
     * Mark the specified note as done.
     */
    public function markDone(string $id): JsonResponse
    {
        $note = Note::find($id);

        if (!$note) {
            return response()->json([
                'message' => 'Note not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $note->markDone();
        $note->save();

        return response()->json([
            'data' => $note,
            'message' => 'Note marked as done.',
        ]);
    }

    /**
     * Mark the specified note as undone.
     */
    public function markUndone(string $id): JsonResponse
    {
        $note = Note::find($id);

        if (!$note) {
            return response()->json([
                'message' => 'Note not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $note->markUndone();
        $note->save();

        return response()->json([
            'data' => $note,
            'message' => 'Note marked as undone.',
        ]);
    }
}
