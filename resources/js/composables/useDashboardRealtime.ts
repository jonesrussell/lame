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

interface NoteStats {
    total: number;
    completed: number;
    pending: number;
    recent: Note[];
}

export function useDashboardRealtime() {
    const noteStats = ref<NoteStats>({
        total: 0,
        completed: 0,
        pending: 0,
        recent: [],
    });
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

    // Update stats based on current notes
    const updateStats = (notes: Note[]) => {
        noteStats.value.total = notes.length;
        noteStats.value.completed = notes.filter(note => note.done).length;
        noteStats.value.pending = notes.filter(note => !note.done).length;
        noteStats.value.recent = notes
            .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
            .slice(0, 5);
    };

    // Listen for note events
    const listenForNotes = () => {
        const echo = initializeEcho();
        
        echo.channel('notes')
            .listen('note.created', (event: NoteEvent) => {
                console.log('🎉 Note created (Dashboard):', event.note);
                // Add to recent notes if it's one of the 5 most recent
                const allNotes = [...noteStats.value.recent, event.note];
                updateStats(allNotes);
            })
            .listen('note.updated', (event: NoteEvent) => {
                console.log('📝 Note updated (Dashboard):', event.note);
                // Update the note in recent list if it exists
                const recentIndex = noteStats.value.recent.findIndex(note => note.id === event.note.id);
                if (recentIndex !== -1) {
                    noteStats.value.recent[recentIndex] = event.note;
                }
                // Recalculate stats
                updateStats(noteStats.value.recent);
            })
            .listen('note.deleted', (event: NoteDeletedEvent) => {
                console.log('🗑️ Note deleted (Dashboard):', event.noteId);
                // Remove from recent list if it exists
                const recentIndex = noteStats.value.recent.findIndex(note => note.id === event.noteId);
                if (recentIndex !== -1) {
                    noteStats.value.recent.splice(recentIndex, 1);
                }
                // Recalculate stats
                updateStats(noteStats.value.recent);
            })
            .error((error: any) => {
                console.error('Echo error:', error);
            });
    };

    // Fetch initial stats
    const fetchStats = async () => {
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
            updateStats(data.data);
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'An error occurred';
            console.error('Error fetching notes:', err);
        } finally {
            isLoading.value = false;
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
        fetchStats();
        listenForNotes();
    });

    onUnmounted(() => {
        cleanup();
    });

    return {
        noteStats,
        isLoading,
        error,
        fetchStats,
    };
}
