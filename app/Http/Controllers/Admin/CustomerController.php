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
}
