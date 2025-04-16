<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainAccessRule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Mail\LoginVerificationMail;

class UserManagementController extends Controller
{
    public function settings()
    {
        $settings = DomainAccessRule::first() ?? new DomainAccessRule([
            'mode' => 'blacklist',
            'rules' => [],
            'allow_public_registration' => true,
        ]);

        return view('admin.user-management.settings', compact('settings'));
    }

    public function updateRegistration(Request $request)
    {
        // Convert checkbox presence/absence to boolean
        $allow_public_registration = $request->has('allow_public_registration');

        $settings = DomainAccessRule::firstOrCreate();
        $settings->update([
            'allow_public_registration' => $allow_public_registration,
        ]);

        return back()->with('status', 'settings-updated');
    }

    public function updateDomainRules(Request $request)
    {
        $validated = $request->validate([
            'access_control_mode' => ['required', 'string', 'in:blacklist,whitelist'],
            'domain_rules' => ['nullable', 'string'],
        ]);

        // Convert textarea input to array of rules
        $rules = array_filter(
            array_map('trim', explode("\n", $validated['domain_rules'] ?? '')),
            fn($rule) => !empty($rule)
        );

        // Validate each rule
        foreach ($rules as $rule) {
            if (!preg_match('/^(?:\*\.)?[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?)*$|^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/i', $rule)) {
                throw ValidationException::withMessages([
                    'domain_rules' => ["The rule '{$rule}' is not a valid domain pattern or email address."],
                ]);
            }
        }

        $settings = DomainAccessRule::firstOrCreate();
        $settings->update([
            'mode' => $validated['access_control_mode'],
            'rules' => $rules,
        ]);

        return back()->with('status', 'settings-updated');
    }

    public function createClient(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        ]);

        // Create user with random password (they'll use email validation to access)
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(32)),
            'role' => 'client'
        ]);

        // Generate a signed URL for the user to log in
        $loginUrl = URL::temporarySignedRoute(
            'login.via.token',
            now()->addDays(7),
            ['user' => $user->id]
        );

        // Send invitation email
        Mail::to($user->email)->send(new LoginVerificationMail($loginUrl));

        return back()->with('status', 'client-created');
    }
}
