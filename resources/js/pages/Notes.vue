<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { notes as notesRoute } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { CheckCircle, Clock, FileText, Plus, Trash2, X, Bug } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';
import { useNotesRealtime } from '@/composables/useNotesRealtime';

interface Note {
    id: string;
    content: string;
    done: boolean;
    created_at: string;
    updated_at: string;
}

// Use real-time composable
const { 
    notes: realtimeNotes, 
    isLoading, 
    error, 
    createNote, 
    deleteNote, 
    toggleNote,
    debugConnection  // Get the debug function
} = useNotesRealtime();

// Form state
const showAddForm = ref(false);
const newNoteContent = ref('');
const isSubmitting = ref(false);

// Debug state
const showDebugPanel = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notes',
        href: notesRoute().url,
    },
];

// Debug connection on mount
onMounted(() => {
    // Wait a bit for Echo to initialize
    setTimeout(() => {
        console.log('🔍 Running debug check on component mount...');
        debugConnection();
    }, 3000);
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const handleDeleteNote = async (noteId: string) => {
    if (confirm('Are you sure you want to delete this note? This action cannot be undone.')) {
        try {
            await deleteNote(noteId);
        } catch (error) {
            console.error('Error deleting note:', error);
            alert('Failed to delete note. Please try again.');
        }
    }
};

const handleToggleNote = async (noteId: string) => {
    try {
        await toggleNote(noteId);
    } catch (error) {
        console.error('Error toggling note status:', error);
        alert('Failed to update note status. Please try again.');
    }
};

const handleAddNote = async () => {
    if (!newNoteContent.value.trim()) {
        alert('Please enter note content.');
        return;
    }

    if (newNoteContent.value.length > 1000) {
        alert('Note content cannot exceed 1000 characters.');
        return;
    }

    isSubmitting.value = true;

    try {
        console.log('🚀 Creating note, watch for real-time event...');
        await createNote(newNoteContent.value.trim());
        
        // Reset form - note will be added automatically via real-time event
        newNoteContent.value = '';
        showAddForm.value = false;
        
        // Run debug after creating note
        setTimeout(() => {
            console.log('🔍 Debug check after note creation...');
            debugConnection();
        }, 1000);
    } catch (error) {
        console.error('Error creating note:', error);
        alert(error instanceof Error ? error.message : 'Failed to create note. Please try again.');
    } finally {
        isSubmitting.value = false;
    }
};

const cancelAddNote = () => {
    newNoteContent.value = '';
    showAddForm.value = false;
};

// Manual debug trigger
const runDebugCheck = () => {
    console.log('🔍 Manual debug check triggered...');
    debugConnection();
    showDebugPanel.value = true;
    
    // Hide debug panel after 5 seconds
    setTimeout(() => {
        showDebugPanel.value = false;
    }, 5000);
};

// Test note creation for debugging
const testNoteCreation = async () => {
    try {
        console.log('🧪 Creating test note...');
        await createNote(`Test note created at ${new Date().toLocaleTimeString()}`);
        console.log('🧪 Test note creation completed, check console for real-time events');
    } catch (error) {
        console.error('🧪 Test note creation failed:', error);
    }
};
</script>

<template>
    <Head title="Notes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Notes</h1>
                    <p class="text-muted-foreground">Manage your todo notes</p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Debug Button (remove in production) -->
                    <button 
                        @click="runDebugCheck"
                        class="inline-flex items-center gap-2 rounded-md bg-orange-500 px-3 py-2 text-sm font-medium text-white hover:bg-orange-600"
                        title="Debug WebSocket Connection"
                    >
                        <Bug class="h-4 w-4" />
                        Debug
                    </button>
                    
                    <!-- Test Button (remove in production) -->
                    <button 
                        @click="testNoteCreation"
                        class="inline-flex items-center gap-2 rounded-md bg-blue-500 px-3 py-2 text-sm font-medium text-white hover:bg-blue-600"
                        title="Create Test Note"
                    >
                        Test Note
                    </button>
                    
                    <button 
                        v-if="!showAddForm"
                        @click="showAddForm = true"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        <Plus class="h-4 w-4" />
                        Add Note
                    </button>
                </div>
            </div>

            <!-- Debug Panel -->
            <div v-if="showDebugPanel" class="rounded-lg border border-orange-200 bg-orange-50 p-4 dark:border-orange-800 dark:bg-orange-950">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-orange-800 dark:text-orange-200">
                        🔍 Debug Information
                    </h3>
                    <button 
                        @click="showDebugPanel = false"
                        class="text-orange-600 hover:text-orange-800 dark:text-orange-400 dark:hover:text-orange-200"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <p class="text-sm text-orange-700 dark:text-orange-300">
                    Check your browser console for detailed WebSocket connection information.
                    <br>
                    <strong>What to look for:</strong>
                </p>
                <ul class="mt-2 text-xs text-orange-600 dark:text-orange-400 space-y-1">
                    <li>• "🔗 Connected" - WebSocket connected</li>
                    <li>• "✅ Subscribed to notes channel" - Channel subscription successful</li>
                    <li>• "📨 NoteCreated event received" - Events are being received</li>
                    <li>• Environment variables are properly set</li>
                </ul>
            </div>

            <!-- Add Note Form -->
            <div v-if="showAddForm" class="rounded-lg border bg-card p-4">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Add New Note</h3>
                    <button 
                        @click="cancelAddNote"
                        class="rounded-md p-1 text-muted-foreground hover:bg-muted"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>
                
                <form @submit.prevent="handleAddNote" class="space-y-4">
                    <div>
                        <label for="note-content" class="block text-sm font-medium text-foreground mb-2">
                            Note Content
                        </label>
                        <textarea
                            id="note-content"
                            v-model="newNoteContent"
                            rows="3"
                            maxlength="1000"
                            placeholder="Enter your note content..."
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="isSubmitting"
                        />
                        <div class="mt-1 text-xs text-muted-foreground">
                            {{ newNoteContent.length }}/1000 characters
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <button
                            type="submit"
                            :disabled="isSubmitting || !newNoteContent.trim()"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <Plus class="h-4 w-4" />
                            {{ isSubmitting ? 'Creating...' : 'Create Note' }}
                        </button>
                        <button
                            type="button"
                            @click="cancelAddNote"
                            :disabled="isSubmitting"
                            class="inline-flex items-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium text-foreground hover:bg-accent hover:text-accent-foreground disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="flex items-center justify-center py-8">
                <div class="text-muted-foreground">Loading notes...</div>
            </div>

            <!-- Error State -->
            <div v-else-if="error" class="rounded-lg border border-destructive bg-destructive/10 p-4">
                <div class="text-destructive">Error: {{ error }}</div>
            </div>

            <!-- Empty State -->
            <div v-else-if="realtimeNotes.length === 0" class="flex flex-1 items-center justify-center">
                <div class="text-center">
                    <FileText class="mx-auto h-16 w-16 text-muted-foreground" />
                    <h3 class="mt-4 text-lg font-semibold">No notes yet</h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Get started by creating your first note.
                    </p>
                    <button 
                        @click="showAddForm = true"
                        class="mt-4 inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        <Plus class="h-4 w-4" />
                        Create Note
                    </button>
                </div>
            </div>

            <!-- Notes List -->
            <div v-else class="space-y-3">
                <div
                    v-for="note in realtimeNotes"
                    :key="note.id"
                    class="group rounded-lg border bg-card p-4 transition-colors hover:bg-muted/50"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-3 flex-1">
                            <!-- Status Indicator -->
                            <div class="mt-1">
                                <div
                                    :class="[
                                        'h-3 w-3 rounded-full',
                                        note.done ? 'bg-green-500' : 'bg-orange-500'
                                    ]"
                                />
                            </div>

                            <!-- Note Content -->
                            <div class="flex-1 min-w-0">
                                <p :class="[
                                    'text-sm font-medium',
                                    note.done ? 'line-through text-muted-foreground' : 'text-foreground'
                                ]">
                                    {{ note.content }}
                                </p>
                                <div class="mt-2 flex items-center gap-4 text-xs text-muted-foreground">
                                    <span class="flex items-center gap-1">
                                        <Clock class="h-3 w-3" />
                                        Created {{ formatDate(note.created_at) }}
                                    </span>
                                    <span v-if="note.updated_at !== note.created_at" class="flex items-center gap-1">
                                        <CheckCircle class="h-3 w-3" />
                                        Updated {{ formatDate(note.updated_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 opacity-0 transition-opacity group-hover:opacity-100">
                            <button
                                @click="handleToggleNote(note.id)"
                                :class="[
                                    'rounded-md px-2 py-1 text-xs font-medium transition-colors',
                                    note.done 
                                        ? 'bg-orange-100 text-orange-700 hover:bg-orange-200 dark:bg-orange-900 dark:text-orange-300 dark:hover:bg-orange-800'
                                        : 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900 dark:text-green-300 dark:hover:bg-green-800'
                                ]"
                            >
                                {{ note.done ? 'Mark Undone' : 'Mark Done' }}
                            </button>
                            <button 
                                @click="handleDeleteNote(note.id)"
                                class="rounded-md px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-100 dark:text-red-300 dark:hover:bg-red-900"
                            >
                                <Trash2 class="h-3 w-3" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div v-if="realtimeNotes.length > 0" class="rounded-lg border bg-muted/50 p-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">
                        {{ realtimeNotes.length }} total notes
                    </span>
                    <div class="flex items-center gap-4">
                        <span class="flex items-center gap-1 text-green-600">
                            <CheckCircle class="h-3 w-3" />
                            {{ realtimeNotes.filter((n: Note) => n.done).length }} completed
                        </span>
                        <span class="flex items-center gap-1 text-orange-600">
                            <Clock class="h-3 w-3" />
                            {{ realtimeNotes.filter((n: Note) => !n.done).length }} pending
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
