<?php
// FILE: app/Http/Controllers/Admin/OpenTabController.php
//
// #17 — one open running tab per customer. Any staff member can add
// parts/services to it across multiple visits in the same day (e.g.
// a customer fixing their car at the workshop, ordering parts in the
// morning and a service in the afternoon). Closing the tab converts
// everything into one final invoice, deducting stock for real parts
// at that point (not as each item is added — the tab itself never
// touches inventory until closed).

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class OpenTabController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'open');
        $tabs = DB::table('open_tabs')
            ->when($status, fn($q) => $q->where('status', $status))
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('admin.tabs.index', compact('tabs', 'status'));
    }

    public function create()
    {
        $locations = ['Waxahachie TX','Kennedale TX','Elkhorn WI','Ile-Ife Nigeria','Ibadan Nigeria','Lagos Nigeria','Abuja Nigeria','Akure Nigeria','Accra Ghana'];
        return view('admin.tabs.create', compact('locations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_name'  => 'required|string|max:150',
            'customer_phone' => 'required|string|max:30',
            'customer_email' => 'nullable|email',
            'location'       => 'required|string',
        ]);

        // ── Check for an existing open tab for this customer (same
        // phone number) before creating a new one — staff should be
        // prompted to continue the existing tab rather than fragment
        // the same customer's day across two separate tabs, unless
        // they explicitly confirm they want a genuinely fresh one.
        if (!$request->boolean('force_new')) {
            $existingTab = DB::table('open_tabs')
                ->where('customer_phone', $request->customer_phone)
                ->where('status', 'open')
                ->first();

            if ($existingTab) {
                return back()->withInput()->with('existing_tab', $existingTab);
            }
        }

        $year = date('Y');
        $seq  = DB::table('open_tabs')->whereYear('created_at', $year)->count() + 1;
        $tabNo = 'TAB-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

        $tabId = DB::table('open_tabs')->insertGetId([
            'tab_no'           => $tabNo,
            'customer_name'    => $request->customer_name,
            'customer_phone'   => $request->customer_phone,
            'customer_email'   => $request->customer_email,
            'location'         => $request->location,
            'status'           => 'open',
            'opened_by_staff_id' => Session::get('staff_id'),
            'notes'            => $request->notes,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return redirect()->route('admin.tabs.show', $tabId)->with('success', "Tab {$tabNo} opened for {$request->customer_name}.");
    }

    public function show(int $id)
    {
        $tab = DB::table('open_tabs')->where('id', $id)->first();
        abort_if(!$tab, 404);

        $items = DB::table('open_tab_items')->where('tab_id', $id)->orderBy('created_at')->get();
        $currency = InvoiceController::currencyForLocation($tab->location);
        $total = $items->sum(fn($i) => $i->unit_price_local * $i->qty);

        return view('admin.tabs.show', compact('tab', 'items', 'currency', 'total'));
    }

    // AJAX: search both parts (in stock, this tab's location) and services,
    // reusing the same pattern as Place Order / Quick Receipt.
    public function searchItems(Request $request, int $id)
    {
        $tab = DB::table('open_tabs')->where('id', $id)->first();
        abort_if(!$tab, 404);

        $q = trim($request->get('q', ''));

        $parts = DB::table('parts_inventory')
            ->where('status', 'Available')->where('stock_qty', '>', 0)
            ->where('location', $tab->location)
            ->when($q, fn($qq) => $qq->where(fn($w) => $w->where('part_name', 'like', "%{$q}%")->orWhere('part_code', 'like', "%{$q}%")))
            ->select('id', 'part_code', 'part_name', 'price_local', 'currency_code', 'stock_qty')
            ->limit(20)->get()
            ->map(fn($p) => (array) $p + ['item_type' => 'part']);

        $services = DB::table('service_rates')->where('is_active', true)
            ->when($q, fn($qq) => $qq->where('name', 'like', "%{$q}%"))
            ->select('id', 'service_code as part_code', 'name as part_name', 'default_price as price_local')
            ->limit(20)->get()
            ->map(fn($s) => (array) $s + ['item_type' => 'service', 'currency_code' => InvoiceController::currencyForLocation($tab->location)['code'], 'stock_qty' => null]);

        return response()->json(['items' => $parts->concat($services)->values()]);
    }

    // Add one item to the tab (does NOT touch inventory yet — only on close)
    public function addItem(Request $request, int $id)
    {
        $tab = DB::table('open_tabs')->where('id', $id)->first();
        abort_if(!$tab || $tab->status !== 'open', 404);

        $request->validate([
            'item_type' => 'required|in:part,service',
            'ref_id'    => 'required|integer',
            'qty'       => 'required|integer|min:1',
        ]);

        if ($request->item_type === 'part') {
            $part = DB::table('parts_inventory')->where('id', $request->ref_id)->first();
            if (!$part || $part->status !== 'Available' || $part->stock_qty < $request->qty) {
                return back()->with('error', 'That part is no longer available in the requested quantity.');
            }
            DB::table('open_tab_items')->insert([
                'tab_id' => $id, 'item_type' => 'part', 'part_id' => $part->id,
                'item_name' => $part->part_name, 'item_code' => $part->part_code,
                'qty' => $request->qty, 'unit_price_local' => $part->price_local,
                'currency_code' => $part->currency_code, 'added_by_staff_id' => Session::get('staff_id'),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } else {
            $service = DB::table('service_rates')->where('id', $request->ref_id)->first();
            if (!$service) return back()->with('error', 'Service not found.');
            $currency = InvoiceController::currencyForLocation($tab->location);
            DB::table('open_tab_items')->insert([
                'tab_id' => $id, 'item_type' => 'service', 'service_id' => $service->id,
                'item_name' => $service->name, 'item_code' => $service->service_code,
                'qty' => $request->qty, 'unit_price_local' => $service->default_price ?? 0,
                'currency_code' => $currency['code'], 'added_by_staff_id' => Session::get('staff_id'),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Added to tab.');
    }

    // Remove an item — override-PIN protected, same as POS/Place Order (#2)
    public function removeItem(int $id, int $itemId)
    {
        DB::table('open_tab_items')->where('id', $itemId)->where('tab_id', $id)->delete();
        return back()->with('success', 'Item removed from tab.');
    }

    // Close the tab — converts every item into one final invoice,
    // deducting stock for real parts at this point.
    public function close(Request $request, int $id)
    {
        $tab = DB::table('open_tabs')->where('id', $id)->first();
        abort_if(!$tab || $tab->status !== 'open', 404);

        $items = DB::table('open_tab_items')->where('tab_id', $id)->get();
        if ($items->isEmpty()) {
            return back()->with('error', 'Cannot close an empty tab — add at least one item first.');
        }

        // Re-validate stock for every part-type item before committing
        $stockErrors = [];
        foreach ($items->where('item_type', 'part') as $item) {
            $part = DB::table('parts_inventory')->where('id', $item->part_id)->first();
            if (!$part || $part->stock_qty < $item->qty) {
                $stockErrors[] = "{$item->item_name}: only " . ($part->stock_qty ?? 0) . " left in stock, tab has {$item->qty}.";
            }
        }
        if (!empty($stockErrors)) {
            return back()->with('error', 'Cannot close tab — ' . implode(' | ', $stockErrors));
        }

        $currency = InvoiceController::currencyForLocation($tab->location);
        $subtotalLocal = $items->sum(fn($i) => $i->unit_price_local * $i->qty);
        $invoiceNo = 'TAB-INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        DB::beginTransaction();
        try {
            $invoiceId = DB::table('invoices')->insertGetId([
                'invoice_no'       => $invoiceNo,
                'invoice_type'     => 'service', // mixed parts+services, same convention as Quick Receipt
                'customer_name'    => $tab->customer_name,
                'customer_phone'   => $tab->customer_phone,
                'customer_email'   => $tab->customer_email,
                'location'         => $tab->location,
                'currency_code'    => $currency['code'],
                'subtotal_local'   => $subtotalLocal,
                'subtotal_usd'     => $subtotalLocal,
                'payment_method'   => $request->payment_method ?? 'Cash',
                'created_by'       => Session::get('staff_name') ?? 'Admin',
                'notes'            => "Closed from Open Tab {$tab->tab_no}",
                'created_at'       => now(), 'updated_at' => now(),
            ]);

            foreach ($items as $item) {
                DB::table('invoice_items')->insert([
                    'invoice_id'       => $invoiceId,
                    'part_id'          => $item->part_id,
                    'part_name'        => $item->item_name,
                    'part_code'        => $item->item_code ?? ($item->item_type === 'service' ? 'SERVICE' : ''),
                    'condition_grade'  => $item->item_type === 'part' ? 'N/A' : 'N/A',
                    'qty'              => $item->qty,
                    'unit_price_local' => $item->unit_price_local,
                    'unit_price_usd'   => $item->unit_price_local,
                    'created_at'       => now(), 'updated_at' => now(),
                ]);

                if ($item->item_type === 'part' && $item->part_id) {
                    $locked = DB::table('parts_inventory')->where('id', $item->part_id)->lockForUpdate()->first();
                    $newQty = max(0, $locked->stock_qty - $item->qty);
                    DB::table('parts_inventory')->where('id', $item->part_id)->update([
                        'stock_qty' => $newQty,
                        'status'    => $newQty <= 0 ? 'Sold' : 'Available',
                        'updated_at'=> now(),
                    ]);
                }
            }

            DB::table('open_tabs')->where('id', $id)->update([
                'status'            => 'closed',
                'closed_by_staff_id'=> Session::get('staff_id'),
                'closed_invoice_id' => $invoiceId,
                'closed_at'         => now(),
                'updated_at'        => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Could not close tab: ' . $e->getMessage());
        }

        return redirect()->route('admin.invoices.show.manual', $invoiceId)
            ->with('success', "Tab {$tab->tab_no} closed — invoice {$invoiceNo} created.");
    }

    public function cancel(int $id)
    {
        DB::table('open_tabs')->where('id', $id)->update(['status' => 'cancelled', 'updated_at' => now()]);
        return redirect()->route('admin.tabs.index')->with('success', 'Tab cancelled.');
    }
}
