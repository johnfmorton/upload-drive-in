<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /**
     * Display a listing of the client users.
     */
    public function index()
    {
        $clients = User::where('role', 'client')->paginate(15); // Paginate client users
        return view('admin.users.index', compact('clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Fetch the user (client) and return the edit view
        $client = User::where('role', 'client')->findOrFail($id);
        return view('admin.users.edit', compact('client'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $client = User::where('role', 'client')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$client->id,
            // Add other fields as needed, maybe a 'status' (active/inactive)?
        ]);

        $client->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'Client user updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $client = User::where('role', 'client')->findOrFail($id);
        // Add logic here: should we delete related files? Or just the user?
        // For now, just delete the user.
        $client->delete();

        return redirect()->route('admin.users.index')->with('success', 'Client user deleted successfully.');
    }
}
