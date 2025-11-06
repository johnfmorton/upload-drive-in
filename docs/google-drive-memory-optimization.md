# Google Drive Memory Optimization

This document describes the solution implemented to fix memory exhaustion issues when uploading large files to Google Drive.

## Problem Description

When uploading large files (>200MB) to Google Drive, the application was experiencing memory exhaustion errors:

```
Allowed memory size of 536870912 bytes exhausted (tried to allocate 273801692 bytes)
```

This occurred because the original implementation loaded entire files into memory before uploading them to Google Drive.

## Root Cause

The issue was in the `GoogleDriveService::uploadFileForUser()` method:

```php
// OLD CODE - Loads entire file into memory
$content = Storage::disk('public')->get($localRelativePath);
$createdFile = $driveService->files->create($fileMetadata, [
    'data' => $content,  // Entire file in memory
    'mimeType' => $mimeType,
    'uploadType' => 'multipart',
    'fields' => 'id'
]);
```

For a 200MB file on a server with 512MB memory limit, this approach fails because:
1. File content (200MB) + existing memory usage + PHP overhead > 512MB limit
2. Google API client may create additional copies of the data internally

## Solution: Chunked/Resumable Uploads

### Implementation Overview

1. **GoogleDriveChunkedUploadService**: New service for handling large file uploads in chunks
2. **Automatic Detection**: Files are automatically routed to chunked upload based on size and memory constraints
3. **Memory-Safe Processing**: Files are read and uploaded in small chunks without loading the entire file into memory
4. **Configuration**: Fully configurable chunk sizes and thresholds

### Key Components

#### 1. GoogleDriveChunkedUploadService

```php
public function uploadFileChunked(
    User $user,
    string $localPath,
    string $targetFolderId,
    string $filename,
    string $mimeType,
    ?string $description = null,
    ?int $chunkSize = null
): string
```

**Features:**
- Reads files using file handles (no memory loading)
- Uploads in configurable chunks (default 8MB)
- Progress tracking and logging
- Automatic retry capability
- Memory-aware chunk size optimization

#### 2. Automatic Upload Routing

The `GoogleDriveService::uploadFileForUser()` method now automatically chooses the appropriate upload method:

```php
// Check if we should use chunked upload for large files
$chunkedUploadService = app(GoogleDriveChunkedUploadService::class);
if ($chunkedUploadService->shouldUseChunkedUpload($fileSize)) {
    // Use chunked upload for large files
    return $chunkedUploadService->uploadFileChunked(...);
} else {
    // Use traditional upload for small files
    // ... existing code
}
```

#### 3. Smart Decision Logic

The system decides whether to use chunked upload based on:

1. **File Size Threshold**: Files larger than 50MB (configurable)
2. **Memory Constraints**: Files that would use more than 25% of available memory
3. **Configuration**: Can be enabled/disabled via config

### Configuration Options

All settings are configurable in `config/cloud-storage.php`:

```php
'chunked_upload' => [
    'enabled' => env('GOOGLE_DRIVE_CHUNKED_UPLOAD_ENABLED', true),
    'threshold_bytes' => env('GOOGLE_DRIVE_CHUNKED_UPLOAD_THRESHOLD', 52428800), // 50MB
    'memory_threshold_percent' => env('GOOGLE_DRIVE_MEMORY_THRESHOLD_PERCENT', 25), // 25%
    'default_chunk_size' => env('GOOGLE_DRIVE_DEFAULT_CHUNK_SIZE', 8388608), // 8MB
    'min_chunk_size' => 262144, // 256KB
    'max_chunk_size' => 104857600, // 100MB
    'chunk_delay_ms' => env('GOOGLE_DRIVE_CHUNK_DELAY_MS', 100), // Delay between chunks
],
```

### Environment Variables

Add these to your `.env` file to customize behavior:

```env
# Enable/disable chunked uploads
GOOGLE_DRIVE_CHUNKED_UPLOAD_ENABLED=true

# File size threshold for chunked uploads (bytes)
GOOGLE_DRIVE_CHUNKED_UPLOAD_THRESHOLD=52428800

# Memory threshold percentage (1-100)
GOOGLE_DRIVE_MEMORY_THRESHOLD_PERCENT=25

# Default chunk size (bytes)
GOOGLE_DRIVE_DEFAULT_CHUNK_SIZE=8388608

# Delay between chunks (milliseconds)
GOOGLE_DRIVE_CHUNK_DELAY_MS=100
```

## Performance Characteristics

### Memory Usage

| File Size | Old Method | New Method (Chunked) |
|-----------|------------|---------------------|
| 50MB      | ~50MB RAM  | ~8MB RAM           |
| 200MB     | ~200MB RAM | ~8MB RAM           |
| 1GB       | FAILS      | ~16MB RAM          |

### Upload Performance

- **Chunk Size**: Automatically optimized based on file size and available memory
- **Progress Tracking**: Detailed logging of upload progress
- **Error Recovery**: Individual chunk failures can be retried
- **API Rate Limiting**: Built-in delays to prevent overwhelming Google's API

### Typical Performance

For a 200MB file on a 512MB memory server:
- **Memory Usage**: ~8-16MB (vs 200MB+ previously)
- **Upload Time**: Similar to original (network bound)
- **Reliability**: Much higher (no memory exhaustion)

## Fixing Existing Pending Uploads

### Command: `uploads:fix-pending`

A new Artisan command helps identify and fix pending uploads:

```bash
# Analyze pending uploads (dry run)
php artisan uploads:fix-pending --dry-run

# Retry failed uploads
php artisan uploads:fix-pending --retry-failed

# Clean up records for missing local files
php artisan uploads:fix-pending --cleanup-missing

# Force retry even recently failed uploads
php artisan uploads:fix-pending --retry-failed --force
```

### Example Output

```
🔍 Analyzing pending uploads...

Found 3 pending uploads:

📁 ID: 68 | IMG_9729.MOV | 200.5 MB 🔥
   Email: morton@jmx2.com | Created: 2025-11-06 12:18:20
   💡 Large file - will use chunked upload

❌ ID: 67 | document.pdf | N/A
   Email: client@example.com | Created: 2025-11-05 10:30:15
   ⚠️  Local file missing: uploads/abc123.pdf

📁 ID: 66 | photo.jpg | 15.2 MB
   Email: user@domain.com | Created: 2025-11-05 09:15:30

📊 Summary:
   Total pending uploads: 3
   With local files: 2
   Missing local files: 1
   Large files (>50MB): 1
```

## Monitoring and Logging

### Upload Progress Logging

Chunked uploads provide detailed progress information:

```json
{
  "message": "Uploaded chunk to Google Drive",
  "user_id": 3,
  "chunk_number": 5,
  "chunk_size": 8388608,
  "uploaded_bytes": 41943040,
  "total_bytes": 209715200,
  "progress_percent": 20.0,
  "chunk_duration_ms": 1250.5,
  "filename": "IMG_9729.MOV"
}
```

### Completion Logging

```json
{
  "message": "Chunked upload to Google Drive completed successfully",
  "user_id": 3,
  "file_id": "1abc123def456ghi789",
  "filename": "IMG_9729.MOV",
  "file_size": 209715200,
  "total_chunks": 25,
  "chunk_size": 8388608,
  "total_duration_ms": 45230.2,
  "average_speed_mbps": 4.2
}
```

## Testing

### Automated Tests

The solution includes comprehensive tests in `tests/Feature/GoogleDriveChunkedUploadTest.php`:

- Chunk size optimization
- Memory threshold detection
- Configuration validation
- Progress calculation
- Boundary condition handling

### Manual Testing

```bash
# Run chunked upload tests
php artisan test tests/Feature/GoogleDriveChunkedUploadTest.php

# Test with actual large files (requires Google Drive setup)
php artisan uploads:fix-pending --dry-run
```

## Production Deployment

### Pre-Deployment Checklist

1. **Backup Database**: Ensure file upload records are backed up
2. **Check Memory Limits**: Verify server memory configuration
3. **Test Configuration**: Validate chunked upload settings
4. **Monitor Disk Space**: Ensure adequate space for temporary files

### Deployment Steps

1. **Deploy Code**: Deploy the new chunked upload implementation
2. **Update Configuration**: Add chunked upload settings to production config
3. **Fix Pending Uploads**: Run the fix command to retry failed uploads
4. **Monitor Performance**: Watch logs for successful chunked uploads

### Post-Deployment Verification

```bash
# Check for pending uploads
php artisan uploads:fix-pending --dry-run

# Monitor upload success rate
tail -f storage/logs/laravel.log | grep "Chunked upload.*completed"

# Verify memory usage is stable
# (Monitor server memory usage during large file uploads)
```

## Troubleshooting

### Common Issues

#### 1. Chunked Upload Not Triggering

**Symptoms**: Large files still use traditional upload
**Causes**: 
- Chunked upload disabled in config
- File size below threshold
- Memory threshold too high

**Solutions**:
```bash
# Check configuration
php artisan config:show cloud-storage.providers.google-drive.chunked_upload

# Lower threshold for testing
GOOGLE_DRIVE_CHUNKED_UPLOAD_THRESHOLD=10485760  # 10MB
```

#### 2. Slow Upload Performance

**Symptoms**: Uploads taking much longer than expected
**Causes**:
- Chunk size too small
- Network latency
- API rate limiting

**Solutions**:
```env
# Increase chunk size
GOOGLE_DRIVE_DEFAULT_CHUNK_SIZE=16777216  # 16MB

# Reduce delay between chunks
GOOGLE_DRIVE_CHUNK_DELAY_MS=50
```

#### 3. Memory Still High

**Symptoms**: Memory usage higher than expected
**Causes**:
- Chunk size too large
- Multiple concurrent uploads
- Memory leaks in other parts of application

**Solutions**:
```env
# Reduce chunk size
GOOGLE_DRIVE_DEFAULT_CHUNK_SIZE=4194304  # 4MB

# Lower memory threshold
GOOGLE_DRIVE_MEMORY_THRESHOLD_PERCENT=15
```

### Debug Commands

```bash
# Check memory limits
php -r "echo 'Memory Limit: ' . ini_get('memory_limit') . PHP_EOL;"

# Test chunked upload decision
php artisan tinker
>>> $service = app(\App\Services\GoogleDriveChunkedUploadService::class);
>>> $service->shouldUseChunkedUpload(200 * 1024 * 1024); // 200MB

# Monitor upload progress
tail -f storage/logs/laravel.log | grep "chunk"
```

## Performance Recommendations

### For Different Server Configurations

#### Low Memory Servers (512MB - 1GB)
```env
GOOGLE_DRIVE_CHUNKED_UPLOAD_THRESHOLD=20971520  # 20MB
GOOGLE_DRIVE_DEFAULT_CHUNK_SIZE=4194304         # 4MB
GOOGLE_DRIVE_MEMORY_THRESHOLD_PERCENT=15        # 15%
```

#### Medium Memory Servers (2GB - 4GB)
```env
GOOGLE_DRIVE_CHUNKED_UPLOAD_THRESHOLD=52428800  # 50MB
GOOGLE_DRIVE_DEFAULT_CHUNK_SIZE=8388608         # 8MB
GOOGLE_DRIVE_MEMORY_THRESHOLD_PERCENT=25        # 25%
```

#### High Memory Servers (8GB+)
```env
GOOGLE_DRIVE_CHUNKED_UPLOAD_THRESHOLD=104857600 # 100MB
GOOGLE_DRIVE_DEFAULT_CHUNK_SIZE=16777216        # 16MB
GOOGLE_DRIVE_MEMORY_THRESHOLD_PERCENT=35        # 35%
```

## Future Enhancements

### Planned Improvements

1. **Resume Interrupted Uploads**: Support for resuming partially uploaded files
2. **Parallel Chunk Upload**: Upload multiple chunks simultaneously
3. **Compression**: Optional compression for certain file types
4. **Progress UI**: Real-time upload progress in the admin interface
5. **Bandwidth Throttling**: Configurable upload speed limits

### Integration Opportunities

1. **Queue Monitoring**: Integration with Laravel Horizon for upload job monitoring
2. **Metrics Collection**: Detailed upload performance metrics
3. **Alerting**: Notifications for failed uploads or performance issues
4. **Auto-scaling**: Dynamic chunk size adjustment based on server load

## Conclusion

The chunked upload implementation successfully resolves memory exhaustion issues while maintaining upload reliability and performance. The solution is:

- **Memory Efficient**: Uses minimal memory regardless of file size
- **Configurable**: Fully customizable for different server environments
- **Reliable**: Includes comprehensive error handling and retry logic
- **Monitorable**: Provides detailed logging and progress tracking
- **Backward Compatible**: Automatically falls back to traditional uploads for small files

This implementation ensures that large file uploads (including the 200MB file mentioned in the original issue) will complete successfully without memory exhaustion errors.