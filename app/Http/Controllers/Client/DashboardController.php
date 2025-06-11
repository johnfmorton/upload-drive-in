<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the client dashboard.
     *
     * @param  Request  $request
     * @return View
     */
    public function index(Request $request): View
    {
        return view('client.dashboard', [
            'user' => $request->user(),
        ]);
    }
}
