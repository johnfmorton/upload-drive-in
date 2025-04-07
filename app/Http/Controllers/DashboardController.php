<?php

namespace App\Http\Controllers;

use App\Models\FileUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        // Get all files, ordered by most recent
        $files = FileUpload::orderBy('created_at', 'desc')->get();

        return view('dashboard', compact('files'));
    }
}
