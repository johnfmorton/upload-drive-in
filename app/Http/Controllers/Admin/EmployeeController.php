<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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
        ])->validateWithBag('createEmployee');

        try {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => Str::before($data['email'], '@'),
                'password' => Hash::make(Str::random(32)),
                'role' => UserRole::EMPLOYEE,
                'owner_id' => Auth::id(),
                'email_verified_at' => now(),
            ]);

            return redirect()->route('admin.employees.index')
                ->with('success', __('messages.employee_management_title') . ' created successfully.');

        } catch (\Exception $e) {
            Log::error('Error creating employee user: ' . $e->getMessage());
            return redirect()->route('admin.employees.index')
                ->with('error', 'Failed to create employee user. Please check the logs.');
        }
    }
}
