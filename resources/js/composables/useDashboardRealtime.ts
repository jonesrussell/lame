import { ref, readonly, onMounted, onUnmounted } from 'vue';
import { echoService } from '@/services/EchoService';
import { apiService } from '@/services/ApiService';
import type { Note, NoteEvent, NoteDeletedEvent, NoteStats } from '@/types/Note';

/**
 * Dashboard statistics composable with real-time updates
 * Focuses solely on dashboard statistics and aggregated data
 */
export function useDashboardRealtime() {
    const noteStats = ref<NoteStats>({
        total: 0,
        completed: 0,
        pending: 0,
        recent: [],
    });
    const isLoading = ref(false);
    const error = ref<string | null>(null);
    
    let dashboardChannel: any = null;

    // Utility function to update stats based on notes array
    const updateStats = (notes: Note[]): void => {
        noteStats.value.total = notes.length;
        noteStats.value.completed = notes.filter(note => note.done).length;
        noteStats.value.pending = notes.filter(note => !note.done).length;
        noteStats.value.recent = notes
            .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime())
            .slice(0, 5);
    };

    // Event handlers for dashboard-specific logic
    const handleNoteCreated = (event: NoteEvent): void => {
        console.log('🎉 Note created (Dashboard):', event.note);
        
        if (!event.note || !event.note.id) {
            console.warn('⚠️ Invalid note data received:', event);
            return;
        }

        // Update stats optimistically without full refetch
        noteStats.value.total += 1;
        if (event.note.done) {
            noteStats.value.completed += 1;
        } else {
            noteStats.value.pending += 1;
        }

        // Add to recent notes (maintain max 5)
        noteStats.value.recent.unshift(event.note);
        if (noteStats.value.recent.length > 5) {
            noteStats.value.recent = noteStats.value.recent.slice(0, 5);
        }
    };

    const handleNoteUpdated = (event: NoteEvent): void => {
        console.log('📝 Note updated (Dashboard):', event.note);
        
        if (!event.note || !event.note.id) {
            console.warn('⚠️ Invalid note data received:', event);
            return;
        }

        // Find and update in recent list if it exists
        const recentIndex = noteStats.value.recent.findIndex(note => note.id === event.note.id);
        if (recentIndex !== -1) {
            const oldNote = noteStats.value.recent[recentIndex];
            noteStats.value.recent[recentIndex] = event.note;
            
            // Update completion stats if status changed
            if (oldNote.done !== event.note.done) {
                if (event.note.done) {
                    noteStats.value.completed += 1;
                    noteStats.value.pending -= 1;
                } else {
                    noteStats.value.completed -= 1;
                    noteStats.value.pending += 1;
                }
            }
        }
    };

    const handleNoteDeleted = (event: NoteDeletedEvent): void => {
        console.log('🗑️ Note deleted (Dashboard):', event.noteId);
        
        if (!event.noteId) {
            console.warn('⚠️ Invalid note ID received:', event);
            return;
        }

        // Find and remove from recent list if it exists
        const recentIndex = noteStats.value.recent.findIndex(note => note.id === event.noteId);
        if (recentIndex !== -1) {
            const deletedNote = noteStats.value.recent[recentIndex];
            noteStats.value.recent.splice(recentIndex, 1);
            
            // Update stats
            noteStats.value.total -= 1;
            if (deletedNote.done) {
                noteStats.value.completed -= 1;
            } else {
                noteStats.value.pending -= 1;
            }
        } else {
            // Note wasn't in recent list, but still update total count
            noteStats.value.total = Math.max(0, noteStats.value.total - 1);
        }
    };

    // Setup WebSocket event listeners for dashboard
    const setupEventListeners = (): void => {
        const echo = echoService.initialize();
        dashboardChannel = echo.channel('notes');

        // Listen for note events with dashboard-specific handlers
        dashboardChannel
            .listen('NoteCreated', handleNoteCreated)
            .listen('.note.created', handleNoteCreated)
            .listen('NoteUpdated', handleNoteUpdated) 
            .listen('.note.updated', handleNoteUpdated)
            .listen('NoteDeleted', handleNoteDeleted)
            .listen('.note.deleted', handleNoteDeleted);

        // Channel status handlers
        dashboardChannel.subscribed(() => console.log('✅ Dashboard subscribed to notes channel'));
        dashboardChannel.error((error: any) => console.error('🚨 Dashboard channel error:', error));
    };

    // Fetch initial statistics
    const fetchStats = async (): Promise<void> => {
        isLoading.value = true;
        error.value = null;
        
        try {
            const data = await apiService.get<{ data: Note[] } | Note[]>('/api/notes');
            const notes = Array.isArray(data) ? data : data.data || [];
            updateStats(notes);
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to fetch note statistics';
            console.error('Error fetching note stats:', err);
        } finally {
            isLoading.value = false;
        }
    };

    // Cleanup function
    const cleanup = (): void => {
        if (dashboardChannel) {
            echoService.getEcho()?.leaveChannel('notes');
            dashboardChannel = null;
        }
    };

    // Lifecycle hooks
    onMounted(async () => {
        await fetchStats();
        setupEventListeners();
    });

    onUnmounted(cleanup);

    return {
        // State
        noteStats: readonly(noteStats),
        isLoading: readonly(isLoading),
        error: readonly(error),
        
        // Actions
        fetchStats,
    };
}
