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
        ]);

        $oldItems   = DB::table('order_items')->where('order_id', $id)->get();
        $oldPartIds = $oldItems->where('item_type', 'part')->pluck('part_id')->filter()->values();
        $newPartIds = collect($request->items)->where('item_type', 'part')->pluck('id')->values();

        // Release removed parts
        $removedPartIds = $oldPartIds->diff($newPartIds);
        if ($removedPartIds->isNotEmpty()) {
            DB::table('parts_inventory')
                ->whereIn('id', $removedPartIds)
                ->where('status', 'Reserved')
                ->update(['status' => 'Available', 'updated_at' => now()]);
        }

        // Check newly added parts
        $addedPartIds = $newPartIds->diff($oldPartIds);
        $stockErrors  = [];
        foreach ($addedPartIds as $partId) {
            $part = DB::table('parts_inventory')->where('id', $partId)->first();
            if (!$part) { $stockErrors[] = "Part #{$partId} no longer exists."; continue; }
            if ($part->status !== 'Available') $stockErrors[] = "{$part->part_code} ({$part->part_name}) is no longer Available.";
        }
        if (!empty($stockErrors)) {
            return back()->withInput()->with('error', 'Cannot save changes — ' . implode(' | ', $stockErrors));
        }

        $lineItems = collect($request->items)->map(function ($item) use ($order) {
            if ($item['item_type'] === 'part') {
                $part = DB::table('parts_inventory')->where('id', $item['id'])->first();
                return $part ? (object)[
                    'type' => 'part', 'part_id' => $part->id, 'service_id' => null,
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
                'type' => 'service', 'part_id' => null, 'service_id' => $service->id,
                'part_name' => $service->name, 'part_code' => $service->service_code,
                'brand' => $service->category, 'model' => null,
                'year_from' => null, 'year_to' => null,
                'condition_grade' => null, 'location' => $resolvedLocation,
                'unit_price_local' => $priced['price'], 'currency_code' => $priced['currency_code'],
            ];
        })->filter()->values();

        $newTotalLocal = $lineItems->sum('unit_price_local');
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
                    'part_name'        => $li->part_name,
                    'part_code'        => $li->part_code,
                    'brand'            => $li->brand,
                    'model'            => $li->model,
                    'year_from'        => $li->year_from,
                    'year_to'          => $li->year_to,
                    'condition_grade'  => $li->condition_grade,
                    'location'         => $li->location,
                    'unit_price_local' => $li->unit_price_local,
                    'subtotal_local'   => $li->unit_price_local,
                    'unit_price_ngn'   => $li->currency_code === 'NGN' ? round($li->unit_price_local) : null,
                    'unit_price_usd'   => $li->currency_code === 'USD' ? round($li->unit_price_local, 2) : null,
                    'subtotal_ngn'     => $li->currency_code === 'NGN' ? round($li->unit_price_local) : null,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);

                if ($li->part_id && in_array($li->part_id, $addedPartIds->all())) {
                    DB::table('parts_inventory')->where('id', $li->part_id)->update([
                        'status' => 'Reserved', 'updated_at' => now(),
                    ]);
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
        $request->validate([
            'amount_ngn'     => 'required|numeric|min:0.01',
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
            'amount_ngn'     => $request->amount_ngn,
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

        // Cancelled — release reserved parts
        if ($request->order_status === 'cancelled') {
            $items = DB::table('order_items')->where('order_id', $id)->get();
            foreach ($items as $item) {
                DB::table('parts_inventory')
                    ->where('id', $item->part_id)
                    ->where('status', 'Reserved')
                    ->update(['status' => 'Available', 'updated_at' => now()]);
            }
        }

        // Completed — mark parts as Sold + fire PartSold if not already
        // fired by confirmPayment (e.g. COD orders that skip that step)
        if ($request->order_status === 'completed') {
            $items        = DB::table('order_items')->where('order_id', $id)->get();
            $currencyCode = $order->currency_code ?? 'NGN';

            foreach ($items as $item) {
                // Mark sold
                if ($item->part_id) {
                    DB::table('parts_inventory')
                        ->where('id', $item->part_id)
                        ->whereIn('status', ['Reserved', 'Available'])
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

        // Release any parts still marked Reserved for this order
        $items = DB::table('order_items')->where('order_id', $id)->get();
        foreach ($items as $item) {
            if ($item->part_id) {
                DB::table('parts_inventory')
                    ->where('id', $item->part_id)
                    ->where('status', 'Reserved')
                    ->update(['status' => 'Available', 'updated_at' => now()]);
            }
        }

        $returnTo = $request->input('return_to', route('admin.invoices.index'));
        return redirect($returnTo)->with('success', 'Order moved to recycle bin.');
    }
}
