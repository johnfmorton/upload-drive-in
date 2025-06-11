<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the employee dashboard.
     *
     * @param  Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return view('employee.dashboard');
    }
}
