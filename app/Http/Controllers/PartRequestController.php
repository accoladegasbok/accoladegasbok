<?php
// FILE: app/Http/Controllers/PartRequestController.php
//
// PUBLIC "Request this part" form. A customer who can't find a part (or looked at one that is sold)
// leaves their details; the request lands in the staff inbox (/admin/part-requests) and an email
// goes to info@autozenithparts.com. Nothing here shows or changes stock.

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PartRequestController extends Controller
{
    public function store(Request $request)
    {
        // Bots fill every field; a real person never sees this one. Pretend it worked and drop it.
        if (filled($request->input('website'))) {
            return back()->with('part_request_sent', 'Thank you — we have your request.');
        }

        $data = $request->validateWithBag('partRequest', [
            'customer_name'  => 'required|string|max:120',
            'customer_phone' => 'required|string|max:40',
            'customer_email' => 'nullable|email|max:150',
            'vehicle_text'   => 'nullable|string|max:255',
            'part_text'      => 'required|string|max:255',
            'notes'          => 'nullable|string|max:1000',
            'region'         => 'nullable|string|max:40',
            'source'         => 'nullable|in:search,part_page',
            'part_id'        => 'nullable|integer',
        ]);

        $id = DB::table('part_requests')->insertGetId([
            'customer_name'  => trim($data['customer_name']),
            'customer_phone' => trim($data['customer_phone']),
            'customer_email' => $data['customer_email'] ?? null,
            'vehicle_text'   => $data['vehicle_text'] ?? null,
            'part_text'      => trim($data['part_text']),
            'notes'          => $data['notes'] ?? null,
            'region'         => $data['region'] ?? null,
            'source'         => $data['source'] ?? 'search',
            'part_id'        => !empty($data['part_id']) && DB::table('parts_inventory')->where('id', $data['part_id'])->exists()
                                    ? (int) $data['part_id'] : null,
            'status'         => 'new',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // Tell the team right away. Never let a mail problem lose or fail the request itself.
        try {
            $body = "New part request #{$id}\n\n"
                  . "Name:    {$data['customer_name']}\n"
                  . "Phone:   {$data['customer_phone']}\n"
                  . "Email:   " . ($data['customer_email'] ?? '-') . "\n"
                  . "Region:  " . ($data['region'] ?? '-') . "\n"
                  . "Vehicle: " . ($data['vehicle_text'] ?? '-') . "\n"
                  . "Part:    {$data['part_text']}\n"
                  . "Notes:   " . ($data['notes'] ?? '-') . "\n\n"
                  . "Open the inbox: " . url('/admin/part-requests');
            Mail::raw($body, function ($m) use ($id) {
                $m->to('info@autozenithparts.com')->subject("New part request #{$id} — Auto Zenith Parts");
            });
        } catch (\Throwable $e) {
            Log::warning('Part request email failed: ' . $e->getMessage());
        }

        return redirect(url()->previous() . '#request-part')
            ->with('part_request_sent', 'Thank you — we have your request and will contact you as soon as we find it.');
    }
}
