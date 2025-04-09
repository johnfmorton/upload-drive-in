<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
// Add Google Drive dependencies
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile; // Though not strictly needed for deletion, good practice to include
use Exception; // Add Exception for broader catching

class AdminUserController extends Controller
{
    /**
     * Display a listing of the client users.
     */
    public function index()
    {
        $clients = User::where('role', 'client')->paginate(15); // Paginate client users
        return view('admin.users.index', compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Fetch the user (client) and return the edit view
        $client = User::where('role', 'client')->findOrFail($id);
        return view('admin.users.edit', compact('client'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $client = User::where('role', 'client')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$client->id,
            // Add other fields as needed, maybe a 'status' (active/inactive)?
        ]);

        $client->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'Client user updated successfully.');
    }

    // --- Google Drive Helper Methods (Consider extracting to a Service) ---

    /**
     * Sanitize email address to create a valid folder name.
     * Duplicated from UploadToGoogleDrive job - consider extracting to a Trait or Service.
     */
    protected function sanitizeEmailForFolder($email)
    {
        $sanitized = str_replace(['@', '.'], ['-at-', '-dot-'], $email);
        $sanitized = preg_replace('/[^a-zA-Z0-9\-]/', '-', $sanitized);
        $sanitized = preg_replace('/-+/', '-', $sanitized);
        return trim($sanitized, '-');
    }

    /**
     * Get the configured Google Drive Root Folder ID.
     * Duplicated from UploadToGoogleDrive job - consider extracting.
     */
    protected function getRootFolderId()
    {
        // Ensure the config value is set
        $rootFolderId = config('services.google_drive.root_folder_id');
        if (empty($rootFolderId)) {
            Log::error('Google Drive root folder ID is not configured in services.google_drive.root_folder_id');
            throw new Exception('Google Drive root folder ID is not configured.');
        }
        return $rootFolderId;
    }

    /**
     * Initializes and returns an authenticated Google Drive service client.
     * Logic adapted from UploadToGoogleDrive job - consider extracting to a Service.
     */
    protected function getGoogleDriveService()
    {
        $credentialsPath = Storage::path('google-credentials.json');
        if (!file_exists($credentialsPath)) {
            throw new Exception('Google Drive token (google-credentials.json) not found.');
        }

        $client = new Client();
        $client->setClientId(config('services.google_drive.client_id'));
        $client->setClientSecret(config('services.google_drive.client_secret'));
        $client->addScope(Drive::DRIVE); // DRIVE scope should be sufficient for folder deletion
        $client->setAccessType('offline');

        $token = json_decode(file_get_contents($credentialsPath), true);
        if (!$token) {
            throw new Exception('Invalid Google Drive token format.');
        }
        $client->setAccessToken($token);

        if ($client->isAccessTokenExpired()) {
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                // Persist the new token
                file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
                Log::info('Google Drive token refreshed.');
            } else {
                throw new Exception('Google Drive token expired, and no refresh token available. Please reconnect.');
            }
        }

        return new Drive($client);
    }

    // --- End Google Drive Helper Methods ---

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $clientUser = User::where('role', 'client')->findOrFail($id);
        $clientEmail = $clientUser->email;

        $localFilesDeletedCount = 0;
        $localFilesProcessedCount = 0;
        $googleDriveFolderDeleted = false;
        $deletionAttempted = $request->input('delete_files') == '1';
        $errors = []; // Collect errors

        if ($deletionAttempted) {
            Log::info("Admin triggered deletion for user {$id} ({$clientEmail}). Option 'delete_files' is checked.");

            // 1. Delete local files (from public/uploads)
            Log::info("Step 1: Attempting to delete local files for user {$id} ({$clientEmail}).");
            $fileUploads = FileUpload::where('email', $clientEmail)->get();
            $localFilesProcessedCount = $fileUploads->count();

            if ($localFilesProcessedCount > 0) {
                foreach ($fileUploads as $fileUpload) {
                    $filePath = 'uploads/' . $fileUpload->filename;
                    Log::debug("Attempting local delete: {$filePath}");
                    try {
                        if (Storage::disk('public')->exists($filePath)) {
                            if (Storage::disk('public')->delete($filePath)) {
                                Log::info("Successfully deleted local file: {$filePath}");
                                $localFilesDeletedCount++;
                            } else {
                                Log::warning("Storage::delete returned false for local file: {$filePath}");
                                $errors[] = "Failed to delete local file: {$fileUpload->original_filename}";
                            }
                        } else {
                            Log::info("Local file not found, skipping: {$filePath}");
                            // Consider if a missing local file should still allow DB record deletion
                        }
                    } catch (Exception $e) {
                        Log::error("Error deleting local file {$filePath}: " . $e->getMessage());
                        $errors[] = "Error deleting local file: {$fileUpload->original_filename}";
                    }
                }
            } else {
                Log::info("No local FileUpload records found for user {$id} ({$clientEmail}).");
            }

            // 2. Delete Google Drive Folder
            Log::info("Step 2: Attempting to delete Google Drive folder for user {$id} ({$clientEmail}).");
            try {
                $service = $this->getGoogleDriveService();
                $sanitizedEmail = $this->sanitizeEmailForFolder($clientEmail);
                $folderName = "User: {$sanitizedEmail}";
                $rootFolderId = $this->getRootFolderId();

                Log::info("Searching for Google Drive folder: '{$folderName}' within root '{$rootFolderId}'");

                $query = "name = '{$folderName}' and mimeType = 'application/vnd.google-apps.folder' and '{$rootFolderId}' in parents and trashed = false";
                $results = $service->files->listFiles([
                    'q' => $query,
                    'fields' => 'files(id, name)',
                    'pageSize' => 1 // Only need one if it exists
                ]);

                if (count($results->getFiles()) > 0) {
                    $folderId = $results->getFiles()[0]->getId();
                    Log::info("Found Google Drive folder '{$folderName}' with ID: {$folderId}. Attempting deletion.");
                    $service->files->delete($folderId);
                    $googleDriveFolderDeleted = true;
                    Log::info("Successfully deleted Google Drive folder ID: {$folderId}");
                } else {
                    Log::info("Google Drive folder '{$folderName}' not found for user {$id} ({$clientEmail}). Skipping deletion.");
                }
            } catch (Exception $e) {
                Log::error("Error during Google Drive folder deletion for user {$id} ({$clientEmail}): " . $e->getMessage());
                // Log stack trace for detailed debugging if needed
                // Log::error($e->getTraceAsString());
                $errors[] = "Failed to delete Google Drive folder: " . $e->getMessage();
            }

            // 3. Delete FileUpload Database Records (regardless of file deletion success? TBC)
            // Consider if this should only happen if local AND GDrive deletions were successful
            // For now, we delete them if the intent was to delete files.
            if ($localFilesProcessedCount > 0) {
                Log::info("Step 3: Deleting FileUpload database records for user {$id} ({$clientEmail}).");
                try {
                    $deletedDbRecords = FileUpload::where('email', $clientEmail)->delete();
                    Log::info("Deleted {$deletedDbRecords} FileUpload database records.");
                } catch (Exception $e) {
                    Log::error("Error deleting FileUpload database records for user {$id} ({$clientEmail}): " . $e->getMessage());
                    $errors[] = "Failed to delete file records from database.";
                }
            }
        }

        // 4. Delete the User Record
        Log::info("Step 4: Deleting user record for {$id} ({$clientEmail}).");
        try {
            $clientUser->delete();
        } catch (Exception $e) {
            Log::error("Failed to delete user record for {$id} ({$clientEmail}): " . $e->getMessage());
            // Critical failure - redirect back with error? Or let it continue to success page?
            // For now, redirecting with a general error might be best if user deletion fails.
            return redirect()->route('admin.users.index')->with('error', 'Failed to delete the user record. Please check logs.');
        }

        // 5. Prepare Feedback Message
        $message = 'Client user deleted successfully.';
        if ($deletionAttempted) {
            $fileSummary = [];
            if ($localFilesProcessedCount > 0) {
                $fileSummary[] = "{$localFilesDeletedCount}/{$localFilesProcessedCount} local files removed";
            }
            if ($googleDriveFolderDeleted) {
                $fileSummary[] = "Google Drive folder removed";
            } elseif (isset($folderName)) { // Only mention GDrive if we attempted it
                $fileSummary[] = "Google Drive folder '{$folderName}' not found or could not be removed";
            }

            if (!empty($fileSummary)) {
                 $message .= ' (' . implode(', ', $fileSummary) . ').';
            }
            if (!empty($errors)) {
                $message .= ' Some errors occurred (check logs).';
                // Optionally flash errors to session for more detailed display
                // session()->flash('deletion_errors', $errors);
            }
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }
}
