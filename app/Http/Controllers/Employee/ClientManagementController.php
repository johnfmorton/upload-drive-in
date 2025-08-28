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
use Exception;

class ClientManagementController extends Controller
{
    /**
     * Display a listing of client users associated with this employee.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $employee = Auth::user();
        
        // Get all client users associated with this employee
        $clientUsers = $employee->clientUsers()
            ->with('companyUsers')
            ->paginate(config('file-manager.pagination.items_per_page'));

        // Add login URLs to each client user
        $clientUsers->getCollection()->transform(function ($client) {
            $client->login_url = $client->login_url;
            $client->reset_url = $client->reset_url;
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        try {
            // Create or find client user and associate with current employee
            $clientUser = $clientUserService->findOrCreateClientUser($validated, Auth::user());

            // Generate a signed URL for the user to log in
            $loginUrl = URL::temporarySignedRoute(
                'login.via.token',
                now()->addDays(7),
                ['user' => $clientUser->id]
            );

            // Send invitation email
            Mail::to($clientUser->email)->send(new LoginVerificationMail($loginUrl));

            return back()->with('status', 'client-created');
        } catch (Exception $e) {
            Log::error('Failed to create client user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'employee_id' => Auth::id()
            ]);
            return back()->withErrors(['email' => 'Failed to create client user.'])->withInput();
        }
    }
}