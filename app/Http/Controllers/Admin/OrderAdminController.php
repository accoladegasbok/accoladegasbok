<?php
// FILE: app/Http/Controllers/Admin/OrderAdminController.php

namespace App\Http\Controllers\Admin;

use App\Events\PartSold;
use App\Http\Controllers\Controller;
use App\Mail\OrderReceiptMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class OrderAdminController extends Controller
{
    // =========================================================
    // GET /admin/orders
    // =========================================================
    public function index(Request $request)
    {
        $query = DB::table('orders')->whereNull('deleted_at')->orderByDesc('created_at');

        if ($f = $request->get('status'))  $query->where('order_status', $f);
        if ($f = $request->get('payment')) $query->where('payment_status', $f);
        if ($f = $request->get('method'))  $query->where('payment_method', $f);
        if ($f = $request->get('q')) {
            $query->where(function ($q) use ($f) {
                $q->where('order_ref',       'like', "%{$f}%")
                  ->orWhere('customer_name', 'like', "%{$f}%")
                  ->orWhere('customer_phone','like', "%{$f}%");
            });
        }

        $orders = $query->paginate(25)->withQueryString();

        $counts = DB::table('orders')
            ->whereNull('deleted_at')
            ->select('payment_status', DB::raw('count(*) as n'))
            ->groupBy('payment_status')
            ->pluck('n', 'payment_status');

        $todayRevenue = DB::table('orders')
            ->whereNull('deleted_at')
            ->whereDate('created_at', today())
            ->where('payment_status', 'confirmed')
            ->select('currency_code', DB::raw('SUM(COALESCE(total_amount_local, total_amount_ngn, total_amount_usd)) as total'))
            ->groupBy('currency_code')
            ->pluck('total', 'currency_code');

        return view('admin.orders.index', compact('orders', 'counts', 'todayRevenue'));
    }

    // =========================================================
    // GET /admin/orders/{id}
    // =========================================================
    public function show(int $id)
    {
        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) abort(404);

        $items = DB::table('order_items')->where('order_id', $id)->get();
        $currencyCode   = $order->currency_code ?? ($order->total_amount_ngn ? 'NGN' : 'USD');
        $currencySymbol = match($currencyCode) { 'NGN' => '₦', 'GHS' => 'GH₵', default => '$' };
        return view('admin.orders.show', compact('order', 'items', 'currencyCode', 'currencySymbol'));
    }

    // =========================================================
    // Shared data builder for the PDF letterhead template — mirrors
    // InvoiceController::buildInvoicePdfData() exactly, sourced from
    // orders/order_items instead of invoices/invoice_items, reusing
    // the SAME PDF Blade view so orders and invoices produce
    // identically-formatted PDFs. Orders have no discount or return-
    // credit columns at all (confirmed earlier this session), so
    // those fields are simply left at 0/null — the template already
    // guards on them being empty.
    // =========================================================
    private function buildOrderPdfData(int $orderId): ?array
    {
        $order = DB::table('orders')->where('id', $orderId)->first();
        if (!$order) return null;

        $items = DB::table('order_items')->where('order_id', $orderId)->get();

        // FIXED: location previously fell straight through to a
        // hardcoded 'Waxahachie TX' default whenever orders.location
        // was empty, with no fallback at all — meaning an Ile-Ife order
        // with a blank location column would print with the WRONG
        // business identity entirely (Accolade Autos/USA instead of
        // Gasbok Engineering/Nigeria). InvoiceController::show()
        // already had a more reliable fallback chain (check the
        // order's actual items for a real location) — matching that
        // here.
        // FIXED: crashed with "Undefined property: stdClass::$location" —
        // order_items doesn't actually have a column by that exact
        // FIXED (for real this time): the crash persisted because I'd
        // used ?: (short ternary) for the very first check —
        // "$order->location ?: ..." — and unlike ?? (null coalescing),
        // ?: does NOT suppress an undefined-property warning; it still
        // has to evaluate $order->location to check truthiness, and
        // THAT evaluation is what threw. The data_get() calls further
        // down were already safe — only the first link in the chain
        // wasn't. Using data_get() consistently for every step now.
        $firstItem = $items->first();
        $resolvedLocation = data_get($order, 'location')
            ?: data_get($firstItem, 'location')
            ?: data_get($firstItem, 'part_location')
            ?: 'Waxahachie TX';

        $currencyCode = data_get($order, 'currency_code')
            ?? \App\Http\Controllers\Admin\InvoiceController::currencyForLocation($resolvedLocation)['code'];

        // FIXED: DomPDF's default font can't render the ₦ Unicode
        // glyph — it silently prints as a literal "?" instead, which
        // is exactly what showed up on a real downloaded receipt. Use
        // the plain "NGN" text prefix for the PDF specifically
        // (guaranteed correct regardless of font Unicode coverage)
        // rather than the pretty symbol used everywhere else in the
        // browser-rendered views, where it renders fine.
        $syms = ['NGN' => 'NGN ', 'GHS' => 'GHS ', 'USD' => '$'];
        $sym  = $syms[$currencyCode] ?? '$';
        $fmt  = fn($n) => $sym . number_format((float) $n, $currencyCode === 'NGN' ? 0 : 2);

        $lineItems = $items->map(function ($item) use ($fmt) {
            $priceLocal = $item->unit_price_local ?? $item->unit_price_ngn ?? $item->unit_price_usd ?? 0;
            $qty = $item->qty ?? 1;
            return (object)[
                'part_name'       => $item->part_name,
                'part_code'       => $item->part_code,
                'brand'           => $item->brand ?? null,
                'model'           => $item->model ?? null,
                'condition_grade' => $item->condition_grade ?? null,
                'qty'             => $qty,
                'unit_price_fmt'  => $fmt($priceLocal),
                'total_fmt'       => $fmt($priceLocal * $qty),
            ];
        });

        $customerInfo = (object)[
            'name' => $order->customer_name, 'phone' => $order->customer_phone,
            'email' => $order->customer_email, 'address' => $order->customer_address ?? null,
        ];

        $totalLocal = $order->total_amount_local ?? $order->total_amount_ngn ?? $order->total_amount_usd ?? 0;
        $businessInfo = app(\App\Http\Controllers\Admin\InvoiceController::class)->getBusinessInfo($resolvedLocation);

        // NEW: orders now have real discount columns (store() was
        // fixed to actually save them) — read them the same way
        // InvoiceController::show() does for consistency.
        $discountLocal = (float) ($order->discount_amount_local ?? 0);
        $grossTotalLocal = $totalLocal + $discountLocal;
        $discountLabel = null;
        if ($discountLocal > 0) {
            $pct = $grossTotalLocal > 0 ? ($discountLocal / $grossTotalLocal) * 100 : 0;
            $discountLabel = "Discount (" . rtrim(rtrim(number_format($pct, 1), '0'), '.') . "%):";
        }

        return [
            'invoiceNo'    => $order->order_ref,
            'invoice'      => $order,
            'lineItems'    => $lineItems,
            'currency'     => ['code' => $currencyCode, 'symbol' => $sym],
            'businessInfo' => $businessInfo,
            'saleLocation' => $resolvedLocation,
            'createdAt'    => $order->created_at,
            'customerInfo' => $customerInfo,
            'paymentMethod'=> $order->payment_method ?? 'Online',
            'isVehicleSale'=> false,
            'subtotalFmt'  => $fmt($grossTotalLocal),
            'totalFmt'     => $fmt($totalLocal),
            'discountLocal'=> $discountLocal,
            'discountFmt'  => $discountLocal > 0 ? $fmt($discountLocal) : null,
            'discountLabel'=> $discountLabel,
            'returnCreditApplied' => 0,
            'returnCreditFmt'     => null,
            'footerAddresses'     => \App\Http\Controllers\Admin\InvoiceController::footerAddressesForLocation($resolvedLocation),
        ];
    }

    // =========================================================
    // GET /admin/orders/{id}/download-pdf
    // =========================================================
    public function downloadPdf(int $orderId)
    {
        $data = $this->buildOrderPdfData($orderId);
        abort_if(!$data, 404);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.invoices.invoice-pdf', $data)->setPaper('a4');
        return $pdf->download("order-{$data['invoiceNo']}.pdf");
    }

    // =========================================================
    // POST /admin/orders/{id}/send-customer-copy
    // =========================================================
    public function sendCustomerCopy(int $orderId)
    {
        $data = $this->buildOrderPdfData($orderId);
        abort_if(!$data, 404);
        $order = $data['invoice']; // same key name as the invoice builder, holds the order row here

        $pdfBinary = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.invoices.invoice-pdf', $data)->setPaper('a4')->output();

        $emailHtml = "<p>Hi {$order->customer_name},</p>"
            . "<p>Please find attached your receipt for order <strong>{$order->order_ref}</strong>.</p>"
            . "<p><strong>Total: {$data['totalFmt']}</strong></p>"
            . "<p>Thank you for your business!</p><p>— Auto Zenith Parts</p>";
        $message = "Hi {$order->customer_name}, here's your receipt for order {$order->order_ref} — Total: {$data['totalFmt']}. — Auto Zenith Parts";

        $emailSent = false;
        if (!empty($order->customer_email)) {
            $emailSent = \App\Services\NotificationService::sendEmail(
                $order->customer_email,
                $order->customer_name,
                "Your Receipt — Order {$order->order_ref}",
                $emailHtml,
                [],
                [['data' => $pdfBinary, 'filename' => "order-{$order->order_ref}.pdf", 'mime' => 'application/pdf']]
            );
        }
        $whatsappLink = !empty($order->customer_phone)
            ? \App\Services\NotificationService::whatsappLink($order->customer_phone, $message)
            : null;

        $statusMsg = $emailSent
            ? 'Customer copy emailed with PDF attached.'
            : 'Could not send email (check customer has an email on file).';

        return back()->with('success', $statusMsg)->with('whatsapp_reminder_link', $whatsappLink);
    }

    // =========================================================
    // GET /admin/orders/{id}/edit — admin/manager only
    // =========================================================
    public function edit(int $id)
    {
        if (!in_array(Session::get('staff_role'), ['admin', 'manager'])) {
            abort(403, 'Only admin or manager accounts can edit orders.');
        }

        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) abort(404);

        $items = DB::table('order_items')->where('order_id', $id)->get();

        $existingItemsJson = $items->map(function ($i) {
            return [
                'item_type'  => $i->item_type,
                'part_id'    => $i->part_id,
                'service_id' => $i->service_id,
                'name'       => $i->part_name,
                'code'       => $i->part_code,
                'price'      => $i->unit_price_local ?? $i->unit_price_ngn ?? $i->unit_price_usd,
                'qty'        => 1,
            ];
        })->values()->toJson();

        return view('admin.orders.edit', compact('order', 'items', 'existingItemsJson'));
    }

    // =========================================================
    // PUT /admin/orders/{id}
    // =========================================================
    public function update(Request $request, int $id)
    {
        if (!in_array(Session::get('staff_role'), ['admin', 'manager'])) {
            abort(403, 'Only admin or manager accounts can edit orders.');
        }

        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) abort(404);

        $request->validate([
            'items'             => 'required|array|min:1',
            'items.*.item_type' => 'required|in:part,service',
            'items.*.id'        => 'required|integer',
            // FIXED: qty was never validated or read here at all — the
            // edit form silently treated every line as qty=1. Now reads
            // it (default 1 for any older form submission that doesn't
            // send it yet), matching AdminOrderController::store().
            'items.*.qty'       => 'nullable|integer|min:1',
        ]);

        $oldItems   = DB::table('order_items')->where('order_id', $id)->get();
        $oldPartIds = $oldItems->where('item_type', 'part')->pluck('part_id')->filter()->values();
        $newPartIds = collect($request->items)->where('item_type', 'part')->pluck('id')->values();

        // FIXED: previously kept only a list of old/new part IDs and did
        // a blind status flip (Reserved<->Available) on whole rows. Now
        // tracks actual QUANTITY per part_id on both sides, so partial
        // changes (e.g. an order line for a 20-unit consumable row going
        // from qty=2 to qty=5) adjust stock_qty by the real delta instead
        // of falsely freeing or re-reserving the whole batch.
        $oldPartQty = $oldItems->where('item_type', 'part')->filter(fn($i) => $i->part_id)
            ->groupBy('part_id')->map(fn($rows) => (int) $rows->sum(fn($r) => $r->qty ?? 1));
        $newPartQty = collect($request->items)->where('item_type', 'part')->filter(fn($i) => $i['id'])
            ->groupBy('id')->map(fn($rows) => (int) collect($rows)->sum(fn($r) => $r['qty'] ?? 1));

        // Release removed parts — add back the FULL old quantity, and
        // only flip status back to Available if this part had actually
        // been fully depleted (status='Reserved'); if other stock was
        // still Available the whole time, no status change is needed.
        $removedPartIds = $oldPartIds->diff($newPartIds);
        foreach ($removedPartIds as $partId) {
            $qtyToRestore = $oldPartQty->get($partId, 1);
            DB::table('parts_inventory')->where('id', $partId)->increment('stock_qty', $qtyToRestore);
            DB::table('parts_inventory')->where('id', $partId)
                ->where('status', 'Reserved')
                ->update(['status' => 'Available', 'updated_at' => now()]);
        }

        // Check newly added parts have enough stock for the requested qty
        $addedPartIds = $newPartIds->diff($oldPartIds);
        $stockErrors  = [];
        foreach ($addedPartIds as $partId) {
            $part = DB::table('parts_inventory')->where('id', $partId)->first();
            $qtyNeeded = $newPartQty->get($partId, 1);
            if (!$part) { $stockErrors[] = "Part #{$partId} no longer exists."; continue; }
            if ($part->status !== 'Available') { $stockErrors[] = "{$part->part_code} ({$part->part_name}) is no longer Available."; continue; }
            if ($qtyNeeded > $part->stock_qty) { $stockErrors[] = "{$part->part_code}: requested {$qtyNeeded}, only {$part->stock_qty} in stock."; continue; }
        }

        // NEW: parts kept on the order but with a CHANGED quantity need
        // their stock delta applied too — previously ignored entirely,
        // so increasing a line's qty on edit never actually reserved the
        // extra units, and decreasing it never released the difference.
        $keptPartIds = $oldPartIds->intersect($newPartIds);
        foreach ($keptPartIds as $partId) {
            $delta = $newPartQty->get($partId, 1) - $oldPartQty->get($partId, 1);
            if ($delta === 0) continue;
            if ($delta > 0) {
                $part = DB::table('parts_inventory')->where('id', $partId)->first();
                if (!$part || $delta > $part->stock_qty) {
                    $stockErrors[] = "{$part->part_code ?? "Part #{$partId}"}: increasing quantity by {$delta}, only " . ($part->stock_qty ?? 0) . " more in stock.";
                }
            }
        }

        if (!empty($stockErrors)) {
            return back()->withInput()->with('error', 'Cannot save changes — ' . implode(' | ', $stockErrors));
        }

        $lineItems = collect($request->items)->map(function ($item) use ($order) {
            $qty = (int) ($item['qty'] ?? 1);
            if ($item['item_type'] === 'part') {
                $part = DB::table('parts_inventory')->where('id', $item['id'])->first();
                return $part ? (object)[
                    'type' => 'part', 'part_id' => $part->id, 'service_id' => null, 'qty' => $qty,
                    'part_name' => $part->part_name, 'part_code' => $part->part_code,
                    'brand' => $part->brand, 'model' => $part->model,
                    'year_from' => $part->year_from, 'year_to' => $part->year_to,
                    'condition_grade' => $part->condition_grade, 'location' => $part->location,
                    'unit_price_local' => $part->price_local ?? $part->price_usd,
                    'currency_code' => $part->currency_code ?? 'USD',
                ] : null;
            }
            $service = DB::table('service_rates')->where('id', $item['id'])->first();
            if (!$service) return null;
            $resolvedLocation = $order->location ?? $order->customer_country ?? 'Waxahachie TX';
            $priced = \App\Http\Controllers\Admin\ServiceRateController::priceForLocation($service->id, $resolvedLocation);
            return (object)[
                'type' => 'service', 'part_id' => null, 'service_id' => $service->id, 'qty' => $qty,
                'part_name' => $service->name, 'part_code' => $service->service_code,
                'brand' => $service->category, 'model' => null,
                'year_from' => null, 'year_to' => null,
                'condition_grade' => null, 'location' => $resolvedLocation,
                'unit_price_local' => $priced['price'], 'currency_code' => $priced['currency_code'],
            ];
        })->filter()->values();

        // FIXED: this used to sum unit_price_local ALONE (implicit
        // qty=1 for every line, no matter what was actually ordered).
        // Now multiplies by the real qty per line.
        $newTotalLocal = $lineItems->sum(fn($li) => $li->unit_price_local * $li->qty);
        $orderCurrency = $lineItems->first()->currency_code ?? ($order->currency_code ?? 'USD');

        $changes = [];
        $oldTotal = $order->total_amount_local ?? $order->total_amount_ngn ?? $order->total_amount_usd ?? 0;
        if (round((float) $oldTotal, 2) !== round($newTotalLocal, 2)) $changes[] = "Total: " . round($oldTotal, 2) . " → " . round($newTotalLocal, 2) . " {$orderCurrency}";
        if ($oldItems->count() !== $lineItems->count()) $changes[] = "Item count: {$oldItems->count()} → {$lineItems->count()}";
        if ($removedPartIds->isNotEmpty()) $changes[] = "Removed " . $removedPartIds->count() . " part(s), released back to Available";
        if ($addedPartIds->isNotEmpty()) $changes[] = "Added " . $addedPartIds->count() . " part(s), marked Reserved";
        $changesSummary = $changes ? implode('; ', $changes) : 'No substantive changes detected.';

        DB::beginTransaction();
        try {
            DB::table('orders')->where('id', $id)->update([
                'total_amount_local' => round($newTotalLocal, 2),
                'total_amount_ngn'   => $orderCurrency === 'NGN' ? round($newTotalLocal) : $order->total_amount_ngn,
                'total_amount_usd'   => $orderCurrency === 'USD' ? round($newTotalLocal, 2) : $order->total_amount_usd,
                'currency_code'      => $orderCurrency,
                'updated_at'         => now(),
            ]);

            DB::table('order_items')->where('order_id', $id)->delete();
            foreach ($lineItems as $li) {
                DB::table('order_items')->insert([
                    'order_id'         => $id,
                    'item_type'        => $li->type,
                    'part_id'          => $li->part_id,
                    'service_id'       => $li->service_id,
                    // FIXED: qty was never saved on this path either —
                    // same root-cause fix as AdminOrderController::store().
                    'qty'              => $li->qty,
                    'part_name'        => $li->part_name,
                    'part_code'        => $li->part_code,
                    'brand'            => $li->brand,
                    'model'            => $li->model,
                    'year_from'        => $li->year_from,
                    'year_to'          => $li->year_to,
                    'condition_grade'  => $li->condition_grade,
                    'location'         => $li->location,
                    'unit_price_local' => $li->unit_price_local,
                    // FIXED: subtotal previously ignored qty entirely
                    // (subtotal_local === unit_price_local, always).
                    'subtotal_local'   => round($li->unit_price_local * $li->qty, 2),
                    'unit_price_ngn'   => $li->currency_code === 'NGN' ? round($li->unit_price_local) : null,
                    'unit_price_usd'   => $li->currency_code === 'USD' ? round($li->unit_price_local, 2) : null,
                    'subtotal_ngn'     => $li->currency_code === 'NGN' ? round($li->unit_price_local * $li->qty) : null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);

                if (!$li->part_id) continue;

                // Newly added part — decrement by its full ordered qty,
                // row-locked to protect against a concurrent sale of the
                // same part between the earlier stock check and this write.
                if (in_array($li->part_id, $addedPartIds->all())) {
                    $locked = DB::table('parts_inventory')->where('id', $li->part_id)->lockForUpdate()->first();
                    if (!$locked || $locked->stock_qty < $li->qty) {
                        throw new \Exception("Stock for {$li->part_code} changed before this edit was saved — only " . ($locked->stock_qty ?? 0) . " left.");
                    }
                    $remaining = $locked->stock_qty - $li->qty;
                    DB::table('parts_inventory')->where('id', $li->part_id)->update([
                        'stock_qty'  => $remaining,
                        'status'     => $remaining <= 0 ? 'Reserved' : 'Available',
                        'updated_at' => now(),
                    ]);
                    continue;
                }

                // Kept part whose quantity increased — apply just the
                // delta, same row-locked pattern.
                if ($keptPartIds->contains($li->part_id)) {
                    $delta = $newPartQty->get($li->part_id, 1) - $oldPartQty->get($li->part_id, 1);
                    if ($delta > 0) {
                        $locked = DB::table('parts_inventory')->where('id', $li->part_id)->lockForUpdate()->first();
                        if (!$locked || $locked->stock_qty < $delta) {
                            throw new \Exception("Stock for {$li->part_code} changed before this edit was saved — only " . ($locked->stock_qty ?? 0) . " more available.");
                        }
                        $remaining = $locked->stock_qty - $delta;
                        DB::table('parts_inventory')->where('id', $li->part_id)->update([
                            'stock_qty'  => $remaining,
                            'status'     => $remaining <= 0 ? 'Reserved' : 'Available',
                            'updated_at' => now(),
                        ]);
                    } elseif ($delta < 0) {
                        DB::table('parts_inventory')->where('id', $li->part_id)->increment('stock_qty', abs($delta));
                        DB::table('parts_inventory')->where('id', $li->part_id)
                            ->where('status', 'Reserved')
                            ->update(['status' => 'Available', 'updated_at' => now()]);
                    }
                }
            }

            DB::table('order_edit_log')->insert([
                'order_id'        => $id,
                'edited_by'       => Session::get('staff_name') ?? 'Unknown',
                'changes_summary' => $changesSummary,
                'created_at'      => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Could not save changes: ' . $e->getMessage());
        }

        return redirect()->route('admin.orders.show', $id)
            ->with('success', 'Order updated successfully. Changes have been logged.');
    }

    // =========================================================
    // GET /admin/orders/{id}/print
    // =========================================================
    public function printAdmin(int $id)
    {
        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) abort(404);

        $items = DB::table('order_items')->where('order_id', $id)->get();

        return view('admin.orders.print', compact('order', 'items'));
    }

    // =========================================================
    // GET /receipt/{order_ref} — PUBLIC receipt
    // =========================================================
    public function receiptPublic(string $orderRef)
    {
        $order = DB::table('orders')->where('order_ref', $orderRef)->first();
        if (!$order) abort(404, 'Receipt not found.');

        $items = DB::table('order_items')->where('order_id', $order->id)->get();

        return view('orders.receipt', compact('order', 'items'));
    }

    // =========================================================
    // POST /admin/orders/{id}/email-receipt
    // =========================================================
    public function emailReceipt(Request $request, int $id)
    {
        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) return response()->json(['success' => false, 'error' => 'Order not found.'], 404);

        if (!$order->customer_email) {
            return response()->json(['success' => false, 'error' => 'This customer has no email address on file.'], 422);
        }

        // FIXED: this method bypassed NotificationService entirely,
        // meaning it never checked whether the customer had unsubscribed
        // from email — a real compliance gap now that unsubscribe
        // actually exists. Kept as its own separate mechanism (per
        // your decision to keep both buttons), but now honors opt-out
        // the same way every other email in the app does.
        if ($order->customer_phone && \App\Services\NotificationService::isOptedOutPublic($order->customer_phone, 'email')) {
            return response()->json(['success' => false, 'error' => 'This customer has unsubscribed from email notifications.'], 422);
        }

        $items      = DB::table('order_items')->where('order_id', $id)->get();
        $receiptUrl = route('orders.receipt.public', $order->order_ref);

        try {
            Mail::to($order->customer_email)->send(new OrderReceiptMail($order, $items, $receiptUrl));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => 'Could not send email: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => "Receipt emailed to {$order->customer_email}."]);
    }

    // =========================================================
    // POST /admin/orders/{id}/confirm-payment
    // =========================================================
    // =========================================================
    // POST /admin/orders/{id}/payments
    // This route existed and pointed here, but this method never did —
    // every "Add Payment" attempt on an order was a hard crash
    // (undefined method). Built to mirror InvoiceController::addPayment()
    // exactly, using order_payments' REAL columns (confirmed via the
    // actual migration — amount_ngn, not amount_local; no created_by
    // column, same gap the invoice version had).
    // =========================================================
    public function addPayment(Request $request, int $id)
    {
        // FIXED: the actual form field is named "amount_local" (see
        // order-show.blade.php's Record a Payment form) — this was
        // validating/reading "amount_ngn" instead, a field that never
        // existed in the submitted request at all. That meant this
        // validation rule failed on EVERY submission, 100% of the
        // time, regardless of what was actually typed in — and
        // combined with this page having no error display at all
        // (fixed separately), it looked exactly like the button just
        // did nothing.
        $request->validate([
            'amount_local'   => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
        ]);

        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) abort(404);

        $proofPath = null;
        if ($request->hasFile('proof') && $request->file('proof')->isValid()) {
            $proofPath = $request->file('proof')->store('payment-proofs', 'public');
        }

        DB::table('order_payments')->insert([
            'order_id'       => $id,
            'amount_ngn'     => $request->amount_local,
            'payment_method' => $request->payment_method,
            'proof_path'     => $proofPath,
            // No "added by" column exists on this table (same gap as
            // invoice_payments) — kept in notes so it's not lost.
            'notes'          => trim(($request->notes ?? '') . ' [Added by: ' . (Session::get('staff_name') ?? 'Staff') . ']'),
            'status'         => 'pending',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return back()->with('success', 'Payment recorded as pending. Confirm it to reduce the balance.');
    }

    // =========================================================
    // POST /admin/orders/{id}/confirm-payment
    // =========================================================
    // =========================================================
    // POST /admin/orders/{id}/send-reminder
    // Route existed, method never did — same "undefined method"
    // crash pattern as addPayment() had. Uses the real
    // payment_reminders table (order_id, channel, sent_by_staff_id —
    // no updated_at column) confirmed from the actual migration.
    // Records one row per channel (SMS + Email), matching what the
    // button already says it does.
    //
    // NOTE: this only LOGS that a reminder was sent — it does not
    // actually dispatch an SMS or email yet. That's a separate,
    // larger integration (real SMS/email provider setup) — see the
    // broader email/WhatsApp request, which needs provider details
    // before it can be built.
    // =========================================================
    public function sendReminder(Request $request, int $id)
    {
        $order = DB::table('orders')->where('id', $id)->first();
        abort_if(!$order, 404);

        $summary    = self::paymentSummary($id);
        $currency   = ['NGN'=>'₦','USD'=>'$','GHS'=>'GH₵'][$order->currency_code ?? 'NGN'] ?? '₦';
        $balanceFmt = $currency . number_format($summary['balanceDue'] ?? 0);

        $message = "Hi {$order->customer_name}, this is a reminder that order {$order->order_ref} "
            . "has an outstanding balance of {$balanceFmt}. Please reach out to arrange payment. — Auto Zenith Parts";

        $emailHtml = "<p>Hi {$order->customer_name},</p>"
            . "<p>This is a reminder that order <strong>{$order->order_ref}</strong> has an outstanding balance of <strong>{$balanceFmt}</strong>.</p>"
            . "<p>Please reach out to arrange payment at your earliest convenience.</p>"
            . "<p>— Auto Zenith Parts</p>";

        $result = \App\Services\NotificationService::notify(
            ['email' => $order->customer_email, 'phone' => $order->customer_phone, 'name' => $order->customer_name],
            "Payment Reminder — Order {$order->order_ref}",
            $message,
            $emailHtml
        );

        $staffId = Session::get('staff_id');
        foreach (['email', 'sms'] as $channel) {
            DB::table('payment_reminders')->insert([
                'order_id'         => $id,
                'channel'          => $channel,
                'sent_by_staff_id' => $staffId,
                'created_at'       => now(),
            ]);
        }

        $statusMsg = $result['email']
            ? 'Payment reminder emailed.'
            : 'Could not send email (check customer has an email on file).';
        if ($result['whatsapp_link']) {
            $statusMsg .= ' WhatsApp link ready — click "Message customer" to send manually.';
        }

        return back()->with('success', $statusMsg)->with('whatsapp_reminder_link', $result['whatsapp_link']);
    }

    // =========================================================
    // POST /admin/orders/{id}/payments/{paymentId}/confirm
    // FIXED: this route (name: admin.orders.payments.confirm) already
    // existed pointing to 'confirmPaymentRecord' — but that method
    // never existed at all, meaning every attempt to confirm a
    // recorded order payment was a hard "undefined method" crash.
    // addPayment() records a payment as 'pending' (matching the
    // invoice pattern exactly), and paymentSummary()'s confirmedPaid
    // only ever sums status = 'confirmed' — so with no way to reach
    // this method, confirmed totals stayed at 0 forever regardless of
    // what staff recorded. This is that missing method, matching
    // InvoiceController::confirmPayment() exactly, plus fires the
    // Payment Received notification at the actual moment a specific
    // payment is confirmed.
    // =========================================================
    public function confirmPaymentRecord(int $id, int $paymentId)
    {
        DB::table('order_payments')
            ->where('id', $paymentId)
            ->where('order_id', $id)
            ->update([
                'status'                => 'confirmed',
                'confirmed_by_staff_id' => Session::get('staff_id'),
                'confirmed_at'          => now(),
                'updated_at'            => now(),
            ]);

        $order   = DB::table('orders')->where('id', $id)->first();
        $payment = DB::table('order_payments')->where('id', $paymentId)->first();
        if ($order && $payment) {
            $currencySym = ['NGN'=>'₦','USD'=>'$','GHS'=>'GH₵'][$order->currency_code ?? 'NGN'] ?? '₦';
            $amount      = $payment->amount_local ?? $payment->amount_ngn ?? $payment->amount_usd ?? 0;
            $amountFmt   = $currencySym . number_format($amount);
            $summary     = self::paymentSummary($id);
            $balanceFmt  = $currencySym . number_format($summary['balanceDue']);
            $stillOwed   = $summary['balanceDue'] > 0 ? " Remaining balance: {$balanceFmt}." : ' Order is now fully paid — thank you!';

            $message = "Hi {$order->customer_name}, we've received your payment of {$amountFmt} for order {$order->order_ref}.{$stillOwed} — Auto Zenith Parts";
            $emailHtml = "<p>Hi {$order->customer_name},</p>"
                . "<p>We've received your payment of <strong>{$amountFmt}</strong> for order <strong>{$order->order_ref}</strong>.</p>"
                . "<p>{$stillOwed}</p><p>— Auto Zenith Parts</p>";

            \App\Services\NotificationService::notify(
                ['email' => $order->customer_email, 'phone' => $order->customer_phone, 'name' => $order->customer_name],
                "Payment Received — Order {$order->order_ref}",
                $message,
                $emailHtml
            );
        }

        return back()->with('success', 'Payment confirmed.');
    }

    // =========================================================
    // POST /admin/orders/{id}/payments/{paymentId}/reject
    // FIXED: same gap as above — this route already existed pointing
    // to 'rejectPayment', which also never existed.
    // =========================================================
    public function rejectPayment(Request $request, int $id, int $paymentId)
    {
        $payment = DB::table('order_payments')->where('id', $paymentId)->where('order_id', $id)->first();
        abort_if(!$payment, 404);

        DB::table('order_payments')->where('id', $paymentId)->update([
            'status'     => 'rejected',
            'notes'      => trim(($payment->notes ?? '') . ' [Rejected: ' . ($request->reason ?? 'no reason given') . ']'),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Payment rejected.');
    }

    public function confirmPayment(Request $request, int $id)
    {
        $request->validate(['confirmed_by' => 'nullable|string|max:80']);

        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) abort(404);

        DB::table('orders')->where('id', $id)->update([
            'payment_status'       => 'confirmed',
            'order_status'         => 'confirmed',
            'payment_confirmed_at' => now(),
            'confirmed_by'         => $request->confirmed_by ?? Session::get('staff_name'),
            'updated_at'           => now(),
        ]);

        // ── Phase 4: fire PartSold for every part on this order ───────
        // Triggered when payment is confirmed — this is the moment
        // money actually changes hands, so that's when revenue
        // should be credited to the vehicle's ROI tracker.
        // Service items (no part_id) are skipped automatically.
        $items        = DB::table('order_items')->where('order_id', $id)->get();
        $currencyCode = $order->currency_code ?? 'NGN';

        foreach ($items as $item) {
            if ($item->part_id) {
                $revenue = $item->unit_price_local
                    ?? ($currencyCode === 'NGN' ? $item->unit_price_ngn : $item->unit_price_usd)
                    ?? 0;

                PartSold::dispatch(
                    $item->part_id,
                    $id,
                    (float) $revenue,
                    $currencyCode,
                    'order'
                );
            }
        }

        // ── Notification Idea: "Payment received" — order-level
        // confirmation, mirrors the invoice version. ──
        $currency  = ['NGN'=>'₦','USD'=>'$','GHS'=>'GH₵'][$currencyCode] ?? '₦';
        $totalFmt  = $currency . number_format($order->total_amount_local ?? $order->total_amount_ngn ?? $order->total_amount_usd ?? 0);
        if ($order->customer_email || $order->customer_phone) {
            $message = "Hi {$order->customer_name}, we've received your payment for order {$order->order_ref} — Total: {$totalFmt}. Thank you! — Auto Zenith Parts";
            $emailHtml = "<p>Hi {$order->customer_name},</p>"
                . "<p>We've received your payment for order <strong>{$order->order_ref}</strong> — Total: <strong>{$totalFmt}</strong>.</p>"
                . "<p>Thank you for your business!</p><p>— Auto Zenith Parts</p>";
            \App\Services\NotificationService::notify(
                ['email' => $order->customer_email, 'phone' => $order->customer_phone, 'name' => $order->customer_name],
                "Payment Received — Order {$order->order_ref}",
                $message,
                $emailHtml
            );
        }

        return response()->json(['success' => true, 'message' => 'Payment confirmed.']);
    }

    // =========================================================
    // POST /admin/orders/{id}/status
    // =========================================================
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'order_status' => 'required|in:confirmed,processing,ready_for_collection,shipped,completed,cancelled',
        ]);

        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) abort(404);

        $updateData = [
            'order_status' => $request->order_status,
            'staff_notes'  => $request->staff_notes,
            'updated_at'   => now(),
        ];

        // Auto-confirm payment when order progresses past awaiting state
        if ($request->order_status !== 'cancelled'
            && in_array($order->payment_status, ['awaiting_payment', 'pending', 'transfer_sent', 'payment_pending_confirmation'])) {
            $updateData['payment_status']        = 'confirmed';
            $updateData['payment_confirmed_at']  = now();
            $updateData['confirmed_by']          = Session::get('staff_name') ?? 'Admin';
        }

        DB::table('orders')->where('id', $id)->update($updateData);

        // Cancelled — release reserved parts.
        // FIXED (item E, cancellation stage): previously only flipped
        // status back to Available, with no idea how much stock to
        // actually restore (qty didn't exist on order_items yet). Now
        // adds back the real qty for this line, and only flips status
        // if the row had been fully depleted (status='Reserved') —
        // if other stock was still Available the whole time, nothing
        // needs to change beyond the quantity restore.
        if ($request->order_status === 'cancelled') {
            $items = DB::table('order_items')->where('order_id', $id)->get();
            foreach ($items as $item) {
                if (!$item->part_id) continue;
                $qty = (int) ($item->qty ?? 1);
                DB::table('parts_inventory')->where('id', $item->part_id)->increment('stock_qty', $qty);
                DB::table('parts_inventory')->where('id', $item->part_id)
                    ->where('status', 'Reserved')
                    ->update(['status' => 'Available', 'updated_at' => now()]);
            }
        }

        // Completed — mark parts as Sold + fire PartSold if not already
        // fired by confirmPayment (e.g. COD orders that skip that step)
        //
        // FIXED (item E, completion stage): previously flipped BOTH
        // 'Reserved' and 'Available' rows straight to 'Sold' regardless
        // of quantity — so if this order's line was a partial slice of
        // a multi-unit row (e.g. 2 of 20, with the other 18 legitimately
        // still Available from other stock), completing THIS order would
        // incorrectly mark the entire remaining 18 as Sold too.
        //
        // Under the qty-aware reserve step (AdminOrderController::store()
        // / OrderAdminController::update()), stock_qty was already
        // decremented at the moment this order reserved its units — a
        // row only reaches 'Reserved' once nothing is left. So completion
        // needs no further stock math: it only needs to flip a row that's
        // ALREADY fully depleted ('Reserved') over to the terminal 'Sold'
        // label. A row still sitting 'Available' means other stock
        // legitimately remains and must be left untouched.
        //
        // CAVEAT: orders reserved before this fix was deployed had their
        // whole row flipped to 'Reserved' without any stock_qty deduction
        // — completing one of those legacy orders will correctly flip
        // status to Sold here, but stock_qty on that row won't reflect
        // the sale. Reconcile any orders that were sitting in Reserved
        // status at deploy time by hand if this matters for your counts.
        if ($request->order_status === 'completed') {
            $items        = DB::table('order_items')->where('order_id', $id)->get();
            $currencyCode = $order->currency_code ?? 'NGN';

            foreach ($items as $item) {
                // Mark sold — only for rows this reservation actually
                // emptied. A part still 'Available' has other stock
                // remaining and is untouched.
                if ($item->part_id) {
                    DB::table('parts_inventory')
                        ->where('id', $item->part_id)
                        ->where('status', 'Reserved')
                        ->update(['status' => 'Sold', 'updated_at' => now()]);
                }

                // Only fire PartSold if this order hasn't already had
                // payment confirmed (avoids double-counting revenue).
                // We detect this by checking if payment was JUST auto-
                // confirmed above (was in awaiting state before update).
                $wasAwaitingPayment = in_array(
                    $order->payment_status,
                    ['awaiting_payment', 'pending', 'transfer_sent', 'payment_pending_confirmation']
                );

                if ($item->part_id && $wasAwaitingPayment) {
                    $revenue = $item->unit_price_local
                        ?? ($currencyCode === 'NGN' ? $item->unit_price_ngn : $item->unit_price_usd)
                        ?? 0;

                    PartSold::dispatch(
                        $item->part_id,
                        $id,
                        (float) $revenue,
                        $currencyCode,
                        'order'
                    );
                }
            }
        }

        // ── Notification Ideas: "Order shipped" and "Delivery
        // confirmation" — fire on the specific status transitions
        // that mean those things, using the same real-send service
        // as every other event wired today. ──
        if (in_array($request->order_status, ['shipped', 'completed']) && ($order->customer_email || $order->customer_phone)) {
            $isShipped = $request->order_status === 'shipped';
            $subject = $isShipped
                ? "Your Order Has Shipped — {$order->order_ref}"
                : "Order Delivered — {$order->order_ref}";
            $message = $isShipped
                ? "Hi {$order->customer_name}, your order {$order->order_ref} is on its way! We'll let you know once it's delivered. — Auto Zenith Parts"
                : "Hi {$order->customer_name}, your order {$order->order_ref} has been marked delivered. Thank you for your business! — Auto Zenith Parts";
            $emailHtml = $isShipped
                ? "<p>Hi {$order->customer_name},</p><p>Your order <strong>{$order->order_ref}</strong> is on its way!</p><p>We'll let you know once it's delivered.</p><p>— Auto Zenith Parts</p>"
                : "<p>Hi {$order->customer_name},</p><p>Your order <strong>{$order->order_ref}</strong> has been marked delivered.</p><p>Thank you for your business!</p><p>— Auto Zenith Parts</p>";

            \App\Services\NotificationService::notify(
                ['email' => $order->customer_email, 'phone' => $order->customer_phone, 'name' => $order->customer_name],
                $subject,
                $message,
                $emailHtml
            );
        }

        return response()->json(['success' => true]);
    }

    // =========================================================
    // PAYMENT SUMMARY — static, called from invoice show blade
    // =========================================================
    public static function paymentSummary(int $orderId): array
    {
        $order = DB::table('orders')->where('id', $orderId)->first();
        if (!$order) return ['payments' => collect(), 'confirmedPaid' => 0, 'balanceDue' => 0, 'currencyCode' => 'USD'];
        $currencyCode = $order->currency_code ?? ($order->total_amount_ngn ? 'NGN' : 'USD');
        $payments = DB::table('order_payments')
            ->where('order_id', $orderId)
            ->orderBy('created_at')
            ->get()
            ->map(function ($p) {
                $p->amount_local = $p->amount_local ?? $p->amount_ngn ?? $p->amount_usd ?? 0;
                return $p;
            });
        $confirmedPaid = $payments->where('status', 'confirmed')->sum('amount_local');
        $total         = $order->total_amount_local ?? $order->total_amount_ngn ?? $order->total_amount_usd ?? 0;
        $balanceDue    = max(0, $total - $confirmedPaid);
        return compact('payments', 'confirmedPaid', 'balanceDue', 'currencyCode');
    }

    // =========================================================
    // DELETE /admin/orders/{id}
    // =========================================================
    public function destroy(Request $request, int $id)
    {
        if (!in_array(Session::get('staff_role'), ['admin', 'manager'])) {
            abort(403);
        }

        DB::table('orders')->where('id', $id)->update([
            'deleted_at' => now(),
            'updated_at' => now(),
        ]);

        // Release any parts still marked Reserved for this order.
        // FIXED: same qty-restore fix as the cancelled branch in
        // updateStatus() above — adds back the real quantity, only
        // flips status if this row had been fully depleted.
        $items = DB::table('order_items')->where('order_id', $id)->get();
        foreach ($items as $item) {
            if ($item->part_id) {
                $qty = (int) ($item->qty ?? 1);
                DB::table('parts_inventory')->where('id', $item->part_id)->increment('stock_qty', $qty);
                DB::table('parts_inventory')->where('id', $item->part_id)
                    ->where('status', 'Reserved')
                    ->update(['status' => 'Available', 'updated_at' => now()]);
            }
        }

        $returnTo = $request->input('return_to', route('admin.invoices.index'));
        return redirect($returnTo)->with('success', 'Order moved to recycle bin.');
    }
}
