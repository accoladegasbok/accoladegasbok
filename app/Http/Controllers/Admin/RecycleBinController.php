<?php
// FILE: app/Http/Controllers/Admin/RecycleBinController.php
//
// Improvement #2 — soft-deleted invoices AND orders previously just
// vanished with no way to see, restore, or permanently purge them.
// This gives one unified recycle bin covering both tables, since they
// already share the identical deleted_at + deleted_by_staff_id pattern.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class RecycleBinController extends Controller
{
    // =========================================================
    // GET /admin/recycle-bin — merged list of soft-deleted
    // invoices + orders, newest-deleted first.
    // =========================================================
    public function index(Request $request)
    {
        $deletedInvoices = DB::table('invoices as i')
            ->leftJoin('staff as s', 's.id', '=', 'i.deleted_by_staff_id')
            ->whereNotNull('i.deleted_at')
            ->select('i.id', 'i.invoice_no as ref', 'i.customer_name', 'i.customer_phone',
                     'i.subtotal_local as amount_local', 'i.currency_code', 'i.location',
                     'i.invoice_type', 'i.deleted_at', 's.name as deleted_by_name')
            ->get()
            ->map(fn($r) => (object)[
                'type'            => 'invoice',
                'id'              => $r->id,
                'ref'             => $r->ref,
                'customer_name'   => $r->customer_name,
                'customer_phone'  => $r->customer_phone,
                'amount_local'    => $r->amount_local,
                'currency_code'   => $r->currency_code,
                'location'        => $r->location,
                'label'           => ucfirst($r->invoice_type ?? 'parts') . ' Invoice',
                'deleted_at'      => $r->deleted_at,
                'deleted_by_name' => $r->deleted_by_name ?? 'Unknown',
            ]);

        $deletedOrders = DB::table('orders as o')
            ->leftJoin('staff as s', 's.id', '=', 'o.deleted_by_staff_id')
            ->whereNotNull('o.deleted_at')
            ->select('o.id', 'o.order_ref as ref', 'o.customer_name', 'o.customer_phone',
                     'o.total_amount_local', 'o.total_amount_ngn', 'o.total_amount_usd',
                     'o.currency_code', 'o.customer_country', 'o.deleted_at', 's.name as deleted_by_name')
            ->get()
            ->map(fn($r) => (object)[
                'type'            => 'order',
                'id'              => $r->id,
                'ref'             => $r->ref,
                'customer_name'   => $r->customer_name,
                'customer_phone'  => $r->customer_phone,
                'amount_local'    => $r->total_amount_local ?? $r->total_amount_ngn ?? $r->total_amount_usd ?? 0,
                'currency_code'   => $r->currency_code ?? 'USD',
                'location'        => $r->customer_country,
                'label'           => 'Order',
                'deleted_at'      => $r->deleted_at,
                'deleted_by_name' => $r->deleted_by_name ?? 'Unknown',
            ]);

        $all = $deletedInvoices->concat($deletedOrders)
            ->sortByDesc('deleted_at')
            ->values();

        // Simple in-memory pagination, same pattern as InvoiceController::index()
        $perPage = 25;
        $page    = (int) $request->get('page', 1);
        $total   = $all->count();
        $items   = $all->slice(($page - 1) * $perPage, $perPage)->values();

        $deletedItems = new \Illuminate\Pagination\LengthAwarePaginator(
            $items, $total, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.recycle-bin.index', compact('deletedItems'));
    }

    // =========================================================
    // POST /admin/recycle-bin/{type}/{id}/restore — admin/manager only.
    // Clears deleted_at/deleted_by_staff_id, returning the row to
    // normal active lists.
    // =========================================================
    public function restore(Request $request, string $type, int $id)
    {
        if (!in_array(Session::get('staff_role'), ['admin', 'manager'])) {
            abort(403, 'Only admin or manager accounts can restore deleted items.');
        }

        $table = $type === 'order' ? 'orders' : 'invoices';

        DB::table($table)->where('id', $id)->update([
            'deleted_at'          => null,
            'deleted_by_staff_id' => null,
        ]);

        return back()->with('success', ucfirst($type) . ' restored.');
    }

    // =========================================================
    // DELETE /admin/recycle-bin/{type}/{id} — ADMIN ONLY. This is a
    // genuine, permanent hard delete — the whole point of the recycle
    // bin is that THIS is the only place data actually disappears for
    // good, and only admin can pull that trigger, on a row that's
    // already been soft-deleted once (so it's had a chance to be
    // noticed/restored first).
    // =========================================================
    public function forceDelete(Request $request, string $type, int $id)
    {
        if (Session::get('staff_role') !== 'admin') {
            abort(403, 'Only admin accounts can permanently delete records.');
        }

        if ($type === 'order') {
            DB::table('order_items')->where('order_id', $id)->delete();
            DB::table('order_payments')->where('order_id', $id)->delete();
            DB::table('payment_reminders')->where('order_id', $id)->delete();
            DB::table('orders')->where('id', $id)->whereNotNull('deleted_at')->delete();
        } else {
            DB::table('invoice_items')->where('invoice_id', $id)->delete();
            DB::table('invoice_payments')->where('invoice_id', $id)->delete();
            DB::table('invoice_payment_reminders')->where('invoice_id', $id)->delete();
            DB::table('invoice_edit_log')->where('invoice_id', $id)->delete();
            DB::table('invoices')->where('id', $id)->whereNotNull('deleted_at')->delete();
        }

        return back()->with('success', ucfirst($type) . ' permanently deleted.');
    }

    // =========================================================
    // POST /admin/recycle-bin/bulk-restore — admin/manager only.
    // =========================================================
    public function bulkRestore(Request $request)
    {
        if (!in_array(Session::get('staff_role'), ['admin', 'manager'])) {
            return response()->json(['success' => false, 'error' => 'Only admin or manager accounts can restore items.'], 403);
        }

        $request->validate([
            'items'        => 'required|array|min:1',
            'items.*.type' => 'required|in:invoice,order',
            'items.*.id'   => 'required|integer',
        ]);

        foreach ($request->items as $item) {
            $table = $item['type'] === 'order' ? 'orders' : 'invoices';
            DB::table($table)->where('id', $item['id'])->update([
                'deleted_at'          => null,
                'deleted_by_staff_id' => null,
            ]);
        }

        return response()->json(['success' => true, 'message' => count($request->items) . ' item(s) restored.']);
    }

    // =========================================================
    // POST /admin/recycle-bin/bulk-force-delete — ADMIN ONLY.
    // =========================================================
    public function bulkForceDelete(Request $request)
    {
        if (Session::get('staff_role') !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Only admin accounts can permanently delete records.'], 403);
        }

        $request->validate([
            'items'        => 'required|array|min:1',
            'items.*.type' => 'required|in:invoice,order',
            'items.*.id'   => 'required|integer',
        ]);

        foreach ($request->items as $item) {
            if ($item['type'] === 'order') {
                DB::table('order_items')->where('order_id', $item['id'])->delete();
                DB::table('order_payments')->where('order_id', $item['id'])->delete();
                DB::table('payment_reminders')->where('order_id', $item['id'])->delete();
                DB::table('orders')->where('id', $item['id'])->whereNotNull('deleted_at')->delete();
            } else {
                DB::table('invoice_items')->where('invoice_id', $item['id'])->delete();
                DB::table('invoice_payments')->where('invoice_id', $item['id'])->delete();
                DB::table('invoice_payment_reminders')->where('invoice_id', $item['id'])->delete();
                DB::table('invoice_edit_log')->where('invoice_id', $item['id'])->delete();
                DB::table('invoices')->where('id', $item['id'])->whereNotNull('deleted_at')->delete();
            }
        }

        return response()->json(['success' => true, 'message' => count($request->items) . ' item(s) permanently deleted.']);
    }
}
