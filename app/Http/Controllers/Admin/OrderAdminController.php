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
        $query = DB::table('orders')->orderByDesc('created_at');

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

        $todayRevenue = DB::table('orders')
            ->whereDate('created_at', today())
            ->where('payment_status', 'confirmed')
            ->sum('total_amount_ngn');

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

        DB::table('orders')->where('id', $id)->update([
            'order_status' => $request->order_status,
            'staff_notes'  => $request->staff_notes,
            'updated_at'   => now(),
        ]);

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
}
