<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class SetupInstructionsController extends Controller
{
    /**
     * Display the setup instructions page.
     */
    public function show(): View
    {
        return view('setup.instructions');
    }
}