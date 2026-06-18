<?php
// FILE: app/Http/Controllers/Admin/AuthController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function loginForm()
    {
        if (Session::get('staff_id')) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $staff = DB::table('staff')
            ->where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if (!$staff || !Hash::check($request->password, $staff->password)) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        // Store in session
        Session::put('staff_id',       $staff->id);
        Session::put('staff_name',     $staff->name);
        Session::put('staff_role',     $staff->role);
        Session::put('staff_location', $staff->location);
        Session::put('staff_email',    $staff->email);

        // Update last login
        DB::table('staff')->where('id', $staff->id)
            ->update(['last_login_at' => now()]);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        Session::flush();
        return redirect()->route('admin.login');
    }
}
