<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    /**
     * Normalize a phone number for matching across tables —
     * strip everything except digits.
     */
    private function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D/', '', $phone ?? '');
    }

    // =========================================================
    // AJAX: GET /admin/customers/lookup?q=...
    // Used by the manual invoice form — search existing customers by
    // phone fragment or name, so staff can pull real history instead
    // of re-typing "Walk-in Customer" every time.
    // =========================================================
    public function lookup(Request $request)
    {
        $q = trim($request->get('q', ''));
        if ($q === '' || strlen($q) < 2) {
            return response()->json(['customers' => []]);
        }

        $qDigits = preg_replace('/\D/', '', $q);

        $orderRows = DB::table('orders')
            ->select('customer_name', 'customer_phone', 'customer_email', 'total_amount_usd', 'created_at')
            ->get()
            ->map(fn($r) => (object)[
                'name' => $r->customer_name, 'phone' => $r->customer_phone,
                'email' => $r->customer_email, 'address' => null,
                'amount' => $r->total_amount_usd, 'date' => $r->created_at,
            ]);

        $invoiceRows = DB::table('invoices')
            ->select('customer_name', 'customer_phone', 'customer_email', 'customer_address', 'subtotal_usd', 'created_at')
            ->get()
            ->map(fn($r) => (object)[
                'name' => $r->customer_name, 'phone' => $r->customer_phone,
                'email' => $r->customer_email, 'address' => $r->customer_address ?? null,
                'amount' => $r->subtotal_usd, 'date' => $r->created_at,
            ]);

        $all = $orderRows->concat($invoiceRows);

        $matches = $all->filter(function ($r) use ($q, $qDigits) {
            $nameMatch  = $r->name && str_contains(strtolower($r->name), strtolower($q));
            $phoneMatch = $qDigits !== '' && str_contains($this->normalizePhone($r->phone), $qDigits);
            return $nameMatch || $phoneMatch;
        });

        $customers = $matches->groupBy(fn($r) => $this->normalizePhone($r->phone))
            ->filter(fn($g, $phone) => $phone !== '')
            ->map(function ($group, $phone) {
                $latest = $group->sortByDesc('date')->first();
                return [
                    'phone'       => $phone,
                    'name'        => $latest->name,
                    'email'       => $group->pluck('email')->filter()->first(),
                    'address'     => $group->pluck('address')->filter()->first(),
                    'total_spent' => $group->sum('amount'),
                    'order_count' => $group->count(),
                ];
            })
            ->sortByDesc('total_spent')
            ->take(10)
            ->values();

        return response()->json(['customers' => $customers]);
    }

    // =========================================================
    // GET /admin/customers — searchable list, auto-aggregated
    // from orders + invoices, grouped by normalized phone
    // =========================================================
    public function index(Request $request)
    {
        $search = trim($request->get('q', ''));

        // Pull raw rows from both sources
        $orderRows = DB::table('orders')
            ->select('customer_name', 'customer_phone', 'customer_email',
                      'customer_city', 'customer_country', 'total_amount_usd',
                      'created_at', 'order_ref')
            ->get()
            ->map(fn($r) => (object)[
                'source'       => 'order',
                'ref'          => $r->order_ref,
                'name'         => $r->customer_name,
                'phone'        => $r->customer_phone,
                'email'        => $r->customer_email,
                'city'         => $r->customer_city,
                'country'      => $r->customer_country,
                'amount_usd'   => $r->total_amount_usd,
                'date'         => $r->created_at,
            ]);

        $invoiceRows = DB::table('invoices')
            ->select('customer_name', 'customer_phone', 'customer_email',
                      'subtotal_usd', 'created_at', 'invoice_no', 'location')
            ->get()
            ->map(fn($r) => (object)[
                'source'       => 'invoice',
                'ref'          => $r->invoice_no,
                'name'         => $r->customer_name,
                'phone'        => $r->customer_phone,
                'email'        => $r->customer_email,
                'city'         => null,
                'country'      => $r->location,
                'amount_usd'   => $r->subtotal_usd,
                'date'         => $r->created_at,
            ]);

        $all = $orderRows->concat($invoiceRows);

        // Group by normalized phone number — this IS the customer record
        $customers = $all->groupBy(fn($r) => $this->normalizePhone($r->phone))
            ->filter(fn($group, $phone) => $phone !== '') // drop blank phones
            ->map(function ($group, $phone) {
                $latest = $group->sortByDesc('date')->first();
                return (object)[
                    'phone'         => $phone,
                    'name'          => $latest->name,
                    'email'         => $group->pluck('email')->filter()->first(),
                    'city'          => $group->pluck('city')->filter()->first(),
                    'country'       => $group->pluck('country')->filter()->first(),
                    'total_orders'  => $group->count(),
                    'total_spent'   => $group->sum('amount_usd'),
                    'last_purchase' => $latest->date,
                ];
            })
            ->values();

        if ($search) {
            $s = strtolower($search);
            $customers = $customers->filter(function ($c) use ($s) {
                return str_contains(strtolower($c->name ?? ''), $s)
                    || str_contains($c->phone, preg_replace('/\D/', '', $s));
            })->values();
        }

        $customers = $customers->sortByDesc('total_spent')->values();

        // ── Manually-added contacts (freelancers, contractors,
        // delivery personnel, jobbers) — these have no purchase
        // history, so they're merged in separately rather than
        // forced through the phone-grouping-by-purchase logic above. ──
        $contactsQuery = DB::table('contacts');
        if ($search) {
            $s = strtolower($search);
            $contactsQuery->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('contact_type', 'like', "%{$s}%");
            });
        }
        $contacts = $contactsQuery->orderBy('name')->get()->map(fn($c) => (object)[
            'id'            => $c->id,
            'phone'         => $c->phone,
            'name'          => $c->name,
            'contact_type'  => $c->contact_type,
            'email'         => $c->email,
            'is_contact'    => true, // distinguishes manual contacts from purchase-derived customers in the view
            'city'          => null, 'country' => null,
            'total_orders'  => 0, 'total_spent' => 0, 'last_purchase' => null,
        ]);

        $customers = $customers->merge($contacts)->values();

        // Simple manual pagination since this is an in-memory collection
        $perPage = 25;
        $page    = (int) $request->get('page', 1);
        $total   = $customers->count();
        $paged   = $customers->slice(($page - 1) * $perPage, $perPage)->values();

        return view('admin.customers.index', [
            'customers'   => $paged,
            'search'      => $search,
            'total'       => $total,
            'page'        => $page,
            'lastPage'    => max(1, ceil($total / $perPage)),
        ]);
    }

    // =========================================================
    // GET /admin/customers/{phone} — full history for one customer
    // =========================================================
    public function show(string $phone)
    {
        $normalizedPhone = $this->normalizePhone($phone);

        $orders = DB::table('orders')
            ->whereRaw("REPLACE(REPLACE(REPLACE(customer_phone, '+', ''), ' ', ''), '-', '') = ?", [$normalizedPhone])
            ->orderByDesc('created_at')
            ->get();

        $invoices = DB::table('invoices')
            ->whereRaw("REPLACE(REPLACE(REPLACE(customer_phone, '+', ''), ' ', ''), '-', '') = ?", [$normalizedPhone])
            ->orderByDesc('created_at')
            ->get();

        if ($orders->isEmpty() && $invoices->isEmpty()) {
            abort(404, 'No customer found with this phone number.');
        }

        $latest = $orders->concat($invoices)->sortByDesc('created_at')->first();

        // Pull order items / invoice items for a most-purchased breakdown
        $orderIds   = $orders->pluck('id');
        $invoiceIds = $invoices->pluck('id');

        $orderItems = $orderIds->isNotEmpty()
            ? DB::table('order_items')->whereIn('order_id', $orderIds)->get()
            : collect();

        $invoiceItems = $invoiceIds->isNotEmpty()
            ? DB::table('invoice_items')->whereIn('invoice_id', $invoiceIds)->get()
            : collect();

        $allItems = $orderItems->concat($invoiceItems);

        $topItems = $allItems
            ->groupBy(fn($i) => $i->part_name ?? 'Unknown')
            ->map(fn($g, $name) => (object)[
                'part_name' => $name,
                'count'     => $g->sum(fn($i) => $i->qty ?? $i->quantity ?? 1),
            ])
            ->sortByDesc('count')
            ->values()
            ->take(10);

        $totalSpent = $orders->sum('total_amount_usd') + $invoices->sum('subtotal_usd');

        return view('admin.customers.show', [
            'phone'      => $normalizedPhone,
            'name'       => $latest->customer_name ?? 'Unknown',
            'email'      => $orders->pluck('customer_email')->filter()->first()
                              ?? $invoices->pluck('customer_email')->filter()->first(),
            'orders'     => $orders,
            'invoices'   => $invoices,
            'topItems'   => $topItems,
            'totalSpent' => $totalSpent,
            'totalCount' => $orders->count() + $invoices->count(),
        ]);
    }

    // =========================================================
    // Manual contacts — freelancers, contractors, delivery
    // personnel, jobbers, or any phone-book entry that isn't
    // purely derived from purchase history. Editable by Staff
    // and above (not Stocking Clerk, per the role restriction
    // already enforced by middleware on this whole admin group).
    // =========================================================

    public function createContact()
    {
        return view('admin.customers.create-contact', [
            'types' => ['Customer', 'Freelancer', 'Contractor', 'Delivery Personnel', 'Jobber', 'Other'],
        ]);
    }

    public function storeContact(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:150',
            'contact_type' => 'required|string',
            'phone'        => 'required|string|max:30',
            'whatsapp'     => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:150',
            'address'      => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $id = DB::table('contacts')->insertGetId([
            'name'                => $request->name,
            'contact_type'        => $request->contact_type,
            'phone'               => $request->phone,
            'whatsapp'            => $request->whatsapp,
            'email'               => $request->email,
            'address'             => $request->address,
            'notes'               => $request->notes,
            'created_by_staff_id' => \Illuminate\Support\Facades\Session::get('staff_id'),
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        return redirect()->route('admin.customers.index')->with('success', 'Contact added.');
    }

    public function editContact(int $id)
    {
        $contact = DB::table('contacts')->where('id', $id)->first();
        abort_if(!$contact, 404);

        return view('admin.customers.edit-contact', [
            'contact' => $contact,
            'types' => ['Customer', 'Freelancer', 'Contractor', 'Delivery Personnel', 'Jobber', 'Other'],
        ]);
    }

    public function updateContact(Request $request, int $id)
    {
        $request->validate([
            'name'         => 'required|string|max:150',
            'contact_type' => 'required|string',
            'phone'        => 'required|string|max:30',
            'whatsapp'     => 'nullable|string|max:30',
            'email'        => 'nullable|email|max:150',
            'address'      => 'nullable|string|max:255',
            'notes'        => 'nullable|string|max:1000',
        ]);

        DB::table('contacts')->where('id', $id)->update([
            'name'         => $request->name,
            'contact_type' => $request->contact_type,
            'phone'        => $request->phone,
            'whatsapp'     => $request->whatsapp,
            'email'        => $request->email,
            'address'      => $request->address,
            'notes'        => $request->notes,
            'updated_at'   => now(),
        ]);

        return redirect()->route('admin.customers.index')->with('success', 'Contact updated.');
    }

    public function destroyContact(int $id)
    {
        DB::table('contacts')->where('id', $id)->delete();
        return redirect()->route('admin.customers.index')->with('success', 'Contact removed.');
    }
}
