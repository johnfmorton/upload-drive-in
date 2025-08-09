<?php

namespace App\Http\Controllers\Employee;

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

        try {
            $service = $this->driveService->getDriveService($user);
            $query = sprintf("mimeType='application/vnd.google-apps.folder' and trashed=false and '%s' in parents", $parentId);
            $response = $service->files->listFiles([
                'q' => $query,
                'fields' => 'files(id,name)',
            ]);

            $folders = array_map(fn($f) => ['id' => $f->id, 'name' => $f->name], $response->getFiles());

            return response()->json(['folders' => $folders]);
        } catch (\Exception $e) {
            Log::error('Failed to list Google Drive folders for employee', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);
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

            return response()->json(['folder' => ['id' => $folder->id, 'name' => $folder->name]]);
        } catch (\Exception $e) {
            Log::error('Failed to create Google Drive folder for employee', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);
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

        try {
            $service = $this->driveService->getDriveService($user);
            $folder = $service->files->get($folderId, ['fields' => 'id,name']);
            return response()->json(['folder' => ['id' => $folder->id, 'name' => $folder->name]]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch Google Drive folder for employee', [
                'error' => $e->getMessage(),
                'folderId' => $folderId,
                'user_id' => $user->id,
                'user_email' => $user->email
            ]);
            return response()->json(['error' => 'Failed to fetch folder'], 500);
        }
    }
}