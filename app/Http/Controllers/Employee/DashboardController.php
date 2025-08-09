<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\FileUpload;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Show the employee dashboard.
     *
     * @param  Request  $request
     * @return View
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        
        // Get files uploaded to this employee (where company_user_id matches this employee)
        // or files uploaded directly by this employee
        $files = FileUpload::where(function($query) use ($user) {
            $query->where('company_user_id', $user->id)
                  ->orWhere('uploaded_by_user_id', $user->id);
        })
        ->orderBy('created_at', 'desc')
        ->paginate(10);

        return view('employee.dashboard', compact('user', 'files'));
    }
}
