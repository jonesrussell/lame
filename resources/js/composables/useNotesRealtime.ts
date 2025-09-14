import { ref, readonly, onMounted, onUnmounted } from 'vue';
import { echoService } from '@/services/EchoService';
import { apiService } from '@/services/ApiService';
import type { Note, NoteEvent, NoteDeletedEvent } from '@/types/Note';

/**
 * Notes management composable with real-time WebSocket updates
 * Focuses solely on notes CRUD operations and WebSocket event handling
 */
export function useNotesRealtime() {
    const notes = ref<Note[]>([]);
    const isLoading = ref(false);
    const error = ref<string | null>(null);
    
    let notesChannel: any = null;

    // Event handlers
    const handleNoteCreated = (event: NoteEvent) => {
        console.log('🎉 Note created:', { 
            event, 
            action: event.action,
            timestamp: event.timestamp,
            noteId: event.note?.id 
        });
        
        // Validate event data
        if (!event.note || !event.note.id) {
            console.warn('⚠️ Invalid note data received:', event);
            return;
        }
        
        // Prevent duplicates
        const exists = notes.value.some(note => note.id === event.note.id);
        if (!exists) {
            notes.value.unshift(event.note);
            console.log('✅ Note added, total count:', notes.value.length);
        } else {
            console.log('ℹ️ Note already exists, skipping duplicate');
        }
    };

    const handleNoteUpdated = (event: NoteEvent) => {
        console.log('📝 Note updated:', event);
        
        const index = notes.value.findIndex(note => note.id === event.note.id);
        if (index !== -1) {
            notes.value[index] = event.note;
        }
    };

    const handleNoteDeleted = (event: NoteDeletedEvent) => {
        console.log('🗑️ Note deleted:', event);
        
        const index = notes.value.findIndex(note => note.id === event.noteId);
        if (index !== -1) {
            notes.value.splice(index, 1);
        }
    };

    // Setup WebSocket event listeners
    const setupEventListeners = () => {
        const echo = echoService.initialize();
        notesChannel = echo.channel('notes');

        // Listen for events - try both naming conventions for backwards compatibility
        notesChannel
            .listen('NoteCreated', handleNoteCreated)
            .listen('.note.created', handleNoteCreated)
            .listen('NoteUpdated', handleNoteUpdated) 
            .listen('.note.updated', handleNoteUpdated)
            .listen('NoteDeleted', handleNoteDeleted)
            .listen('.note.deleted', handleNoteDeleted);

        // Channel status handlers
        notesChannel.subscribed(() => console.log('✅ Subscribed to notes channel'));
        notesChannel.error((error: any) => console.error('🚨 Channel error:', error));

        return notesChannel;
    };

    // API operations
    const fetchNotes = async (): Promise<void> => {
        isLoading.value = true;
        error.value = null;

        try {
            const data = await apiService.get<{ data: Note[] } | Note[]>('/api/notes');
            notes.value = Array.isArray(data) ? data : data.data || [];
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to fetch notes';
            console.error('Error fetching notes:', err);
        } finally {
            isLoading.value = false;
        }
    };

    const createNote = async (content: string): Promise<Note> => {
        try {
            const result = await apiService.post<Note>('/api/notes', { content });
            return result;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to create note';
            throw err;
        }
    };

    const updateNote = async (id: string, data: Partial<Note>): Promise<Note> => {
        try {
            const result = await apiService.patch<Note>(`/api/notes/${id}`, data);
            return result;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to update note';
            throw err;
        }
    };

    const deleteNote = async (id: string): Promise<void> => {
        try {
            await apiService.delete(`/api/notes/${id}`);
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to delete note';
            throw err;
        }
    };

    const toggleNote = async (id: string): Promise<Note> => {
        try {
            const result = await apiService.patch<Note>(`/api/notes/${id}/toggle`);
            return result;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to toggle note';
            throw err;
        }
    };

    // Cleanup function
    const cleanup = (): void => {
        if (notesChannel) {
            echoService.getEcho()?.leaveChannel('notes');
            notesChannel = null;
        }
    };

    // Debug function
    const debugConnection = (): void => {
        echoService.debugConnection();
        
        if (notesChannel) {
            console.log('- Notes channel exists:', !!notesChannel);
            console.log('- Notes channel subscribed:', notesChannel.subscription?.subscribed);
        } else {
            console.log('- Notes channel not found');
        }
    };

    // Lifecycle hooks
    onMounted(async () => {
        await fetchNotes();
        setupEventListeners();
    });

    onUnmounted(cleanup);

    return {
        // State
        notes: readonly(notes),
        isLoading: readonly(isLoading),
        error: readonly(error),
        
        // Actions
        fetchNotes,
        createNote,
        updateNote,
        deleteNote,
        toggleNote,
        
        // Debug
        debugConnection,
    };
}
