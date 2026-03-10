# ClamAV Virus Scanning Integration Plan

## Overview

This document describes how to add ClamAV-based virus scanning to the Upload Drive-In application. All five upload controllers already integrate `FileSecurityService` for extension blocking, MIME type validation, magic byte checking, and filename sanitization. ClamAV scanning will be added as an additional validation step within that same service.

## Current Architecture

### FileSecurityService Integration Points

The `FileSecurityService` is injected and called in all upload controllers via `validateFileUpload()`:

1. **`app/Http/Controllers/UploadController.php`** - Authenticated user uploads (`saveFile` method)
2. **`app/Http/Controllers/Client/UploadController.php`** - Client uploads (`saveFile` method)
3. **`app/Http/Controllers/Employee/UploadController.php`** - Employee uploads (`store` method)
4. **`app/Http/Controllers/FileUploadController.php`** - Public file uploads (`store` method)
5. **`app/Http/Controllers/PublicEmployeeUploadController.php`** - Public employee uploads (`upload`, `uploadByName`, `saveChunkedFile` methods)

All controllers follow the same pattern:

```php
$violations = $this->fileSecurityService->validateFileUpload($file);
if (!empty($violations)) {
    return response()->json(['error' => $violations[0]['message']], 422);
}
```

### FileSecurityService Location

- **Service:** `app/Services/FileSecurityService.php`
- **Registration:** Should be bound in `AppServiceProvider` or auto-resolved via constructor injection

## Implementation Plan

### Step 1: Install ClamAV PHP Client

Add the `appwrite/php-clamav` package (or `seankndy/php-clamav`) for communicating with the ClamAV daemon via socket:

```bash
composer require appwrite/php-clamav
```

Alternatively, use `xenolope/quahog` which is another well-maintained ClamAV PHP client:

```bash
composer require xenolope/quahog
```

### Step 2: Add Configuration

Create or update `config/filesecurity.php`:

```php
return [
    'clamav' => [
        'enabled' => env('CLAMAV_ENABLED', false),
        'socket' => env('CLAMAV_SOCKET', '/var/run/clamav/clamd.ctl'),
        'host' => env('CLAMAV_HOST', '127.0.0.1'),
        'port' => env('CLAMAV_PORT', 3310),
        'connection_type' => env('CLAMAV_CONNECTION', 'socket'), // 'socket' or 'tcp'
        'timeout' => env('CLAMAV_TIMEOUT', 30),
        'max_file_size' => env('CLAMAV_MAX_FILE_SIZE', 25 * 1024 * 1024), // 25MB
    ],
];
```

Add to `.env.example`:

```env
CLAMAV_ENABLED=false
CLAMAV_SOCKET=/var/run/clamav/clamd.ctl
CLAMAV_HOST=127.0.0.1
CLAMAV_PORT=3310
CLAMAV_CONNECTION=socket
CLAMAV_TIMEOUT=30
```

### Step 3: Create ClamAV Scanner Service

Create `app/Services/ClamAvService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ClamAvService
{
    /**
     * Scan a file for viruses.
     *
     * @param string $filePath Absolute path to file
     * @return array{clean: bool, virus: string|null, error: string|null}
     */
    public function scan(string $filePath): array
    {
        if (!config('filesecurity.clamav.enabled')) {
            return ['clean' => true, 'virus' => null, 'error' => null];
        }

        try {
            $socket = $this->connect();
            // Send INSTREAM command and file contents
            // Parse response for OK / FOUND / ERROR
            // Return structured result
        } catch (\Exception $e) {
            Log::error('ClamAV scan failed', [
                'file' => $filePath,
                'error' => $e->getMessage(),
            ]);

            // Fail-open or fail-closed based on config
            // Default: fail-open (allow upload but log warning)
            if (config('filesecurity.clamav.fail_closed', false)) {
                return ['clean' => false, 'virus' => null, 'error' => $e->getMessage()];
            }

            return ['clean' => true, 'virus' => null, 'error' => $e->getMessage()];
        }
    }
}
```

### Step 4: Integrate into FileSecurityService

Add the ClamAV scan as a step in the existing `validateFileUpload()` method in `FileSecurityService`:

```php
public function __construct(
    private ClamAvService $clamAv
) {}

public function validateFileUpload(UploadedFile $file): array
{
    $violations = [];

    // Existing checks: extension, MIME type, magic bytes, content
    // ... (keep all existing validation) ...

    // ClamAV virus scan (runs last since it's the most expensive check)
    if (empty($violations)) {
        $scanResult = $this->clamAv->scan($file->getRealPath());

        if (!$scanResult['clean']) {
            $violations[] = [
                'type' => 'virus_detected',
                'message' => 'The uploaded file failed security scanning and cannot be accepted.',
            ];

            Log::warning('Virus detected in uploaded file', [
                'original_name' => $file->getClientOriginalName(),
                'virus' => $scanResult['virus'],
                'size' => $file->getSize(),
            ]);
        }
    }

    return $violations;
}
```

Key design decisions:
- ClamAV scan runs **after** all other checks pass (extension, MIME, magic bytes) to avoid scanning obviously invalid files
- The user-facing error message is generic and does not reveal the virus name
- The virus name is logged server-side for admin review
- If ClamAV is not enabled or not reachable, uploads proceed (fail-open by default)

### Step 5: No Controller Changes Required

Because all five upload controllers already call `$this->fileSecurityService->validateFileUpload($file)` and handle the violations array, no controller changes are needed. The ClamAV scanning is entirely encapsulated within `FileSecurityService`.

### Step 6: Add Health Check

Add a ClamAV status check to the setup/health system so admins know if scanning is active:

```php
// In SetupInstructionsController or a health check endpoint
public function clamavStatus(): array
{
    if (!config('filesecurity.clamav.enabled')) {
        return ['status' => 'disabled'];
    }

    try {
        $clamav = app(ClamAvService::class);
        $version = $clamav->version(); // PING or VERSION command
        return ['status' => 'connected', 'version' => $version];
    } catch (\Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}
```

### Step 7: Add Tests

Create `tests/Unit/ClamAvServiceTest.php` and `tests/Feature/VirusScanUploadTest.php`:

- **Unit tests:** Mock socket communication, test scan result parsing, test fail-open/fail-closed behavior, test disabled state
- **Feature tests:** Test that uploads with EICAR test string are rejected when ClamAV is enabled, test that uploads proceed when ClamAV is disabled

The EICAR test string (`X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*`) is the standard way to test antivirus without real malware.

## Infrastructure Requirements

### Option A: Local ClamAV Daemon (Recommended for VPS/dedicated)

```bash
# Ubuntu/Debian
apt-get install clamav clamav-daemon
systemctl enable clamav-daemon
systemctl start clamav-daemon
freshclam  # Update virus definitions
```

The daemon listens on a Unix socket (`/var/run/clamav/clamd.ctl`) by default.

### Option B: Docker Sidecar (Recommended for containerized deployments)

```yaml
# docker-compose.yml addition
services:
  clamav:
    image: clamav/clamav:stable
    ports:
      - "3310:3310"
    volumes:
      - clamav-data:/var/lib/clamav
    restart: unless-stopped

volumes:
  clamav-data:
```

Use TCP connection: `CLAMAV_CONNECTION=tcp`, `CLAMAV_HOST=clamav`, `CLAMAV_PORT=3310`.

### Option C: DDEV Integration (for development)

Add a ClamAV service to `.ddev/docker-compose.clamav.yaml`:

```yaml
services:
  clamav:
    container_name: ddev-${DDEV_SITENAME}-clamav
    image: clamav/clamav:stable
    expose:
      - "3310"
    volumes:
      - clamav-data:/var/lib/clamav
    labels:
      com.ddev.site-name: ${DDEV_SITENAME}

volumes:
  clamav-data:
```

### Virus Definition Updates

ClamAV needs regular virus definition updates via `freshclam`. This typically runs as a separate service/cron:

```bash
# Cron job (if not using clamav-freshclam service)
0 */6 * * * /usr/bin/freshclam --quiet
```

In Docker, the official `clamav/clamav` image handles freshclam automatically.

## Performance Considerations

- ClamAV scanning adds latency (typically 100-500ms per file depending on size)
- Run the scan **after** cheaper validation checks (extension, MIME) to minimize unnecessary scans
- The `max_file_size` config prevents scanning extremely large files that would timeout
- For chunked uploads, scanning happens on the reassembled file (after all chunks are received), not on individual chunks
- Consider async scanning via queue jobs for very large files (future enhancement)

## Rollout Strategy

1. Deploy with `CLAMAV_ENABLED=false` (default) - no behavior change
2. Install ClamAV on infrastructure
3. Set `CLAMAV_ENABLED=true` and monitor logs
4. Default fail-open means ClamAV daemon issues won't block uploads
5. Once stable, optionally switch to fail-closed for maximum security

## Files to Create or Modify

| File | Action | Description |
|------|--------|-------------|
| `config/filesecurity.php` | Create | ClamAV configuration |
| `app/Services/ClamAvService.php` | Create | ClamAV socket communication |
| `app/Services/FileSecurityService.php` | Modify | Add ClamAV scan to `validateFileUpload()` |
| `.env.example` | Modify | Add CLAMAV_* environment variables |
| `tests/Unit/ClamAvServiceTest.php` | Create | Unit tests for scanner |
| `tests/Feature/VirusScanUploadTest.php` | Create | Integration tests |
| `.ddev/docker-compose.clamav.yaml` | Create (optional) | DDEV development setup |
