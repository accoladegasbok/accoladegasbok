<?php
// FILE: app/Http/Controllers/CustomerAuthController.php

namespace App\Http\Controllers;

use App\Services\CustomerOtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class CustomerAuthController extends Controller
{
    public function __construct(private CustomerOtpService $otp) {}

    // =========================================================
    // GET /account/register
    // =========================================================
    public function showRegister()
    {
        return view('customer.register');
    }

    // =========================================================
    // POST /account/register
    // Creates an unverified account, sends an email OTP, and hands
    // off to the verify screen. Account is NOT usable (can't log in)
    // until email_verified_at is set.
    // =========================================================
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|max:150|unique:customers,email',
            'phone'    => 'required|string|max:30|unique:customers,phone',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'name'                  => $request->name,
            'email'                 => $request->email,
            'phone'                 => $request->phone,
            'password'              => Hash::make($request->password),
            'preferred_otp_channel' => 'email',
            'created_at'            => now(),
            'updated_at'            => now(),
        ]);

        $this->otp->send($request->email, 'email', 'register', $customerId);

        Session::put('pending_verify_customer_id', $customerId);
        Session::put('pending_verify_identifier', $request->email);
        Session::put('pending_verify_purpose', 'register');

        return redirect()->route('customer.verify')
            ->with('success', "We've sent a 6-digit code to {$request->email}.");
    }

    // =========================================================
    // GET /account/verify
    // =========================================================
    public function showVerify()
    {
        $identifier = Session::get('pending_verify_identifier');
        if (!$identifier) {
            return redirect()->route('customer.login')->with('error', 'Nothing to verify — please log in or register.');
        }
        return view('customer.verify', ['identifier' => $identifier]);
    }

    // =========================================================
    // POST /account/verify
    // =========================================================
    public function verifyOtp(Request $request)
    {
        $request->validate(['code' => 'required|string|size:6']);

        $identifier = Session::get('pending_verify_identifier');
        $purpose    = Session::get('pending_verify_purpose');
        $customerId = Session::get('pending_verify_customer_id');

        if (!$identifier || !$purpose) {
            return redirect()->route('customer.login')->with('error', 'Verification session expired — please try again.');
        }

        if (!$this->otp->verify($identifier, $purpose, $request->code)) {
            return back()->with('error', 'That code is incorrect or has expired. You can request a new one below.');
        }

        // Apply whatever this verification was FOR
        if ($purpose === 'register' && $customerId) {
            DB::table('customers')->where('id', $customerId)->update(['email_verified_at' => now(), 'updated_at' => now()]);
            Session::forget(['pending_verify_customer_id', 'pending_verify_identifier', 'pending_verify_purpose']);
            $this->logCustomerIn($customerId);
            return redirect()->route('customer.account')->with('success', 'Account verified — welcome to Auto Zenith Parts!');
        }

        if ($purpose === 'change_email' && $customerId) {
            DB::table('customers')->where('id', $customerId)->update(['email' => $identifier, 'email_verified_at' => now(), 'updated_at' => now()]);
            Session::forget(['pending_verify_customer_id', 'pending_verify_identifier', 'pending_verify_purpose']);
            return redirect()->route('customer.account')->with('success', 'Email updated and verified.');
        }

        if ($purpose === 'change_phone' && $customerId) {
            DB::table('customers')->where('id', $customerId)->update(['phone' => $identifier, 'phone_verified_at' => now(), 'updated_at' => now()]);
            Session::forget(['pending_verify_customer_id', 'pending_verify_identifier', 'pending_verify_purpose']);
            return redirect()->route('customer.account')->with('success', 'Phone number updated and verified.');
        }

        return redirect()->route('customer.login');
    }

    // =========================================================
    // POST /account/verify/resend
    // =========================================================
    public function resendOtp()
    {
        $identifier = Session::get('pending_verify_identifier');
        $purpose    = Session::get('pending_verify_purpose');
        $customerId = Session::get('pending_verify_customer_id');

        if (!$identifier || !$purpose) {
            return back()->with('error', 'Nothing to resend — please start again.');
        }

        try {
            $this->otp->send($identifier, 'email', $purpose, $customerId);
            return back()->with('success', "New code sent to {$identifier}.");
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // =========================================================
    // GET /account/login
    // =========================================================
    public function showLogin()
    {
        return view('customer.login');
    }

    // =========================================================
    // POST /account/login
    // Email or phone + password. Standard login — OTP is only used
    // for registration and changing contact details, not every login
    // (matches how eBay/Amazon-style sites actually work).
    // =========================================================
    public function login(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'password'   => 'required|string',
        ]);

        $customer = DB::table('customers')
            ->where('email', $request->identifier)
            ->orWhere('phone', $request->identifier)
            ->first();

        if (!$customer || !Hash::check($request->password, $customer->password)) {
            return back()->withInput()->with('error', 'Incorrect email/phone or password.');
        }

        if (!$customer->email_verified_at) {
            $this->otp->send($customer->email, 'email', 'register', $customer->id);
            Session::put('pending_verify_customer_id', $customer->id);
            Session::put('pending_verify_identifier', $customer->email);
            Session::put('pending_verify_purpose', 'register');
            return redirect()->route('customer.verify')->with('error', 'Please verify your email first — we just sent you a new code.');
        }

        $this->logCustomerIn($customer->id);
        return redirect()->route('customer.account')->with('success', 'Welcome back!');
    }

    public function logout()
    {
        Session::forget(['customer_id', 'customer_name']);
        return redirect()->route('home');
    }

    private function logCustomerIn(int $customerId): void
    {
        $customer = DB::table('customers')->where('id', $customerId)->first();
        Session::put('customer_id', $customer->id);
        Session::put('customer_name', $customer->name);
    }

    // =========================================================
    // GET /account — self-service dashboard: profile + order history
    // =========================================================
    public function account()
    {
        $customer = DB::table('customers')->where('id', Session::get('customer_id'))->first();
        abort_if(!$customer, 404);

        // Match existing purchase history by phone — same grouping
        // logic CustomerController::index() already uses for the
        // admin-side "phone book" view, just scoped to this one
        // customer instead of everyone.
        $normalizedPhone = preg_replace('/\D/', '', $customer->phone);

        $orders = DB::table('orders')
            ->whereRaw("REPLACE(REPLACE(REPLACE(customer_phone,'+',''),' ',''),'-','') = ?", [$normalizedPhone])
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();

        $invoices = DB::table('invoices')
            ->whereRaw("REPLACE(REPLACE(REPLACE(customer_phone,'+',''),' ',''),'-','') = ?", [$normalizedPhone])
            ->whereNull('deleted_at')
            ->orderByDesc('created_at')
            ->get();

        return view('customer.account', compact('customer', 'orders', 'invoices'));
    }

    // =========================================================
    // POST /account/change-email — starts re-verification for a NEW
    // email. Doesn't update the record until the OTP is confirmed.
    // =========================================================
    public function requestEmailChange(Request $request)
    {
        $request->validate(['email' => 'required|email|max:150|unique:customers,email']);

        $customerId = Session::get('customer_id');
        $this->otp->send($request->email, 'email', 'change_email', $customerId);

        Session::put('pending_verify_customer_id', $customerId);
        Session::put('pending_verify_identifier', $request->email);
        Session::put('pending_verify_purpose', 'change_email');

        return redirect()->route('customer.verify')->with('success', "Verification code sent to {$request->email}.");
    }

    // =========================================================
    // POST /account/change-phone — same pattern, for phone. NOTE:
    // verification code still goes by EMAIL even when changing the
    // phone number, since SMS isn't wired up yet — the customer just
    // confirms the new phone number's OTP via their verified email.
    // =========================================================
    public function requestPhoneChange(Request $request)
    {
        $request->validate(['phone' => 'required|string|max:30|unique:customers,phone']);

        $customerId = Session::get('customer_id');
        $customer   = DB::table('customers')->where('id', $customerId)->first();

        // Code verifies the NEW phone (identifier) but is delivered to
        // the customer's already-verified email (deliverTo), since
        // SMS/WhatsApp delivery isn't available yet.
        $this->otp->send($request->phone, 'email', 'change_phone', $customerId, deliverTo: $customer->email);

        Session::put('pending_verify_customer_id', $customerId);
        Session::put('pending_verify_identifier', $request->phone);
        Session::put('pending_verify_purpose', 'change_phone');

        return redirect()->route('customer.verify')->with('success', "Verification code sent to your email on file — confirm to update your phone number.");
    }

    // =========================================================
    // GET /telegram/link — shows the deep link to start the bot
    // =========================================================
    public function telegramLinkStart()
    {
        $customerId = Session::get('customer_id');
        $botUsername = config('services.telegram.bot_username');

        if (!$botUsername) {
            return back()->with('error', 'Telegram is not set up yet.');
        }

        $token = Str::random(24);
        Session::put('telegram_link_token', $token);
        DB::table('customer_otps')->insert([
            'customer_id' => $customerId,
            'identifier'  => $token,
            'channel'     => 'telegram',
            'purpose'     => 'telegram_link',
            'code'        => '000000', // unused for this purpose — token IS the credential
            'expires_at'  => now()->addMinutes(30),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return view('customer.telegram-link', [
            'deepLink' => "https://t.me/{$botUsername}?start={$token}",
        ]);
    }

    // =========================================================
    // POST /telegram/webhook — called BY Telegram's servers when the
    // customer sends /start <token> to the bot. No customer session
    // available here (Telegram calls this directly) — the token in
    // the message is what ties it back to a customer_id.
    // =========================================================
    public function telegramWebhook(Request $request)
    {
        $text = data_get($request->all(), 'message.text', '');
        $chatId = data_get($request->all(), 'message.chat.id');

        if (!$chatId || !str_starts_with($text, '/start ')) {
            return response()->json(['ok' => true]); // ignore anything else silently
        }

        $token = trim(substr($text, 7));

        $link = DB::table('customer_otps')
            ->where('identifier', $token)
            ->where('purpose', 'telegram_link')
            ->where('expires_at', '>', now())
            ->whereNull('consumed_at')
            ->first();

        if (!$link) {
            return response()->json(['ok' => true]);
        }

        DB::table('customers')->where('id', $link->customer_id)->update(['telegram_chat_id' => $chatId, 'updated_at' => now()]);
        DB::table('customer_otps')->where('id', $link->id)->update(['consumed_at' => now()]);

        // Best-effort confirmation back to the customer via Telegram itself
        $botToken = config('services.telegram.bot_token');
        if ($botToken) {
            \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text'    => 'Your Telegram is now linked to your Auto Zenith Parts account.',
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
