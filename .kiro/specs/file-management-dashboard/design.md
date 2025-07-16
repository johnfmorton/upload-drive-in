# Design Document

## Overview

This design document outlines the technical approach for enhancing the file management dashboard to address current usability issues including batch operations, responsive layout, file preview/download capabilities, and improved user experience. The solution will replace the current dual mobile/desktop views with a unified responsive interface that supports bulk operations and direct file access without external Google Drive dependencies.

## Architecture

### High-Level Architecture

The enhanced file management system will follow Laravel's MVC pattern with additional service layers:

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Controllers    │    │   Services      │
│   (Blade/Alpine)│◄──►│   Dashboard      │◄──►│   FileManager   │
│                 │    │   FileManager    │    │   GoogleDrive   │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                                │                        │
                                ▼                        ▼
                       ┌──────────────────┐    ┌─────────────────┐
                       │     Models       │    │   Storage       │
                       │   FileUpload     │    │   Local/Drive   │
                       │   User           │    │                 │
                       └──────────────────┘    └─────────────────┘
```

### Component Structure

1. **FileManagerController**: New controller handling file operations
2. **FileManagerService**: Business logic for file operations
3. **FilePreviewService**: File preview and download logic
4. **Enhanced GoogleDriveService**: Extended with download capabilities
5. **Unified Dashboard View**: Single responsive Blade template
6. **Alpine.js Components**: Client-side interactivity

## Components and Interfaces

### 1. FileManagerController

```php
class FileManagerController extends Controller
{
    public function index(Request $request): View
    public function bulkDelete(BulkDeleteRequest $request): JsonResponse
    public function preview(FileUpload $file): Response
    public function download(FileUpload $file): Response
    public function bulkDownload(BulkDownloadRequest $request): Response
}
```

**Key Methods:**
- `index()`: Returns paginated file list with filtering/sorting
- `bulkDelete()`: Handles multiple file deletion
- `preview()`: Serves file preview (images, PDFs, text)
- `download()`: Direct file download
- `bulkDownload()`: Creates ZIP archive for multiple files

### 2. FileManagerService

```php
class FileManagerService
{
    public function getFilesForUser(User $user, array $filters = []): LengthAwarePaginator
    public function bulkDelete(array $fileIds, User $user): BulkOperationResult
    public function canUserAccessFile(User $user, FileUpload $file): bool
    public function getFileContent(FileUpload $file): string
    public function createBulkDownloadArchive(array $fileIds, User $user): string
}
```

**Responsibilities:**
- File access control based on user roles
- Bulk operations coordination
- File content retrieval from local/Google Drive
- ZIP archive creation for bulk downloads

### 3. FilePreviewService

```php
class FilePreviewService
{
    public function canPreview(string $mimeType): bool
    public function generatePreview(FileUpload $file): PreviewResponse
    public function getPreviewHtml(FileUpload $file): string
    public function getThumbnail(FileUpload $file): ?string
}
```

**Supported Preview Types:**
- Images: Direct display with zoom/pan
- PDFs: PDF.js integration
- Text files: Syntax highlighted display
- Office docs: Basic metadata display
- Unsupported: File info with download option

### 4. Enhanced GoogleDriveService

**New Methods:**
```php
public function downloadFile(User $user, string $fileId): string
public function getFileMetadata(User $user, string $fileId): array
public function bulkDownloadFiles(User $user, array $fileIds): array
```

**Enhanced Capabilities:**
- Direct file content download from Google Drive
- Metadata retrieval without full file download
- Batch operations support
- Error handling for missing/inaccessible files

## Data Models

### FileUpload Model Enhancements

**New Methods:**
```php
public function canBeAccessedBy(User $user): bool
public function getPreviewUrl(): ?string
public function getDownloadUrl(): string
public function getThumbnailUrl(): ?string
public function isPreviewable(): bool
```

**New Attributes:**
```php
protected $appends = [
    'can_preview',
    'preview_url',
    'download_url',
    'thumbnail_url'
];
```

### User Permission Matrix

| Role     | Own Files | Client Files | All Files | Bulk Operations |
|----------|-----------|--------------|-----------|-----------------|
| Admin    | ✓         | ✓            | ✓         | ✓               |
| Employee | ✓         | ✓ (managed)  | ✗         | ✓ (limited)     |
| Client   | ✓         | ✗            | ✗         | ✓ (own only)    |

## Error Handling

### File Access Errors

1. **File Not Found**: Return 404 with user-friendly message
2. **Permission Denied**: Return 403 with role-specific guidance
3. **Google Drive API Errors**: Graceful fallback to local storage
4. **Large File Handling**: Streaming downloads with progress indication

### Bulk Operation Errors

1. **Partial Failures**: Return detailed results with success/failure counts
2. **Permission Conflicts**: Skip unauthorized files, process authorized ones
3. **Storage Errors**: Retry mechanism with exponential backoff
4. **Timeout Handling**: Background processing for large operations

### Error Response Format

```json
{
    "success": false,
    "message": "User-friendly error message",
    "errors": {
        "field": ["Specific validation errors"]
    },
    "partial_success": {
        "processed": 5,
        "failed": 2,
        "details": [...]
    }
}
```

## Testing Strategy

### Unit Tests

1. **FileManagerService Tests**
   - Permission checking logic
   - File content retrieval
   - Bulk operation coordination

2. **FilePreviewService Tests**
   - MIME type detection
   - Preview generation
   - Thumbnail creation

3. **Model Tests**
   - Permission methods
   - URL generation
   - Scope queries

### Feature Tests

1. **File Management Endpoints**
   - Bulk delete operations
   - File preview/download
   - Permission enforcement

2. **UI Interaction Tests**
   - Checkbox selection
   - Responsive layout
   - Modal interactions

3. **Integration Tests**
   - Google Drive API integration
   - File streaming
   - ZIP archive creation

### Performance Tests

1. **Large File Handling**
   - Memory usage during streaming
   - Download speed optimization
   - Concurrent request handling

2. **Bulk Operations**
   - Processing time for large batches
   - Database query optimization
   - Background job performance

## Frontend Design

### Unified Responsive Layout

**Breakpoint Strategy:**
- Mobile: < 768px (Card layout)
- Tablet: 768px - 1024px (Hybrid layout)
- Desktop: > 1024px (Table layout)

**Layout Components:**

1. **File List Container**
   ```html
   <div class="file-manager" x-data="fileManager()">
     <!-- Toolbar -->
     <div class="toolbar">
       <div class="bulk-actions">
         <input type="checkbox" x-model="selectAll">
         <button x-show="selectedFiles.length" @click="bulkDelete()">
           Delete Selected ({{ selectedFiles.length }})
         </button>
       </div>
       <div class="view-controls">
         <button @click="toggleView()">Toggle View</button>
         <input type="search" x-model="searchQuery">
       </div>
     </div>
     
     <!-- File Grid/Table -->
     <div class="file-grid" :class="viewMode">
       <!-- Dynamic content -->
     </div>
   </div>
   ```

2. **File Item Component**
   ```html
   <div class="file-item" :class="{ 'selected': isSelected(file.id) }">
     <input type="checkbox" :checked="isSelected(file.id)" 
            @change="toggleSelection(file.id)">
     <div class="file-preview">
       <img x-show="file.can_preview" :src="file.thumbnail_url">
     </div>
     <div class="file-info">
       <h3 class="filename">{{ file.original_filename }}</h3>
       <p class="metadata">{{ file.file_size_human }} • {{ file.created_at }}</p>
     </div>
     <div class="file-actions">
       <button @click="previewFile(file)" x-show="file.can_preview">
         Preview
       </button>
       <button @click="downloadFile(file)">Download</button>
       <button @click="deleteFile(file)" class="danger">Delete</button>
     </div>
   </div>
   ```

### Alpine.js State Management

```javascript
function fileManager() {
    return {
        files: [],
        selectedFiles: [],
        viewMode: 'grid', // 'grid' or 'table'
        searchQuery: '',
        sortBy: 'created_at',
        sortDirection: 'desc',
        
        // Selection methods
        selectAll: {
            get() { return this.selectedFiles.length === this.filteredFiles.length; },
            set(value) { 
                this.selectedFiles = value ? this.filteredFiles.map(f => f.id) : [];
            }
        },
        
        // File operations
        async bulkDelete() {
            const response = await fetch('/admin/files/bulk-delete', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ file_ids: this.selectedFiles })
            });
            // Handle response
        },
        
        async previewFile(file) {
            // Open preview modal
        },
        
        async downloadFile(file) {
            window.location.href = file.download_url;
        }
    }
}
```

### CSS Grid/Flexbox Layout

```css
.file-manager {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.file-grid {
    display: grid;
    gap: 1rem;
}

/* Responsive grid */
.file-grid.grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
}

.file-grid.table {
    grid-template-columns: auto 1fr auto auto auto;
    grid-template-rows: auto;
}

/* Mobile-first responsive design */
@media (max-width: 768px) {
    .file-grid.table {
        grid-template-columns: 1fr;
    }
    
    .file-item {
        display: flex;
        flex-direction: column;
        padding: 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
    }
}

@media (min-width: 769px) {
    .file-grid.table .file-item {
        display: grid;
        grid-template-columns: subgrid;
        align-items: center;
        padding: 0.75rem;
        border-bottom: 1px solid #e5e7eb;
    }
}
```

## Security Considerations

### File Access Control

1. **Permission Middleware**: Verify user can access requested files
2. **Direct URL Protection**: Signed URLs for file downloads
3. **CSRF Protection**: All state-changing operations protected
4. **Rate Limiting**: Prevent abuse of download/preview endpoints

### File Content Security

1. **MIME Type Validation**: Verify file types match extensions
2. **Content Scanning**: Basic malware detection for uploads
3. **Sanitized Previews**: Safe rendering of file content
4. **Download Headers**: Proper Content-Disposition headers

### Google Drive Integration Security

1. **Token Validation**: Verify tokens before API calls
2. **Scope Limitation**: Minimal required permissions
3. **Error Information**: Don't expose internal API details
4. **Audit Logging**: Track all file access operations

## Performance Optimizations

### Database Optimizations

1. **Eager Loading**: Load related models efficiently
2. **Query Optimization**: Indexed columns for sorting/filtering
3. **Pagination**: Limit result sets for large file lists
4. **Caching**: Cache file metadata and user permissions

### File Handling Optimizations

1. **Streaming Downloads**: Memory-efficient large file handling
2. **Thumbnail Generation**: Cached preview images
3. **Lazy Loading**: Load file content only when needed
4. **CDN Integration**: Serve static assets from CDN

### Frontend Optimizations

1. **Virtual Scrolling**: Handle large file lists efficiently
2. **Debounced Search**: Reduce API calls during typing
3. **Progressive Loading**: Load file metadata incrementally
4. **Image Optimization**: Responsive images with proper sizing

## Migration Strategy

### Phase 1: Backend Infrastructure
1. Create new FileManagerController and services
2. Add new routes and middleware
3. Extend FileUpload model with new methods
4. Implement permission checking logic

### Phase 2: File Operations
1. Implement preview functionality
2. Add download capabilities
3. Create bulk operation endpoints
4. Test Google Drive integration

### Phase 3: Frontend Replacement
1. Create new unified dashboard view
2. Implement Alpine.js components
3. Add responsive CSS framework
4. Test across devices and browsers

### Phase 4: Testing and Deployment
1. Comprehensive testing suite
2. Performance optimization
3. Security audit
4. Gradual rollout with feature flags