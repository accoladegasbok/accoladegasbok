<?php
// FILE: app/Http/Controllers/Admin/OrderAdminController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderReceiptMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class OrderAdminController extends Controller
{
    // =========================================================
    // GET /admin/orders — paginated order list with filters
    // =========================================================
    public function index(Request $request)
    {
        $query = DB::table('orders')->whereNull('deleted_at')->orderByDesc('created_at');

        // Filters
        if ($f = $request->get('status')) {
            $query->where('order_status', $f);
        }
        if ($f = $request->get('payment')) {
            $query->where('payment_status', $f);
        }
        if ($f = $request->get('method')) {
            $query->where('payment_method', $f);
        }
        if ($f = $request->get('q')) {
            $query->where(function ($q) use ($f) {
                $q->where('order_ref',      'like', "%{$f}%")
                  ->orWhere('customer_name', 'like', "%{$f}%")
                  ->orWhere('customer_phone','like', "%{$f}%");
            });
        }

        $orders = $query->paginate(25)->withQueryString();

        // Summary counts for the filter badges
        $counts = DB::table('orders')
            ->select('payment_status', DB::raw('count(*) as n'))
            ->groupBy('payment_status')
            ->pluck('n', 'payment_status');

        // Per-currency, never blended — an order in USD and an order in
        // NGN can't be summed into one meaningful number.
        $todayRevenue = DB::table('orders')
            ->whereDate('created_at', today())
            ->where('payment_status', 'confirmed')
            ->select('currency_code', DB::raw('SUM(COALESCE(total_amount_local, total_amount_ngn, total_amount_usd)) as total'))
            ->groupBy('currency_code')
            ->pluck('total', 'currency_code');

        return view('admin.orders.index', compact('orders', 'counts', 'todayRevenue'));
    }

    // =========================================================
    // GET /admin/orders/{id} — order detail view
    // =========================================================
    public function show(int $id)
    {
        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) abort(404);

        $items = DB::table('order_items')
            ->where('order_id', $id)
            ->get();

        return view('admin.orders.show', compact('order', 'items'));
    }

    // =========================================================
    // GET /admin/orders/{id}/print — ADMIN-ONLY quadruplicate print
    // layout (Customer / Office / Accounts / Store Room copies),
    // matching the existing invoice print system. The public
    // single-copy receipt (receiptPublic below) stays separate and
    // is the only one ever sent to customers via email/WhatsApp.
    // =========================================================
    public function printAdmin(int $id)
    {
        $order = DB::table('orders')->where('id', $id)->first();
        if (!$order) abort(404);

        $items = DB::table('order_items')->where('order_id', $id)->get();

        return view('admin.orders.print', compact('order', 'items'));
    }

    // =========================================================
    // GET /receipt/{order_ref} — PUBLIC printable receipt.
    // No login required — this is the link sent via email/WhatsApp.
    // Looked up by order_ref (not numeric id) so it's not trivially
    // guessable by incrementing a number in the URL.
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
    public function confirmPayment(Request $request, int $id)
    {
        $request->validate(['confirmed_by' => 'nullable|string|max:80']);

        DB::table('orders')->where('id', $id)->update([
            'payment_status'       => 'confirmed',
            'order_status'         => 'confirmed',
            'payment_confirmed_at' => now(),
            'confirmed_by'         => $request->confirmed_by ?? Session::get('staff_name'),
            'updated_at'           => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Payment confirmed.']);
    }

    // =========================================================
    // POST /admin/orders/{id}/status — update order status
    // =========================================================
    public function updateStatus(Request $request, int $id)
    {
        $request->validate([
            'order_status' => 'required|in:confirmed,processing,ready_for_collection,shipped,completed,cancelled',
        ]);

        $order = DB::table('orders')->where('id', $id)->first();

        $updateData = [
            'order_status' => $request->order_status,
            'staff_notes'  => $request->staff_notes,
            'updated_at'   => now(),
        ];

        // ── Any forward progress past "awaiting payment" implies
        // payment was actually received — fixes payment_status
        // getting permanently stuck at "awaiting_payment" even after
        // an order is marked completed, which is what was happening
        // before this fix (staff could advance order_status without
        // ever explicitly confirming payment first).
        if ($request->order_status !== 'cancelled'
            && in_array($order->payment_status, ['awaiting_payment', 'pending', 'transfer_sent', 'payment_pending_confirmation'])) {
            $updateData['payment_status'] = 'confirmed';
            $updateData['payment_confirmed_at'] = now();
            $updateData['confirmed_by'] = Session::get('staff_name') ?? 'Admin';
        }

        DB::table('orders')->where('id', $id)->update($updateData);

        // If cancelled — release reserved parts back to Available
        if ($request->order_status === 'cancelled') {
            $items = DB::table('order_items')->where('order_id', $id)->get();
            foreach ($items as $item) {
                DB::table('parts_inventory')
                    ->where('id', $item->part_id)
                    ->where('status', 'Reserved')
                    ->update(['status' => 'Available', 'updated_at' => now()]);
            }
        }

        // If completed — mark parts as Sold
        if ($request->order_status === 'completed') {
            $items = DB::table('order_items')->where('order_id', $id)->get();
            foreach ($items as $item) {
                DB::table('parts_inventory')
                    ->where('id', $item->part_id)
                    ->update(['status' => 'Sold', 'updated_at' => now()]);
            }
        }

        return response()->json(['success' => true]);
    }

    // =========================================================
    // POST /admin/orders/{id}/cancel
    // =========================================================
    public function cancel(Request $request, int $id)
    {
        return $this->updateStatus(
            $request->merge(['order_status' => 'cancelled']),
            $id
        );
    }

    // =========================================================
    // DELETE /admin/orders/{id} — Admin deletes directly; Staff/
    // Supervisor require a logged Supervisor-or-above PIN approval.
    // Soft-delete only (deleted_at), same as invoices — financial
    // records are never hard-deleted, only hidden from normal views,
    // preserving the full audit trail.
    // =========================================================
    public function destroy(Request $request, int $id)
    {
        $role = Session::get('staff_role');

        if ($role !== 'admin') {
            $request->validate(['override_token' => 'required|string']);
            $validApproval = DB::table('override_logs')
                ->where('action', 'delete_order')
                ->where('context', 'like', "%order #{$id}%")
                ->where('requested_by_staff_id', Session::get('staff_id'))
                ->where('created_at', '>=', now()->subMinutes(5))
                ->whereNotIn('approved_by_role', ['UNKNOWN'])
                ->exists();

            if (!$validApproval) {
                return back()->with('error', 'A valid Supervisor/Manager/Admin PIN approval is required to delete an order.');
            }
        }

        DB::table('orders')->where('id', $id)->update([
            'deleted_at'          => now(),
            'deleted_by_staff_id' => Session::get('staff_id'),
        ]);

        return redirect()->route('admin.orders.index')->with('success', 'Order deleted.');
    }

    // =========================================================
    // PARTIAL / MULTIPLE PAYMENTS — an order can be paid off across
    // several payments, each with its own method and an optional
    // uploaded proof (bank transfer screenshot, receipt, etc).
    // Confirmed payments accumulate against the order total; the
    // remaining balance is always order.total_amount_ngn minus the
    // sum of CONFIRMED payments only (pending ones don't count yet).
    // =========================================================

    // GET /admin/orders/{id} already shows the order — this just
    // recalculates the running balance, used by the show() view.
    public static function paymentSummary(int $orderId): array
    {
        $order = DB::table('orders')->where('id', $orderId)->first();
        $payments = DB::table('order_payments')->where('order_id', $orderId)->orderByDesc('created_at')->get();

        $confirmedPaid = $payments->where('status', 'confirmed')->sum('amount_local');
        $pendingTotal  = $payments->where('status', 'pending')->sum('amount_local');
        $orderTotal    = $order->total_amount_local ?? $order->total_amount_ngn ?? $order->total_amount_usd ?? 0;
        $balanceDue    = max(0, $orderTotal - $confirmedPaid);

        return [
            'payments'      => $payments,
            'confirmedPaid' => $confirmedPaid,
            'pendingTotal'  => $pendingTotal,
            'balanceDue'    => $balanceDue,
            'currencyCode'  => $order->currency_code ?? 'NGN',
        ];
    }

    // POST /admin/orders/{id}/payments — record a new (partial or full) payment
    public function addPayment(Request $request, int $id)
    {
        $request->validate([
            'amount_local'   => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:50',
            'proof'          => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:8192',
            'notes'          => 'nullable|string|max:500',
        ]);

        $order = DB::table('orders')->where('id', $id)->first();
        abort_if(!$order, 404);

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store("order-payments/{$id}", 'public');
        }

        // Payment is recorded in the ORDER's own real currency — never
        // converted, never assumed to be NGN.
        $currencyCode = $order->currency_code ?? 'NGN';

        DB::table('order_payments')->insert([
            'order_id'       => $id,
            'amount_local'   => $request->amount_local,
            'currency_code'  => $currencyCode,
            'amount_ngn'     => $currencyCode === 'NGN' ? $request->amount_local : null, // kept for any legacy reads
            'payment_method' => $request->payment_method,
            'proof_path'     => $proofPath,
            'status'         => 'pending', // requires staff confirmation before it counts toward balance
            'notes'          => $request->notes,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return back()->with('success', 'Payment recorded — pending confirmation.');
    }

    // POST /admin/orders/{id}/payments/{paymentId}/confirm — staff
    // verifies the proof and confirms the payment actually came in.
    // This is what actually reduces the outstanding balance.
    public function confirmPaymentRecord(Request $request, int $id, int $paymentId)
    {
        DB::table('order_payments')->where('id', $paymentId)->where('order_id', $id)->update([
            'status'                 => 'confirmed',
            'confirmed_by_staff_id'  => Session::get('staff_id'),
            'confirmed_at'           => now(),
            'updated_at'             => now(),
        ]);

        $order = DB::table('orders')->where('id', $id)->first();
        $summary = self::paymentSummary($id);
        $newPaymentStatus = $summary['balanceDue'] <= 0 ? 'confirmed' : ($summary['confirmedPaid'] > 0 ? 'partial' : 'awaiting_payment');

        // ── payment_status reflects payment progress (this drives the
        // visible badge on the orders list). order_status is a
        // SEPARATE fulfillment-stage field (Processing, Shipped, etc)
        // with its own fixed dropdown that doesn't include "partial"
        // — it should only ever auto-advance once fully paid (moving
        // it from "Awaiting Payment" into "Confirmed"), and otherwise
        // stay exactly as staff left it.
        $newOrderStatus = $order->order_status;
        if ($newPaymentStatus === 'confirmed' && in_array($order->order_status, ['awaiting_payment', 'payment_pending_confirmation', 'pending'])) {
            $newOrderStatus = 'confirmed';
        }

        DB::table('orders')->where('id', $id)->update([
            'payment_status'        => $newPaymentStatus,
            'order_status'          => $newOrderStatus,
            'payment_confirmed_at'  => $newPaymentStatus === 'confirmed' ? now() : null,
            'confirmed_by'          => $newPaymentStatus === 'confirmed' ? (Session::get('staff_name') ?? 'Admin') : null,
            'updated_at'            => now(),
        ]);

        return back()->with('success', 'Payment confirmed. ' . ($summary['balanceDue'] <= 0 ? 'Order is now fully paid.' : 'Balance updated — order marked partial.'));
    }

    public function rejectPayment(int $id, int $paymentId)
    {
        DB::table('order_payments')->where('id', $paymentId)->where('order_id', $id)->update([
            'status' => 'rejected', 'updated_at' => now(),
        ]);
        return back()->with('success', 'Payment marked as rejected.');
    }

    // POST /admin/orders/{id}/send-reminder — SMS + email reminder
    // for the outstanding balance. Logged so staff can see reminder
    // history and avoid spamming the same customer repeatedly.
    public function sendReminder(int $id)
    {
        $order = DB::table('orders')->where('id', $id)->first();
        abort_if(!$order, 404);

        $summary = self::paymentSummary($id);
        if ($summary['balanceDue'] <= 0) {
            return back()->with('error', 'This order has no outstanding balance.');
        }

        $balanceFmt = '₦' . number_format($summary['balanceDue']);
        $message = "Hi {$order->customer_name}, this is Auto Zenith Parts. Your order {$order->order_ref} has an outstanding balance of {$balanceFmt}. Please complete payment at your earliest convenience.";

        if ($order->customer_phone) {
            app(\App\Services\SmsService::class)->send($order->customer_phone, $message);
            DB::table('payment_reminders')->insert([
                'order_id' => $id, 'channel' => 'sms',
                'sent_by_staff_id' => Session::get('staff_id'), 'created_at' => now(),
            ]);
        }

        if ($order->customer_email) {
            try {
                Mail::to($order->customer_email)->send(new \App\Mail\PaymentReminderMail($order, $summary['balanceDue']));
                DB::table('payment_reminders')->insert([
                    'order_id' => $id, 'channel' => 'email',
                    'sent_by_staff_id' => Session::get('staff_id'), 'created_at' => now(),
                ]);
            } catch (\Exception $e) { /* logged by mail config */ }
        }

        return back()->with('success', 'Payment reminder sent.');
    }
}
