import { ref, onMounted, onUnmounted } from 'vue';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher globally available
window.Pusher = Pusher;

// Extend window interface for Echo and Pusher
declare global {
    interface Window {
        Echo: Echo<any>;
        Pusher: typeof Pusher;
    }
}

interface Note {
    id: string;
    content: string;
    done: boolean;
    created_at: string;
    updated_at: string;
}

interface NoteEvent {
    note: Note;
    action: 'created' | 'updated';
}

interface NoteDeletedEvent {
    noteId: string;
    action: 'deleted';
}

export function useNotesRealtime() {
    const notes = ref<Note[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Initialize Echo
    const initializeEcho = () => {
        if (!window.Echo) {
            console.log('Initializing Echo with Reverb...', {
                key: import.meta.env.VITE_REVERB_APP_KEY,
                wsHost: import.meta.env.VITE_REVERB_HOST,
                wsPort: import.meta.env.VITE_REVERB_PORT,
                scheme: import.meta.env.VITE_REVERB_SCHEME,
            });
            
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: import.meta.env.VITE_REVERB_APP_KEY,
                cluster: 'mt1',
                wsHost: import.meta.env.VITE_REVERB_HOST,
                wsPort: import.meta.env.VITE_REVERB_PORT,
                wssPort: import.meta.env.VITE_REVERB_PORT,
                forceTLS: false,
                enabledTransports: ['wss'],
                disableStats: true,
            });
            
            console.log('✅ Echo instance created successfully');
            
            // Add connection debugging
            window.Echo.connector.pusher.connection.bind('connecting', () => {
                console.log('🔄 Connecting to WebSocket...');
            });
            
            window.Echo.connector.pusher.connection.bind('connected', () => {
                console.log('🔗 WebSocket connected successfully');
            });
            
            window.Echo.connector.pusher.connection.bind('disconnected', () => {
                console.log('❌ WebSocket disconnected');
            });
            
            window.Echo.connector.pusher.connection.bind('error', (error: any) => {
                console.error('🚨 WebSocket error:', error);
            });
        }
        return window.Echo;
    };

    // Listen for note events
    const listenForNotes = () => {
        const echo = initializeEcho();
        
        echo.channel('notes')
            .listen('note.created', (event: NoteEvent) => {
                console.log('🎉 Note created:', event.note);
                notes.value.unshift(event.note);
            })
            .listen('note.updated', (event: NoteEvent) => {
                console.log('📝 Note updated:', event.note);
                const index = notes.value.findIndex(note => note.id === event.note.id);
                if (index !== -1) {
                    notes.value[index] = event.note;
                }
            })
            .listen('note.deleted', (event: NoteDeletedEvent) => {
                console.log('🗑️ Note deleted:', event.noteId);
                const index = notes.value.findIndex(note => note.id === event.noteId);
                if (index !== -1) {
                    notes.value.splice(index, 1);
                }
            })
            .error((error: any) => {
                console.error('Echo error:', error);
            });
    };

    // Fetch initial notes
    const fetchNotes = async () => {
        isLoading.value = true;
        error.value = null;
        
        try {
            const response = await fetch('/api/notes', {
                headers: {
                    'Accept': 'application/json',
                },
            });
            
            if (!response.ok) {
                throw new Error('Failed to fetch notes');
            }
            
            const data = await response.json();
            notes.value = data.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error fetching notes:', err);
        } finally {
            isLoading.value = false;
        }
    };

    // Create a new note
    const createNote = async (content: string) => {
        try {
            const response = await fetch('/api/notes', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ content }),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to create note');
            }

            // Note will be added automatically via real-time event
            return await response.json();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to create note';
            throw err;
        }
    };

    // Update a note
    const updateNote = async (id: string, data: Partial<Note>) => {
        try {
            const response = await fetch(`/api/notes/${id}`, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(data),
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to update note');
            }

            // Note will be updated automatically via real-time event
            return await response.json();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to update note';
            throw err;
        }
    };

    // Delete a note
    const deleteNote = async (id: string) => {
        try {
            const response = await fetch(`/api/notes/${id}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to delete note');
            }

            // Note will be removed automatically via real-time event
            return await response.json();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to delete note';
            throw err;
        }
    };

    // Toggle note status
    const toggleNote = async (id: string) => {
        try {
            const response = await fetch(`/api/notes/${id}/toggle`, {
                method: 'PATCH',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to toggle note');
            }

            // Note will be updated automatically via real-time event
            return await response.json();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to toggle note';
            throw err;
        }
    };

    // Cleanup
    const cleanup = () => {
        if (window.Echo) {
            window.Echo.leave('notes');
        }
    };

    // Setup
    onMounted(() => {
        fetchNotes();
        listenForNotes();
    });

    onUnmounted(() => {
        cleanup();
    });

    return {
        notes,
        isLoading,
        error,
        fetchNotes,
        createNote,
        updateNote,
        deleteNote,
        toggleNote,
    };
}
