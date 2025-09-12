<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { notes } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { CheckCircle, Clock, FileText, Plus, Trash2 } from 'lucide-vue-next';

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
                <button class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                    <Plus class="h-4 w-4" />
                    Add Note
                </button>
            </div>

            <!-- Notes List -->
            <div v-if="props.notes.length === 0" class="flex flex-1 items-center justify-center">
                <div class="text-center">
                    <FileText class="mx-auto h-16 w-16 text-muted-foreground" />
                    <h3 class="mt-4 text-lg font-semibold">No notes yet</h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Get started by creating your first note.
                    </p>
                    <button class="mt-4 inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
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
                                :class="[
                                    'rounded-md px-2 py-1 text-xs font-medium transition-colors',
                                    note.done 
                                        ? 'bg-orange-100 text-orange-700 hover:bg-orange-200 dark:bg-orange-900 dark:text-orange-300 dark:hover:bg-orange-800'
                                        : 'bg-green-100 text-green-700 hover:bg-green-200 dark:bg-green-900 dark:text-green-300 dark:hover:bg-green-800'
                                ]"
                            >
                                {{ note.done ? 'Mark Undone' : 'Mark Done' }}
                            </button>
                            <button class="rounded-md px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-100 dark:text-red-300 dark:hover:bg-red-900">
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

