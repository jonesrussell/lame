# Go Note App - API Integration Prompt

## Overview
Add API integration to your Go Note application with configurable storage backends. The app should support both local SQLite storage (current implementation) and remote Laravel API storage, with the ability to switch between them via configuration.

## Current Go Model Reference
Your existing Go Note model:
```go
type Note struct {
    ID        string    `json:"id"`
    Content   string    `json:"content"`
    Done      bool      `json:"done"`
    CreatedAt time.Time `json:"created_at"`
    UpdatedAt time.Time `json:"updated_at"`
}
```

## Laravel API Endpoints Available
The Laravel API provides these endpoints:
- `GET /api/notes` - List all notes
- `POST /api/notes` - Create note (body: `{"content": "string"}`)
- `GET /api/notes/{id}` - Get specific note
- `PATCH /api/notes/{id}` - Update note (body: `{"content": "string", "done": bool}`)
- `DELETE /api/notes/{id}` - Delete note
- `PATCH /api/notes/{id}/toggle` - Toggle done status
- `PATCH /api/notes/{id}/mark-done` - Mark as done
- `PATCH /api/notes/{id}/mark-undone` - Mark as undone

## Implementation Requirements

### 1. Configuration System
Create a configuration system that allows switching between storage backends:

```go
type Config struct {
    StorageType string `json:"storage_type"` // "sqlite" or "api"
    APIConfig   struct {
        BaseURL string `json:"base_url"`
        Timeout int    `json:"timeout_seconds"`
    } `json:"api_config"`
    SQLiteConfig struct {
        FilePath string `json:"file_path"`
    } `json:"sqlite_config"`
}
```

### 2. Storage Interface
Define a common interface for both storage implementations:

```go
type NoteStorage interface {
    CreateNote(content string) (*Note, error)
    GetNote(id string) (*Note, error)
    GetAllNotes() ([]*Note, error)
    UpdateNote(id string, content string, done bool) (*Note, error)
    DeleteNote(id string) error
    ToggleDone(id string) (*Note, error)
    MarkDone(id string) (*Note, error)
    MarkUndone(id string) (*Note, error)
}
```

### 3. API Storage Implementation
Create an API storage implementation that:
- Uses HTTP client with configurable timeout
- Handles JSON marshaling/unmarshaling
- Implements proper error handling for HTTP status codes
- Maps Laravel API responses to Go Note structs
- Handles validation errors from the API

### 4. Storage Factory
Create a factory function that returns the appropriate storage implementation based on configuration:

```go
func NewNoteStorage(config Config) (NoteStorage, error) {
    switch config.StorageType {
    case "sqlite":
        return NewSQLiteStorage(config.SQLiteConfig)
    case "api":
        return NewAPIStorage(config.APIConfig)
    default:
        return nil, fmt.Errorf("unsupported storage type: %s", config.StorageType)
    }
}
```

### 5. Error Handling
Implement proper error handling that:
- Distinguishes between network errors and API errors
- Handles HTTP status codes appropriately
- Provides meaningful error messages
- Maintains compatibility with existing error handling

### 6. Configuration File
Create a configuration file (JSON/YAML) that allows easy switching:

```json
{
    "storage_type": "api",
    "api_config": {
        "base_url": "http://localhost:8000/api",
        "timeout_seconds": 30
    },
    "sqlite_config": {
        "file_path": "./notes.db"
    }
}
```

## Implementation Steps

1. **Create the storage interface** - Define the common interface
2. **Implement API storage** - Create HTTP client wrapper for Laravel API
3. **Refactor existing SQLite code** - Wrap current implementation in interface
4. **Add configuration system** - Load config from file/environment
5. **Create storage factory** - Switch between implementations
6. **Update main application** - Use storage interface instead of direct SQLite calls
7. **Add error handling** - Handle API-specific errors
8. **Test both backends** - Ensure feature parity between storage types

## API Response Format
The Laravel API returns responses in this format:
```json
{
    "data": {
        "id": "uuid-string",
        "content": "note content",
        "done": false,
        "created_at": "2025-01-01T00:00:00Z",
        "updated_at": "2025-01-01T00:00:00Z"
    },
    "message": "Success message"
}
```

## Error Response Format
API errors return:
```json
{
    "message": "Error message",
    "errors": {
        "field": ["validation error message"]
    }
}
```

## Testing Considerations
- Test both storage backends with the same operations
- Verify error handling for network failures
- Test configuration switching
- Ensure data consistency between backends
- Test timeout handling for API calls

## Additional Features to Consider
- Connection pooling for API calls
- Retry logic for failed API requests
- Caching layer for API responses
- Health check endpoint for API availability
- Metrics/logging for API call performance

This implementation will allow your Go application to seamlessly switch between local SQLite storage and the Laravel API backend while maintaining the same interface and functionality.
