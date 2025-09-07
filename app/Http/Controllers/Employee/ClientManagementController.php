<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ClientUserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use App\Mail\LoginVerificationMail;
use App\Services\VerificationMailFactory;
use Exception;

class ClientManagementController extends Controller
{
    /**
     * Display a listing of client users associated with this employee.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $employee = Auth::user();
        
        // Get all client users associated with this employee
        $query = $employee->clientUsers()->with('companyUsers');
        
        // Handle primary contact filtering
        if ($request->has('filter') && $request->get('filter') === 'primary_contact') {
            $query->wherePivot('is_primary', true);
        }
        
        $clientUsers = $query->paginate(config('file-manager.pagination.items_per_page'));

        // Add login URLs and primary contact status to each client user
        $clientUsers->getCollection()->transform(function ($client) use ($employee) {
            $client->login_url = $client->login_url;
            $client->reset_url = $client->reset_url;
            
            // Add primary contact status for current employee
            $client->is_primary_contact_for_current_user = $employee->isPrimaryContactFor($client);
            
            return $client;
        });

        return view('employee.client-management.index', compact('clientUsers'));
    }

    /**
     * Create a new client user and associate them with the current employee.
     *
     * @param Request $request
     * @param ClientUserService $clientUserService
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, ClientUserService $clientUserService)
    {
        // Enhanced server-side validation with custom error messages
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'action' => ['required', 'in:create,create_and_invite']
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
        Log::info('Employee client creation attempt', [
            'employee_id' => Auth::id(),
            'employee_email' => Auth::user()->email,
            'client_name' => $validated['name'],
            'client_email' => $validated['email'],
            'action' => $validated['action'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            // Create or find client user and associate with current employee
            $clientUser = $clientUserService->findOrCreateClientUser($validated, Auth::user());

            if ($validated['action'] === 'create_and_invite') {
                try {
                    $this->sendInvitationEmail($clientUser);
                    
                    // Log successful creation with invitation
                    Log::info('Employee client created with invitation sent', [
                        'employee_id' => Auth::id(),
                        'client_id' => $clientUser->id,
                        'client_email' => $clientUser->email,
                    ]);
                    
                    return back()->with('status', 'employee-client-created-and-invited');
                } catch (Exception $e) {
                    // Log email sending failure with detailed context
                    Log::error('Failed to send invitation email during employee client creation', [
                        'employee_id' => Auth::id(),
                        'client_id' => $clientUser->id,
                        'client_email' => $clientUser->email,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    return back()->with('status', 'employee-client-created-email-failed');
                }
            } else {
                // Log successful creation without invitation
                Log::info('Employee client created without invitation', [
                    'employee_id' => Auth::id(),
                    'client_id' => $clientUser->id,
                    'client_email' => $clientUser->email,
                ]);
                
                return back()->with('status', 'employee-client-created');
            }
        } catch (Exception $e) {
            // Log user creation failure with detailed context
            Log::error('Failed to create client user via employee', [
                'employee_id' => Auth::id(),
                'client_name' => $validated['name'],
                'client_email' => $validated['email'],
                'action' => $validated['action'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip_address' => $request->ip(),
            ]);
            
            return back()->withErrors(['general' => __('messages.employee_client_creation_failed')])->withInput();
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
                'employee_id' => Auth::id(),
            ]);

            // Use VerificationMailFactory to select appropriate template
            $mailFactory = app(VerificationMailFactory::class);
            $verificationMail = $mailFactory->createForUser($clientUser, $loginUrl);
            
            // Log template selection for debugging
            Log::info('Email verification template selected for employee user creation', [
                'client_id' => $clientUser->id,
                'client_email' => $clientUser->email,
                'client_role' => $clientUser->role,
                'mail_class' => get_class($verificationMail),
                'context' => 'employee_user_creation',
                'employee_id' => Auth::id(),
            ]);

            Mail::to($clientUser->email)->send($verificationMail);
            
            // Log successful email sending
            Log::info('Invitation email sent successfully', [
                'client_id' => $clientUser->id,
                'client_email' => $clientUser->email,
                'employee_id' => Auth::id(),
            ]);
            
        } catch (Exception $e) {
            // Enhanced error logging with more context
            Log::error('Failed to send invitation email', [
                'client_id' => $clientUser->id,
                'client_email' => $clientUser->email,
                'employee_id' => Auth::id(),
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
}