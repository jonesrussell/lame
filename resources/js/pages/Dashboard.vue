<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard, notes } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle, Clock, FileText, Plus } from 'lucide-vue-next';

interface NoteStats {
    total: number;
    completed: number;
    pending: number;
    recent: Array<{
        id: string;
        content: string;
        done: boolean;
        created_at: string;
        updated_at: string;
    }>;
}

interface Props {
    noteStats: NoteStats;
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Note Statistics Cards -->
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <!-- Total Notes -->
                <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Total Notes</p>
                            <p class="text-3xl font-bold">{{ noteStats.total }}</p>
                        </div>
                        <FileText class="h-8 w-8 text-muted-foreground" />
                    </div>
                </div>

                <!-- Completed Notes -->
                <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Completed</p>
                            <p class="text-3xl font-bold text-green-600">{{ noteStats.completed }}</p>
                        </div>
                        <CheckCircle class="h-8 w-8 text-green-600" />
                    </div>
                </div>

                <!-- Pending Notes -->
                <div class="relative overflow-hidden rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">Pending</p>
                            <p class="text-3xl font-bold text-orange-600">{{ noteStats.pending }}</p>
                        </div>
                        <Clock class="h-8 w-8 text-orange-600" />
                    </div>
                </div>
            </div>

            <!-- Recent Notes Section -->
            <div class="relative flex-1 rounded-xl border border-sidebar-border/70 bg-card p-6 dark:border-sidebar-border">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Recent Notes</h2>
                    <Link
                        :href="notes().url"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        <Plus class="h-4 w-4" />
                        View All Notes
                    </Link>
                </div>

                <div v-if="noteStats.recent.length === 0" class="text-center py-8">
                    <FileText class="mx-auto h-12 w-12 text-muted-foreground" />
                    <p class="mt-2 text-sm text-muted-foreground">No notes yet</p>
                    <Link
                        :href="notes().url"
                        class="mt-2 inline-flex items-center gap-2 text-sm text-primary hover:underline"
                    >
                        <Plus class="h-4 w-4" />
                        Create your first note
                    </Link>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="note in noteStats.recent"
                        :key="note.id"
                        class="flex items-center justify-between rounded-lg border p-3"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                :class="[
                                    'h-2 w-2 rounded-full',
                                    note.done ? 'bg-green-500' : 'bg-orange-500'
                                ]"
                            />
                            <p :class="[
                                'text-sm',
                                note.done ? 'line-through text-muted-foreground' : ''
                            ]">
                                {{ note.content }}
                            </p>
                        </div>
                        <span class="text-xs text-muted-foreground">
                            {{ new Date(note.created_at).toLocaleDateString() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
