<?php
// FILE: app/Http/Controllers/Admin/StaffPasswordResetController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\StaffPasswordResetMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class StaffPasswordResetController extends Controller
{
    const TOKEN_LIFETIME_MINUTES = 60;

    // =========================================================
    // GET /admin/forgot-password
    // =========================================================
    public function showRequestForm()
    {
        return view('admin.auth.forgot-password');
    }

    // =========================================================
    // POST /admin/forgot-password
    // Always responds the same way regardless of whether the email
    // matches a real staff account — doesn't reveal which emails are
    // valid staff logins to anyone probing the form.
    // =========================================================
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = strtolower(trim($request->email));
        $staff = DB::table('staff')->where('email', $email)->where('is_active', true)->first();

        if ($staff) {
            $token = Str::random(64);

            DB::table('staff')->where('id', $staff->id)->update([
                'password_reset_token'      => $token,
                'password_reset_expires_at' => now()->addMinutes(self::TOKEN_LIFETIME_MINUTES),
                'updated_at'                => now(),
            ]);

            Mail::to($staff->email)->send(new StaffPasswordResetMail($staff->name, $token));
        }

        return back()->with('success', "If {$email} matches an active staff account, a reset link has been sent — check your inbox.");
    }

    // =========================================================
    // GET /admin/reset-password/{token}
    // =========================================================
    public function showResetForm(string $token)
    {
        $staff = DB::table('staff')
            ->where('password_reset_token', $token)
            ->where('password_reset_expires_at', '>', now())
            ->first();

        if (!$staff) {
            return redirect()->route('admin.password.request')
                ->with('error', 'This reset link is invalid or has expired — request a new one below.');
        }

        return view('admin.auth.reset-password', ['token' => $token]);
    }

    // =========================================================
    // POST /admin/reset-password
    // =========================================================
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $staff = DB::table('staff')
            ->where('password_reset_token', $request->token)
            ->where('password_reset_expires_at', '>', now())
            ->first();

        if (!$staff) {
            return redirect()->route('admin.password.request')
                ->with('error', 'This reset link is invalid or has expired — request a new one below.');
        }

        DB::table('staff')->where('id', $staff->id)->update([
            'password'                  => Hash::make($request->password),
            // Token is single-use — cleared immediately so the same
            // link can't be replayed to reset the password again.
            'password_reset_token'      => null,
            'password_reset_expires_at' => null,
            'updated_at'                => now(),
        ]);

        return redirect()->route('admin.login')
            ->with('success', 'Your password has been reset — log in with your new password.');
    }
}
