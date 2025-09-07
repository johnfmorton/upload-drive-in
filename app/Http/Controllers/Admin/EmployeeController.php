<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Services\VerificationMailFactory;
use Exception;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the employee users for the current owner.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $employees = User::where('role', UserRole::EMPLOYEE)
            ->where('owner_id', Auth::id())
            ->paginate(config('file-manager.pagination.items_per_page'));

        // Ensure login and reset URLs are included in the JSON payload
        $employees->getCollection()->transform(function ($emp) {
            // Trigger the accessors and store as attributes
            $emp->login_url = $emp->login_url;
            $emp->reset_url = $emp->reset_url;
            return $emp;
        });

        return view('admin.employee-management.index', compact('employees'));
    }

    /**
     * Store a newly created employee user in storage.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // Validate with a named error bag for the create form
        $data = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'action' => ['required', 'in:create,create_and_invite'],
        ])->validateWithBag('createEmployee');

        // Log the employee creation attempt for audit purposes
        Log::info('Employee user creation attempt', [
            'admin_id' => Auth::id(),
            'admin_email' => Auth::user()->email,
            'employee_name' => $data['name'],
            'employee_email' => $data['email'],
            'action' => $data['action'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => Str::before($data['email'], '@'),
                'password' => Hash::make(Str::random(32)),
                'role' => UserRole::EMPLOYEE,
                'owner_id' => Auth::id(),
                // Don't auto-verify email - let verification email handle this
                'email_verified_at' => null,
            ]);

            if ($data['action'] === 'create_and_invite') {
                try {
                    $this->sendEmployeeVerificationEmail($user);
                    
                    // Log successful creation with invitation
                    Log::info('Employee user created with verification email sent', [
                        'admin_id' => Auth::id(),
                        'employee_id' => $user->id,
                        'employee_email' => $user->email,
                    ]);
                    
                    return redirect()->route('admin.employees.index')
                        ->with('success', __('messages.employee_created_and_invited_success'));
                } catch (Exception $e) {
                    // Log email sending failure with detailed context
                    Log::error('Failed to send verification email during employee creation', [
                        'admin_id' => Auth::id(),
                        'employee_id' => $user->id,
                        'employee_email' => $user->email,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    
                    return redirect()->route('admin.employees.index')
                        ->with('warning', __('messages.employee_created_email_failed'));
                }
            } else {
                // Log successful creation without invitation
                Log::info('Employee user created without verification email', [
                    'admin_id' => Auth::id(),
                    'employee_id' => $user->id,
                    'employee_email' => $user->email,
                ]);
                
                return redirect()->route('admin.employees.index')
                    ->with('success', __('messages.employee_created_success'));
            }

        } catch (\Exception $e) {
            // Log user creation failure with detailed context
            Log::error('Failed to create employee user', [
                'admin_id' => Auth::id(),
                'employee_name' => $data['name'],
                'employee_email' => $data['email'],
                'action' => $data['action'],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip_address' => $request->ip(),
            ]);
            
            return redirect()->route('admin.employees.index')
                ->with('error', __('messages.employee_creation_failed'));
        }
    }

    /**
     * Send verification email to an employee user.
     *
     * @param User $employeeUser
     * @return void
     * @throws Exception
     */
    private function sendEmployeeVerificationEmail(User $employeeUser): void
    {
        try {
            // Validate email address format before attempting to send
            if (!filter_var($employeeUser->email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address format: {$employeeUser->email}");
            }

            $verificationUrl = URL::temporarySignedRoute(
                'login.via.token',
                now()->addDays(7),
                ['user' => $employeeUser->id]
            );

            // Log email sending attempt
            Log::info('Attempting to send employee verification email', [
                'employee_id' => $employeeUser->id,
                'employee_email' => $employeeUser->email,
                'admin_id' => Auth::id(),
            ]);

            // Use VerificationMailFactory to select appropriate template
            $mailFactory = app(VerificationMailFactory::class);
            $verificationMail = $mailFactory->createForUser($employeeUser, $verificationUrl);
            
            // Log template selection for debugging
            Log::info('Email verification template selected for employee creation', [
                'employee_id' => $employeeUser->id,
                'employee_email' => $employeeUser->email,
                'employee_role' => $employeeUser->role,
                'mail_class' => get_class($verificationMail),
                'context' => 'employee_user_creation',
                'admin_id' => Auth::id(),
            ]);

            Mail::to($employeeUser->email)->send($verificationMail);
            
            // Log successful email sending
            Log::info('Employee verification email sent successfully', [
                'employee_id' => $employeeUser->id,
                'employee_email' => $employeeUser->email,
                'admin_id' => Auth::id(),
            ]);
            
        } catch (Exception $e) {
            // Enhanced error logging with more context
            Log::error('Failed to send employee verification email', [
                'employee_id' => $employeeUser->id,
                'employee_email' => $employeeUser->email,
                'admin_id' => Auth::id(),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'mail_config' => [
                    'driver' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                ],
            ]);
            
            throw new Exception("Failed to send verification email to {$employeeUser->email}: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
