<?php
// FILE: app/Http/Controllers/Admin/SubscriberAdminController.php
//
// Staff view of the website email list, with a CSV download of everyone still subscribed.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SubscriberAdminController extends Controller
{
    private function allowed(): bool
    {
        return in_array(Session::get('staff_role'), ['admin', 'manager', 'supervisor'], true);
    }

    // GET /admin/subscribers
    public function index(Request $request)
    {
        abort_unless($this->allowed(), 403, 'Supervisor and above only.');

        $q = trim((string) $request->get('q', ''));
        $query = DB::table('email_subscribers')->orderByDesc('subscribed_at');
        if ($q !== '') $query->where('email', 'like', "%{$q}%");

        return view('admin.subscribers.index', [
            'subscribers' => $query->paginate(50)->withQueryString(),
            'active'      => DB::table('email_subscribers')->whereNull('unsubscribed_at')->count(),
            'total'       => DB::table('email_subscribers')->count(),
            'q'           => $q,
        ]);
    }

    // GET /admin/subscribers/export  — active subscribers only
    public function export()
    {
        abort_unless($this->allowed(), 403, 'Supervisor and above only.');

        $rows = DB::table('email_subscribers')->whereNull('unsubscribed_at')->orderBy('email')->get(['email', 'source', 'subscribed_at', 'token']);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['email', 'source', 'subscribed_at', 'unsubscribe_link']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->email, $r->source, $r->subscribed_at, url('/unsubscribe/' . $r->token)]);
            }
            fclose($out);
        }, 'auto-zenith-subscribers-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
