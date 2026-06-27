<?php
// FILE: app/Http/Controllers/Admin/DeviceTokenController.php
//
// The native staff app calls this once on login/app-open to register
// (or refresh) its push notification token. No UI for this — it's
// purely an API endpoint called by native-bridge.js inside the app.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DeviceTokenController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'token'    => 'required|string',
            'platform' => 'required|in:ios,android',
        ]);

        $staffId = Session::get('staff_id');
        if (!$staffId) {
            return response()->json(['error' => 'Not logged in.'], 401);
        }

        DB::table('device_tokens')->updateOrInsert(
            ['token' => $request->token],
            [
                'staff_id'     => $staffId,
                'platform'     => $request->platform,
                'last_seen_at' => now(),
                'updated_at'   => now(),
                'created_at'   => now(),
            ]
        );

        return response()->json(['success' => true]);
    }

    // Called when a staff member logs out, so they stop receiving
    // notifications meant for whoever's logged in next on that device.
    public function unregister(Request $request)
    {
        $request->validate(['token' => 'required|string']);
        DB::table('device_tokens')->where('token', $request->token)->delete();
        return response()->json(['success' => true]);
    }
}
