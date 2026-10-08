<?php
// FILE: app/Http/Controllers/SubscribeController.php
//
// Public "Subscribe to our emails" box and the one-click unsubscribe link.

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscribeController extends Controller
{
    // POST /subscribe
    public function store(Request $request)
    {
        // Bots fill every field; a person never sees this one. Pretend it worked and drop it.
        if (filled($request->input('website'))) {
            return back()->with('subscribed', 'Thank you — you are subscribed.');
        }

        $data  = $request->validate(['email' => 'required|email:rfc|max:150']);
        $email = strtolower(trim($data['email']));
        $row   = DB::table('email_subscribers')->where('email', $email)->first();

        if ($row) {
            // Already on the list: if they had unsubscribed, this is a fresh opt-in.
            if ($row->unsubscribed_at) {
                DB::table('email_subscribers')->where('id', $row->id)->update([
                    'unsubscribed_at' => null,
                    'subscribed_at'   => now(),
                    'updated_at'      => now(),
                ]);
            }
        } else {
            DB::table('email_subscribers')->insert([
                'email'         => $email,
                'source'        => $request->headers->get('referer') && str_contains($request->headers->get('referer'), '/home') ? 'home' : 'site',
                'token'         => Str::random(40),
                'subscribed_at' => now(),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        return back()->with('subscribed', 'Thank you — you are subscribed. We will email you new arrivals and offers.');
    }

    // GET /unsubscribe/{token}
    public function unsubscribe(string $token)
    {
        $row = DB::table('email_subscribers')->where('token', $token)->first();
        if (!$row) abort(404);

        DB::table('email_subscribers')->where('id', $row->id)->update([
            'unsubscribed_at' => $row->unsubscribed_at ?: now(),
            'updated_at'      => now(),
        ]);

        return view('subscribe.unsubscribed', ['email' => $row->email]);
    }
}
