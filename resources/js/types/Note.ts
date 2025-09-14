export interface Note {
    id: string;
    content: string;
    done: boolean;
    created_at: string;
    updated_at: string;
}

export interface NoteEvent {
    note: Note;
    action?: string;
    timestamp?: string;
}

export interface NoteDeletedEvent {
    noteId: string;
    action?: string;
}

export interface NoteStats {
    total: number;
    completed: number;
    pending: number;
    recent: Note[];
}