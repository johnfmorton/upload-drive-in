<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleDriveService;

class GoogleDriveFolderController extends Controller
{
    protected GoogleDriveService $driveService;
    protected string $defaultFolderId;

    public function __construct(GoogleDriveService $driveService)
    {
        $this->driveService = $driveService;
        $this->defaultFolderId = 'root';
    }

    /**
     * List folders under a given parent ID.
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $parentId = $request->input('parent_id', $this->defaultFolderId);
        $user = Auth::user();

        if (!$user->hasGoogleDriveConnected()) {
            return response()->json(['error' => 'Google Drive not connected'], 403);
        }

        // Cache folder listings for 5 minutes to reduce API calls
        $cacheKey = "google_drive_folders_{$user->id}_{$parentId}";
        
        try {
            $folders = cache()->remember($cacheKey, 300, function () use ($user, $parentId) {
                $service = $this->driveService->getDriveService($user);
                $query = sprintf("mimeType='application/vnd.google-apps.folder' and trashed=false and '%s' in parents", $parentId);
                $response = $service->files->listFiles([
                    'q' => $query,
                    'fields' => 'files(id,name)',
                ]);

                return array_map(fn($f) => ['id' => $f->id, 'name' => $f->name], $response->getFiles());
            });

            return response()->json(['folders' => $folders]);
        } catch (\Exception $e) {
            Log::error('Failed to list Google Drive folders', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to list folders'], 500);
        }
    }

    /**
     * Create a new folder under a given parent ID.
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => ['required', 'string'],
            'name' => ['required', 'string'],
        ]);

        $user = Auth::user();

        if (!$user->hasGoogleDriveConnected()) {
            return response()->json(['error' => 'Google Drive not connected'], 403);
        }

        try {
            $service = $this->driveService->getDriveService($user);
            $fileMetadata = new DriveFile([
                'name' => $validated['name'],
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$validated['parent_id']],
            ]);

            $folder = $service->files->create($fileMetadata, ['fields' => 'id,name']);

            // Invalidate the cache for the parent folder's listing
            $cacheKey = "google_drive_folders_{$user->id}_{$validated['parent_id']}";
            cache()->forget($cacheKey);

            return response()->json(['folder' => ['id' => $folder->id, 'name' => $folder->name]]);
        } catch (\Exception $e) {
            Log::error('Failed to create Google Drive folder', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create folder'], 500);
        }
    }

    /**
     * Retrieve metadata for a single folder by ID.
     */
    public function show(string $folderId): \Illuminate\Http\JsonResponse
    {
        $user = Auth::user();

        if (!$user->hasGoogleDriveConnected()) {
            return response()->json(['error' => 'Google Drive not connected'], 403);
        }

        // Cache folder metadata for 5 minutes to reduce API calls
        $cacheKey = "google_drive_folder_meta_{$user->id}_{$folderId}";
        
        try {
            $folder = cache()->remember($cacheKey, 300, function () use ($user, $folderId) {
                $service = $this->driveService->getDriveService($user);
                $file = $service->files->get($folderId, ['fields' => 'id,name']);
                return ['id' => $file->id, 'name' => $file->name];
            });
            
            return response()->json(['folder' => $folder]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch Google Drive folder', ['error' => $e->getMessage(), 'folderId' => $folderId]);
            return response()->json(['error' => 'Failed to fetch folder'], 500);
        }
    }
}
