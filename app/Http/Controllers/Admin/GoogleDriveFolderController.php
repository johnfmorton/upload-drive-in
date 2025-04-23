<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GoogleDriveFolderController extends Controller
{
    protected Client $client;
    protected Drive $service;
    protected string $defaultFolderId;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('cloud-storage.providers.google-drive.client_id'));
        $this->client->setClientSecret(config('cloud-storage.providers.google-drive.client_secret'));
        $this->client->setRedirectUri(config('cloud-storage.providers.google-drive.redirect_uri'));
        $this->client->addScope(Drive::DRIVE_METADATA_READONLY);
        $this->client->addScope(Drive::DRIVE);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        if (Storage::exists('google-credentials.json')) {
            $token = json_decode(Storage::get('google-credentials.json'), true);
            $this->client->setAccessToken($token);
        } else {
            throw new \Exception('Google Drive not connected.');
        }

        $this->service = new Drive($this->client);
        $this->defaultFolderId = 'root';
    }

    /**
     * List folders under a given parent ID.
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $parentId = $request->input('parent_id', $this->defaultFolderId);

        try {
            $query = sprintf("mimeType='application/vnd.google-apps.folder' and trashed=false and '%s' in parents", $parentId);
            $response = $this->service->files->listFiles([
                'q' => $query,
                'fields' => 'files(id,name)',
            ]);

            $folders = array_map(fn($f) => ['id' => $f->id, 'name' => $f->name], $response->getFiles());

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

        try {
            $fileMetadata = new DriveFile([
                'name' => $validated['name'],
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$validated['parent_id']],
            ]);

            $folder = $this->service->files->create($fileMetadata, ['fields' => 'id,name']);

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
        try {
            $folder = $this->service->files->get($folderId, ['fields' => 'id,name']);
            return response()->json(['folder' => ['id' => $folder->id, 'name' => $folder->name]]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch Google Drive folder', ['error' => $e->getMessage(), 'folderId' => $folderId]);
            return response()->json(['error' => 'Failed to fetch folder'], 500);
        }
    }
}
