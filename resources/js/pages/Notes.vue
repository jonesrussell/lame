<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { notes } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { CheckCircle, Clock, FileText, Plus, Trash2, X } from 'lucide-vue-next';
import { ref } from 'vue';

interface Note {
    id: string;
    content: string;
    done: boolean;
    created_at: string;
    updated_at: string;
}

interface Props {
    notes: Note[];
}

const props = defineProps<Props>();

// Form state
const showAddForm = ref(false);
const newNoteContent = ref('');
const isSubmitting = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notes',
        href: notes().url,
    },
];

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const deleteNote = async (noteId: string) => {
    if (confirm('Are you sure you want to delete this note? This action cannot be undone.')) {
        try {
            await fetch(`/api/notes/${noteId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            
            // Refresh the page to show updated notes list
            router.reload();
        } catch (error) {
            console.error('Error deleting note:', error);
            alert('Failed to delete note. Please try again.');
        }
    }
};

const toggleNoteStatus = async (noteId: string, currentStatus: boolean) => {
    try {
        await fetch(`/api/notes/${noteId}/toggle`, {
            method: 'PATCH',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });
        
        // Refresh the page to show updated notes list
        router.reload();
    } catch (error) {
        console.error('Error toggling note status:', error);
        alert('Failed to update note status. Please try again.');
    }
};

const addNote = async () => {
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
        const response = await fetch('/api/notes', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                content: newNoteContent.value.trim(),
            }),
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Failed to create note');
        }

        // Reset form and refresh page
        newNoteContent.value = '';
        showAddForm.value = false;
        router.reload();
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
                <button 
                    v-if="!showAddForm"
                    @click="showAddForm = true"
                    class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Add Note
                </button>
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
                
                <form @submit.prevent="addNote" class="space-y-4">
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

            <!-- Notes List -->
            <div v-if="props.notes.length === 0" class="flex flex-1 items-center justify-center">
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

            <div v-else class="space-y-3">
                <div
                    v-for="note in props.notes"
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
                                @click="toggleNoteStatus(note.id, note.done)"
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
                                @click="deleteNote(note.id)"
                                class="rounded-md px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-100 dark:text-red-300 dark:hover:bg-red-900"
                            >
                                <Trash2 class="h-3 w-3" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div v-if="props.notes.length > 0" class="rounded-lg border bg-muted/50 p-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-muted-foreground">
                        {{ props.notes.length }} total notes
                    </span>
                    <div class="flex items-center gap-4">
                        <span class="flex items-center gap-1 text-green-600">
                            <CheckCircle class="h-3 w-3" />
                            {{ props.notes.filter((n: Note) => n.done).length }} completed
                        </span>
                        <span class="flex items-center gap-1 text-orange-600">
                            <Clock class="h-3 w-3" />
                            {{ props.notes.filter((n: Note) => !n.done).length }} pending
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

