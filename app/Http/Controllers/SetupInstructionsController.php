<?php

namespace App\Http\Controllers;

use App\Services\SetupDetectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SetupInstructionsController extends Controller
{
    public function __construct(
        private SetupDetectionService $setupDetectionService
    ) {}

    /**
     * Display the setup instructions page or redirect if setup is complete.
     */
    public function show(): View|RedirectResponse
    {
        // If setup is complete, redirect to appropriate dashboard
        if ($this->setupDetectionService->isSetupComplete()) {
            // If user is authenticated, redirect to their appropriate dashboard
            if (auth()->check()) {
                $user = auth()->user();
                
                if ($user->isAdmin()) {
                    return redirect()->route('admin.dashboard');
                } elseif ($user->isEmployee()) {
                    return redirect()->route('employee.dashboard', ['username' => $user->username]);
                } elseif ($user->isClient()) {
                    return redirect()->route('client.dashboard');
                }
            }
            
            // If not authenticated, redirect to home page
            return redirect()->route('home');
        }

        return view('setup.instructions');
    }
}