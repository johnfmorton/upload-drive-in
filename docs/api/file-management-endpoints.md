# File Management API Documentation

## Overview

This document describes the API endpoints for the enhanced file management dashboard system. These endpoints provide comprehensive file management capabilities including bulk operations, file preview, direct downloads, and advanced filtering.

## Authentication

All endpoints require authentication and appropriate role-based permissions:
- **Admin**: Full access to all files and operations
- **Employee**: Access to files from managed clients
- **Client**: Access to own uploaded files only

## Base URL

All endpoints are prefixed with `/admin/file-manager` for admin users.

## Endpoints

### GET /admin/file-manager

**Description**: Retrieve paginated list of files with filtering and sorting options.

**Parameters**:
- `page` (integer, optional): Page number for pagination (default: 1)
- `per_page` (integer, optional): Items per page (default: 15, max: 100)
- `search` (string, optional): Search term for filename filtering
- `sort_by` (string, optional): Column to sort by (default: 'created_at')
  - Valid values: `original_filename`, `file_size`, `created_at`, `uploaded_by`
- `sort_direction` (string, optional): Sort direction (default: 'desc')
  - Valid values: `asc`, `desc`
- `file_type` (string, optional): Filter by file type
  - Valid values: `image`, `document`, `video`, `audio`, `other`
- `date_from` (date, optional): Filter files uploaded after this date (Y-m-d format)
- `date_to` (date, optional): Filter files uploaded before this date (Y-m-d format)
- `uploaded_by` (integer, optional): Filter by uploader user ID

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "original_filename": "document.pdf",
      "file_size": 1048576,
      "file_size_human": "1.00 MB",
      "mime_type": "application/pdf",
      "created_at": "2025-07-18T10:30:00Z",
      "uploaded_by": {
        "id": 2,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "can_preview": true,
      "preview_url": "/admin/file-manager/1/preview",
      "download_url": "/admin/file-manager/1/download",
      "thumbnail_url": "/admin/file-manager/1/thumbnail"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 150,
    "last_page": 10
  }
}
```

### POST /admin/file-manager/bulk-delete

**Description**: Delete multiple files in a single operation.

**Request Body**:
```json
{
  "file_ids": [1, 2, 3, 4, 5]
}
```

**Validation Rules**:
- `file_ids`: required, array, min:1, max:100
- `file_ids.*`: required, integer, exists:file_uploads,id

**Response**:
```json
{
  "success": true,
  "message": "Successfully deleted 4 out of 5 files",
  "results": {
    "processed": 5,
    "successful": 4,
    "failed": 1,
    "details": [
      {
        "file_id": 1,
        "status": "success",
        "message": "File deleted successfully"
      },
      {
        "file_id": 5,
        "status": "error",
        "message": "File not found or access denied"
      }
    ]
  }
}
```

### GET /admin/file-manager/{file}/preview

**Description**: Preview file content directly in the browser.

**Parameters**:
- `file` (integer): File ID from the URL path

**Response**: 
- For images: Returns image content with appropriate headers
- For PDFs: Returns PDF content for browser display
- For text files: Returns formatted HTML with syntax highlighting
- For unsupported types: Returns JSON with file metadata

**Headers**:
- `Content-Type`: Appropriate MIME type for the file
- `Content-Disposition`: inline; filename="original_filename"
- `Cache-Control`: public, max-age=3600

### GET /admin/file-manager/{file}/download

**Description**: Download file directly to user's device.

**Parameters**:
- `file` (integer): File ID from the URL path

**Response**: File content with download headers

**Headers**:
- `Content-Type`: application/octet-stream or file's MIME type
- `Content-Disposition`: attachment; filename="original_filename"
- `Content-Length`: File size in bytes

### POST /admin/file-manager/bulk-download

**Description**: Download multiple files as a ZIP archive.

**Request Body**:
```json
{
  "file_ids": [1, 2, 3],
  "archive_name": "selected_files.zip"
}
```

**Validation Rules**:
- `file_ids`: required, array, min:1, max:50
- `file_ids.*`: required, integer, exists:file_uploads,id
- `archive_name`: optional, string, max:255, regex:/^[a-zA-Z0-9._-]+\.zip$/

**Response**: ZIP file download with appropriate headers

**Headers**:
- `Content-Type`: application/zip
- `Content-Disposition`: attachment; filename="archive_name.zip"

### GET /admin/file-manager/{file}/thumbnail

**Description**: Get thumbnail image for supported file types.

**Parameters**:
- `file` (integer): File ID from the URL path
- `size` (string, optional): Thumbnail size (default: 'medium')
  - Valid values: `small` (150px), `medium` (300px), `large` (600px)

**Response**: Thumbnail image or default placeholder

**Headers**:
- `Content-Type`: image/jpeg or image/png
- `Cache-Control`: public, max-age=86400

## Error Responses

### Standard Error Format

```json
{
  "success": false,
  "message": "Human-readable error message",
  "errors": {
    "field_name": ["Specific validation error messages"]
  },
  "code": "ERROR_CODE"
}
```

### Common Error Codes

- `FILE_NOT_FOUND`: Requested file does not exist
- `ACCESS_DENIED`: User lacks permission to access the file
- `INVALID_FILE_TYPE`: File type not supported for the operation
- `STORAGE_ERROR`: Error accessing file storage (local or Google Drive)
- `VALIDATION_ERROR`: Request validation failed
- `RATE_LIMIT_EXCEEDED`: Too many requests in a short period

### HTTP Status Codes

- `200`: Success
- `400`: Bad Request (validation errors)
- `401`: Unauthorized (not authenticated)
- `403`: Forbidden (insufficient permissions)
- `404`: Not Found (file or endpoint not found)
- `422`: Unprocessable Entity (validation failed)
- `429`: Too Many Requests (rate limited)
- `500`: Internal Server Error

## Rate Limiting

- **Download endpoints**: 60 requests per minute per user
- **Preview endpoints**: 120 requests per minute per user
- **Bulk operations**: 10 requests per minute per user
- **General endpoints**: 300 requests per minute per user

## File Size Limits

- **Individual downloads**: No limit (streaming supported)
- **Bulk ZIP downloads**: Maximum 500MB total uncompressed size
- **Preview generation**: Maximum 50MB per file
- **Thumbnail generation**: Maximum 20MB per file

## Caching

- **File metadata**: Cached for 1 hour
- **Thumbnails**: Cached for 24 hours
- **Preview content**: Cached for 1 hour
- **User permissions**: Cached for 15 minutes

## Security Considerations

- All file access is logged for audit purposes
- CSRF protection required for state-changing operations
- File content is validated before serving
- Signed URLs used for direct file access
- Rate limiting prevents abuse