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
use App\Services\AdminUserSearchOptimizationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\Mail\LoginVerificationMail;
use App\Enums\UserRole;
use App\Services\VerificationMailFactory;
// Remove Google Drive dependencies - they are now in the service
// use Google\Client;
// use Google\Service\Drive;
// use Google\Service\Drive\DriveFile;
use Exception;

class AdminUserController extends Controller
{
    protected ClientUserService $clientUserService;
    protected AdminUserSearchOptimizationService $searchOptimizationService;

    public function __construct(
        ClientUserService $clientUserService,
        AdminUserSearchOptimizationService $searchOptimizationService
    ) {
        $this->clientUserService = $clientUserService;
        $this->searchOptimizationService = $searchOptimizationService;
    }

    /**
     * Display a listing of the client users.
     */
    public function index(Request $request)
    {
        // Validate search and filter parameters
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'filter' => 'nullable|in:primary_contact'
        ]);

        // Use optimized search query builder
        $query = $this->searchOptimizationService->buildOptimizedSearchQuery($request);
        
        $clients = $query->paginate(config('file-manager.pagination.items_per_page'));
        
        // Append query parameters to pagination links
        $clients->appends($request->query());

        // Add the login URL and 2FA status to each client user
        $clients->getCollection()->transform(function ($client) {
            $client->login_url = $client->getLoginUrl();
            $client->two_factor_enabled = (bool) $client->two_factor_enabled;
            $client->two_factor_confirmed_at = $client->two_factor_confirmed_at;
            
            // Add primary contact status for current user
            $currentUser = Auth::user();
            $client->is_primary_contact_for_current_user = $currentUser->isPrimaryContactFor($client);
            
            return $client;
        });

        return view('admin.users.index', compact('clients'))
            ->with('searchTerm', $request->get('search', ''))
            ->with('currentFilter', $request->get('filter', ''));
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
        // Enhanced server-side validation with custom error messages
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255',
            'action' => 'required|in:create,create_and_invite',
        ], [
            'name.required' => __('messages.validation_name_required'),
            'name.string' => __('messages.validation_name_string'),
            'name.max' => __('messages.validation_name_max'),
            'email.required' => __('messages.validation_email_required'),
            'email.email' => __('messages.validation_email_format'),
            'action.required' => __('messages.validation_action_required'),
            'action.in' => __('messages.validation_action_invalid'),
        ]);

        // Log the user creation attempt for audit purposes
        Log::info('Admin user creation attempt', [
            'admin_id' => Auth::id(),
            'admin_email' => Auth::user()->email,
            'client_name' => $validated['name'],
            'client_email' => $validated['email'],
            'action' => $validated['action'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            $clientUser = $this->clientUserService->findOrCreateClientUser($validated, Auth::user());

            if ($validated['action'] === 'create_and_invite') {
                try {
                    $this->sendInvitationEmail($clientUser);
                    
                    // Log successful creation with invitation
                    Log::info('Admin user created with invitation sent', [
                        'admin_id' => Auth::id(),
                        'client_id' => $clientUser->id,
                        'client_email' => $clientUser->email,
                    ]);
                    
                    return redirect()->route('admin.users.index')
                        ->with('success', __('messages.admin_user_created_and_invited'));
                } catch (Exception $e) {
                    // Log email sending failure with detailed context
                    Log::error('Failed to send invitation email during admin user creation', [
                        'admin_id' => Auth::id(),
                        'client_id' => $clientUser->id,
                        'client_email' => $clientUser->email,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    return redirect()->route('admin.users.index')
                        ->with('warning', __('messages.admin_user_created_email_failed'));
                }
            } else {
                // Log successful creation without invitation
                Log::info('Admin user created without invitation', [
                    'admin_id' => Auth::id(),
                    'client_id' => $clientUser->id,
                    'client_email' => $clientUser->email,
                ]);
                
                return redirect()->route('admin.users.index')
                    ->with('success', __('messages.admin_user_created'));
            }
        } catch (Exception $e) {
            // Log user creation failure with detailed context
            Log::error('Failed to create client user via admin', [
                'admin_id' => Auth::id(),
                'client_name' => $validated['name'],
                'client_email' => $validated['email'],
                'action' => $validated['action'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip_address' => $request->ip(),
            ]);
            
            return redirect()->route('admin.users.index')
                ->withErrors(['general' => __('messages.admin_user_creation_failed')])
                ->withInput();
        }
    }

    /**
     * Send invitation email to a client user.
     *
     * @param User $clientUser
     * @return void
     * @throws Exception
     */
    private function sendInvitationEmail(User $clientUser): void
    {
        try {
            // Validate email address format before attempting to send
            if (!filter_var($clientUser->email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address format: {$clientUser->email}");
            }

            $loginUrl = URL::temporarySignedRoute(
                'login.via.token',
                now()->addDays(7),
                ['user' => $clientUser->id]
            );

            // Log email sending attempt
            Log::info('Attempting to send invitation email', [
                'client_id' => $clientUser->id,
                'client_email' => $clientUser->email,
                'admin_id' => Auth::id(),
            ]);

            // Use VerificationMailFactory to select appropriate template
            $mailFactory = app(VerificationMailFactory::class);
            $verificationMail = $mailFactory->createForUser($clientUser, $loginUrl);
            
            // Log template selection for debugging
            Log::info('Email verification template selected for admin user creation', [
                'client_id' => $clientUser->id,
                'client_email' => $clientUser->email,
                'client_role' => $clientUser->role,
                'mail_class' => get_class($verificationMail),
                'context' => 'admin_user_creation',
                'admin_id' => Auth::id(),
            ]);

            Mail::to($clientUser->email)->send($verificationMail);
            
            // Log successful email sending
            Log::info('Invitation email sent successfully', [
                'client_id' => $clientUser->id,
                'client_email' => $clientUser->email,
                'admin_id' => Auth::id(),
            ]);
            
        } catch (Exception $e) {
            // Enhanced error logging with more context
            Log::error('Failed to send invitation email', [
                'client_id' => $clientUser->id,
                'client_email' => $clientUser->email,
                'admin_id' => Auth::id(),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'mail_config' => [
                    'driver' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                ],
            ]);
            
            throw new Exception("Failed to send invitation email to {$clientUser->email}: " . $e->getMessage(), $e->getCode(), $e);
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
        
        // Get client's upload history - only files related to the current admin user
        $currentUser = Auth::user();
        $uploads = FileUpload::where('email', $client->email)
            ->where(function($query) use ($currentUser) {
                $query->where('company_user_id', $currentUser->id)
                      ->orWhere('uploaded_by_user_id', $currentUser->id);
            })
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
                $currentUser = Auth::user();
                $fileUploads = FileUpload::where('email', $clientEmail)
                    ->where(function($query) use ($currentUser) {
                        $query->where('company_user_id', $currentUser->id)
                              ->orWhere('uploaded_by_user_id', $currentUser->id);
                    })
                    ->get();
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
                    // Or just re-query - using email is simpler here, but filter by user access
                    $deletedDbRecords = FileUpload::where('email', $clientEmail)
                        ->where(function($query) use ($currentUser) {
                            $query->where('company_user_id', $currentUser->id)
                                  ->orWhere('uploaded_by_user_id', $currentUser->id);
                        })
                        ->delete();
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
        
        // Enhanced validation with custom error messages
        $validated = $request->validate([
            'team_members' => 'required|array|min:1',
            'team_members.*' => 'exists:users,id',
            'primary_contact' => 'required|exists:users,id|in:' . implode(',', $request->input('team_members', [])),
        ], [
            'team_members.required' => __('messages.validation_team_members_required'),
            'team_members.min' => __('messages.validation_team_members_min'),
            'team_members.*.exists' => __('messages.validation_team_member_invalid'),
            'primary_contact.required' => __('messages.validation_primary_contact_required'),
            'primary_contact.exists' => __('messages.validation_primary_contact_invalid'),
            'primary_contact.in' => __('messages.validation_primary_contact_not_in_team'),
        ]);

        // Log the team assignment update attempt for audit purposes
        Log::info('Team assignment update attempt', [
            'admin_id' => Auth::id(),
            'admin_email' => Auth::user()->email,
            'client_id' => $client->id,
            'client_email' => $client->email,
            'team_members' => $validated['team_members'],
            'primary_contact' => $validated['primary_contact'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            // Validate that all team members belong to the current admin's organization
            $currentUser = Auth::user();
            $validTeamMembers = User::whereIn('id', $validated['team_members'])
                ->where(function($query) use ($currentUser) {
                    $query->where('owner_id', $currentUser->id)
                          ->orWhere('id', $currentUser->id);
                })
                ->whereIn('role', [UserRole::ADMIN, UserRole::EMPLOYEE])
                ->pluck('id')
                ->toArray();
            
            if (count($validTeamMembers) !== count($validated['team_members'])) {
                Log::warning('Invalid team members detected in assignment', [
                    'admin_id' => Auth::id(),
                    'client_id' => $client->id,
                    'requested_members' => $validated['team_members'],
                    'valid_members' => $validTeamMembers,
                ]);
                
                return redirect()->route('admin.users.show', $client->id)
                    ->withErrors(['team_members' => __('messages.validation_team_members_unauthorized')])
                    ->withInput();
            }
            
            // Validate that primary contact is among valid team members
            if (!in_array($validated['primary_contact'], $validTeamMembers)) {
                Log::warning('Invalid primary contact detected in assignment', [
                    'admin_id' => Auth::id(),
                    'client_id' => $client->id,
                    'primary_contact' => $validated['primary_contact'],
                    'valid_members' => $validTeamMembers,
                ]);
                
                return redirect()->route('admin.users.show', $client->id)
                    ->withErrors(['primary_contact' => __('messages.validation_primary_contact_unauthorized')])
                    ->withInput();
            }
            
            // Remove existing relationships
            $client->companyUsers()->detach();
            
            // Add new relationships with proper primary contact assignment
            $teamData = [];
            foreach ($validated['team_members'] as $memberId) {
                $teamData[$memberId] = [
                    'is_primary' => $memberId == $validated['primary_contact']
                ];
            }
            $client->companyUsers()->attach($teamData);
            
            // Log successful team assignment update
            Log::info('Team assignment updated successfully', [
                'admin_id' => Auth::id(),
                'client_id' => $client->id,
                'client_email' => $client->email,
                'team_members_count' => count($validated['team_members']),
                'primary_contact' => $validated['primary_contact'],
            ]);
            
            return redirect()->route('admin.users.show', $client->id)
                ->with('success', __('messages.team_assignments_updated_success'));
                
        } catch (Exception $e) {
            // Enhanced error logging with more context
            Log::error('Failed to update team assignments', [
                'admin_id' => Auth::id(),
                'client_id' => $client->id,
                'client_email' => $client->email,
                'team_members' => $validated['team_members'] ?? null,
                'primary_contact' => $validated['primary_contact'] ?? null,
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'ip_address' => $request->ip(),
            ]);
            
            return redirect()->route('admin.users.show', $client->id)
                ->withErrors(['general' => __('messages.team_assignments_update_failed')])
                ->withInput();
        }
    }
}
