<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainAccessRule;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * SecuritySettingsController
 * 
 * Handles security and access control settings for the application.
 * This includes public registration settings and domain access control rules.
 */
class SecuritySettingsController extends Controller
{
    /**
     * Display the security and access settings page.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $settings = DomainAccessRule::first() ?? new DomainAccessRule([
            'mode' => 'blacklist',
            'rules' => [],
            'allow_public_registration' => true,
        ]);

        return view('admin.security.settings', compact('settings'));
    }

    /**
     * Update the public registration setting.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateRegistration(Request $request)
    {
        // Convert checkbox presence/absence to boolean
        $allow_public_registration = $request->has('allow_public_registration');

        $settings = DomainAccessRule::firstOrCreate();
        $settings->update([
            'allow_public_registration' => $allow_public_registration,
        ]);

        return back()->with('status', __('messages.registration_security_updated'));
    }

    /**
     * Update the domain access control rules.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateDomainRules(Request $request)
    {
        $validated = $request->validate([
            'access_control_mode' => ['required', 'string', 'in:blacklist,whitelist'],
            'domain_rules' => ['nullable', 'string'],
        ], [
            'access_control_mode.required' => __('messages.security_mode_required'),
            'access_control_mode.in' => __('messages.security_mode_required'),
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
                    'domain_rules' => [__('messages.invalid_domain_rule') . " ('{$rule}')"],
                ]);
            }
        }

        $settings = DomainAccessRule::firstOrCreate();
        $settings->update([
            'mode' => $validated['access_control_mode'],
            'rules' => $rules,
        ]);

        return back()->with('status', __('messages.access_control_rules_updated'));
    }
}