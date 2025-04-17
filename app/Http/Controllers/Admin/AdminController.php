<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

abstract class AdminController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        // Check authentication and admin status before any action
        $this->beforeAction();
    }

    /**
     * Check authentication and admin status before any action.
     */
    protected function beforeAction()
    {
        if (!Auth::check() || !Auth::user()->isAdmin()) {
            abort(404, 'Please visit the home page to start using the app.');
        }
    }
}
