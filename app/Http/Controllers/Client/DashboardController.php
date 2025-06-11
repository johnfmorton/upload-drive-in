<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the client dashboard.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return view('client.dashboard');
    }
}
