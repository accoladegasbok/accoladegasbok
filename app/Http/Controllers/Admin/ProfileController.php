<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('admin.profile.edit');
    }

    public function update(Request $request)
    {
        return back()->with('success', 'Profile updated successfully.');
    }

    public function destroy(Request $request)
    {
        return back()->with('success', 'Profile deleted successfully.');
    }
}