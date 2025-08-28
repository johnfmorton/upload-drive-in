<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\FileUpload;
use App\Services\GoogleDriveService; // Import the new service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str; // Added for random password
use Illuminate\Support\Facades\Hash; // Added for hashing password
use App\Services\ClientUserService;
use Illuminate\Support\Facades\Auth;
// Remove Google Drive dependencies - they are now in the service
// use Google\Client;
// use Google\Service\Drive;
// use Google\Service\Drive\DriveFile;
use Exception;

class AdminUserController extends Controller
{
    protected ClientUserService $clientUserService;

    public function __construct(ClientUserService $clientUserService)
    {
        $this->clientUserService = $clientUserService;
    }

    /**
     * Display a listing of the client users.
     */
    public function index()
    {
        $clients = User::where('role', 'client')->paginate(config('file-manager.pagination.items_per_page'));

        // Add the login URL and 2FA status to each client user
        $clients->getCollection()->transform(function ($client) {
            $client->login_url = $client->getLoginUrl();
            $client->two_factor_enabled = (bool) $client->two_factor_enabled;
            $client->two_factor_confirmed_at = $client->two_factor_confirmed_at;
            return $client;
        });

        return view('admin.users.index', compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Generally not needed if creation form is on the index page
    }

    /**
     * Store a newly created client user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255',
        ]);

        try {
            $clientUser = $this->clientUserService->findOrCreateClientUser($validated, Auth::user());

            return redirect()->route('admin.users.index')
                ->with('success', 'Client user created successfully. You can now provide them with their login link.');
        } catch (Exception $e) {
            Log::error("Error creating client user: " . $e->getMessage());
            return redirect()->route('admin.users.index')
                ->with('error', 'Failed to create client user. Please check the logs.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        // Ensure the user is a client
        if ($user->role->value !== 'client') {
            abort(404);
        }
        
        $client = $user->load(['companyUsers', 'clientUserRelationships.companyUser']);
        
        // Get all employees and admins for assignment
        $availableTeamMembers = User::whereIn('role', ['admin', 'employee'])
            ->where('owner_id', Auth::id())
            ->orWhere('id', Auth::id())
            ->get();
        
        // Get client's upload history
        $uploads = FileUpload::where('email', $client->email)
            ->orderBy('created_at', 'desc')
            ->paginate(config('file-manager.pagination.items_per_page'));
        
        return view('admin.users.show', compact('client', 'availableTeamMembers', 'uploads'));
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

    /**
     * Remove the specified resource from storage (User, local files, Google Drive folder).
     *
     * @param Request $request The incoming request.
     * @param string $id The ID of the User to delete.
     * @param GoogleDriveService $driveService Service injected for Google Drive operations.
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, string $id, GoogleDriveService $driveService)
    {
        try {
            $clientUser = User::where('role', 'client')->findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Attempted to delete non-existent user with ID: {$id}");
            return redirect()->route('admin.users.index')->with('error', 'User not found.');
        }

        $clientEmail = $clientUser->email;
        $localFilesDeletedCount = 0;
        $localFilesProcessedCount = 0;
        $googleDriveFolderDeleted = false;
        $googleDriveFolderChecked = false; // Track if we attempted GDrive deletion
        $deletionAttempted = $request->input('delete_files') == '1';
        $errors = []; // Collect errors
        $folderNameForMessage = null; // Store folder name for feedback

        if ($deletionAttempted) {
            Log::info("Admin triggered deletion for user {$id} ({$clientEmail}). Option 'delete_files' is checked.");

            // 1. Delete local files (from public/uploads)
            Log::info("Step 1: Attempting to delete local files for user {$id} ({$clientEmail}).");
            try {
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
                            }
                        } catch (Exception $e) {
                            Log::error("Error deleting local file {$filePath}: " . $e->getMessage());
                            $errors[] = "Error deleting local file: {$fileUpload->original_filename}";
                        }
                    }
                } else {
                    Log::info("No local FileUpload records found for user {$id} ({$clientEmail}).");
                }
            } catch(Exception $e) {
                 Log::error("Error retrieving FileUpload records for user {$id} ({$clientEmail}): " . $e->getMessage());
                 $errors[] = "Could not retrieve file records from database.";
            }

            // 2. Delete Google Drive Folder using the service
            Log::info("Step 2: Attempting to delete Google Drive folder for user {$id} ({$clientEmail}).");
            $googleDriveFolderChecked = true; // Mark that we are attempting this step
            try {
                // Get the potential folder name for feedback messages
                $sanitizedEmail = $driveService->sanitizeEmailForFolderName($clientEmail);
                $folderNameForMessage = "User: {$sanitizedEmail}";

                // Find the user's folder ID
                $folderId = $driveService->findUserFolderId($clientEmail);

                if ($folderId) {
                    Log::info("Found Google Drive folder for user {$id} with ID: {$folderId}. Attempting deletion.");
                    // Attempt to delete the folder
                    if ($driveService->deleteFolder($folderId)) {
                        $googleDriveFolderDeleted = true;
                        Log::info("Successfully initiated deletion of Google Drive folder ID: {$folderId}");
                    } else {
                        // deleteFolder method logs the error internally
                        $errors[] = "Failed to delete Google Drive folder '{$folderNameForMessage}'.";
                    }
                } else {
                    Log::info("Google Drive folder '{$folderNameForMessage}' not found for user {$id}. Skipping deletion.");
                }
            } catch (Exception $e) {
                // Errors during service initialization (e.g., config, token) or API calls are caught here
                Log::error("Error during Google Drive folder check/deletion for user {$id} ({$clientEmail}): " . $e->getMessage());
                $errors[] = "Error communicating with Google Drive: " . $e->getMessage();
            }

            // 3. Delete FileUpload Database Records
            // Only attempt if we successfully retrieved them in step 1
            if ($localFilesProcessedCount > 0 || !empty($fileUploads)) { // Check if $fileUploads might be populated even if count is 0
                 Log::info("Step 3: Deleting FileUpload database records for user {$id} ({$clientEmail}).");
                try {
                    // Use the IDs from the collection fetched earlier for efficiency if possible
                    // Or just re-query - using email is simpler here.
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
            Log::info("Successfully deleted user record for {$id} ({$clientEmail}).");
        } catch (Exception $e) {
            Log::error("CRITICAL: Failed to delete user record for {$id} ({$clientEmail}): " . $e->getMessage());
            // Add the user deletion error to the errors array
            $errors[] = "Failed to delete the main user record!";
            // Prepare message and redirect immediately, skipping normal success message construction
            $errorMessage = 'Client user files/folders might have been processed, but the user record itself could not be deleted. Please check logs.';
            if (!empty($errors)) {
                 $errorMessage .= ' Errors: ' . implode('; ', $errors);
            }
            return redirect()->route('admin.users.index')->with('error', $errorMessage);
        }

        // 5. Prepare Feedback Message
        $message = 'Client user deleted successfully.';
        if ($deletionAttempted) {
            $fileSummary = [];
            // Report on local files only if we processed any
            if ($localFilesProcessedCount > 0) {
                $fileSummary[] = "{$localFilesDeletedCount}/{$localFilesProcessedCount} local files removed";
            }
            // Report on Google Drive status only if we checked it
            if ($googleDriveFolderChecked) {
                 if ($googleDriveFolderDeleted) {
                    $fileSummary[] = "Google Drive folder removed";
                } else {
                    // Use the folder name retrieved earlier for a clearer message
                    $folderNameToReport = $folderNameForMessage ?? 'user\'s Google Drive folder';
                    $fileSummary[] = "{$folderNameToReport} not found or could not be removed";
                }
            }

            if (!empty($fileSummary)) {
                 $message .= ' (' . implode(', ', $fileSummary) . ').';
            }
        }
        // Append general error notice if any non-critical errors occurred
        if (!empty($errors)) {
             $message .= ' Some errors occurred during file/folder cleanup (check logs).';
            // Optionally flash detailed errors if needed for display in the view
            // session()->flash('deletion_errors', $errors);
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    /**
     * Update team assignments for a client user.
     */
    public function updateTeamAssignments(Request $request, User $user)
    {
        // Ensure the user is a client
        if ($user->role->value !== 'client') {
            abort(404);
        }
        
        $client = $user;
        
        $validated = $request->validate([
            'team_members' => 'array',
            'team_members.*' => 'exists:users,id',
            'primary_contact' => 'nullable|exists:users,id',
        ]);

        try {
            // Remove existing relationships
            $client->companyUsers()->detach();
            
            // Add new relationships
            if (!empty($validated['team_members'])) {
                $teamData = [];
                foreach ($validated['team_members'] as $memberId) {
                    $teamData[$memberId] = [
                        'is_primary' => $memberId == $validated['primary_contact']
                    ];
                }
                $client->companyUsers()->attach($teamData);
            }
            
            return redirect()->route('admin.users.show', $client->id)
                ->with('success', 'Team assignments updated successfully.');
                
        } catch (Exception $e) {
            Log::error("Error updating team assignments for client {$id}: " . $e->getMessage());
            return redirect()->route('admin.users.show', $client->id)
                ->with('error', 'Failed to update team assignments.');
        }
    }
}
