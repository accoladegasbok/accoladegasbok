<?php
// FILE: app/Http/Controllers/Admin/CustomerCreditAdminController.php
//
// Staff view of customer credit: who has credit, the full history, paying credit out as cash or
// transfer, and manual adjustments. Anything that moves money needs supervisor or above.
// Every action is a ledger row, so the history is also the audit log.

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CustomerCreditService as Credit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerCreditAdminController extends Controller
{
    // GET /admin/customer-credit/lookup?phone=&currency=   (any signed-in staff — used by the invoice form)
    public function lookup(Request $request)
    {
        $currency = strtoupper((string) $request->get('currency', 'NGN'));
        return response()->json([
            'balance'   => Credit::balance($request->get('phone'), $currency),
            'currency'  => $currency,
            'can_apply' => Credit::canManage(),
        ]);
    }

    // GET /admin/customer-credit
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $query = DB::table('customer_credit_ledger')
            ->select('phone_key', 'currency_code',
                DB::raw('MAX(customer_name) as customer_name'),
                DB::raw('ROUND(SUM(amount_local), 2) as balance'),
                DB::raw('MAX(created_at) as last_activity'))
            ->groupBy('phone_key', 'currency_code');

        if ($q !== '') {
            $digits = preg_replace('/\D/', '', $q);
            $query->where(function ($w) use ($q, $digits) {
                $w->where('customer_name', 'like', "%{$q}%");
                if ($digits !== '') $w->orWhere('phone_key', 'like', '%' . substr($digits, -10) . '%');
            });
        }
        if (!$request->boolean('all')) $query->havingRaw('SUM(amount_local) > 0');

        return view('admin.customer-credit.index', [
            'customers'  => $query->orderByDesc('last_activity')->paginate(30)->withQueryString(),
            'q'          => $q,
            'canManage'  => Credit::canManage(),
            'totals'     => DB::table('customer_credit_ledger')->select('currency_code', DB::raw('ROUND(SUM(amount_local), 2) as bal'))
                                ->groupBy('currency_code')->having('bal', '>', 0)->pluck('bal', 'currency_code'),
        ]);
    }

    // GET /admin/customer-credit/{phoneKey}
    public function show(string $phoneKey)
    {
        $entries = DB::table('customer_credit_ledger')->where('phone_key', $phoneKey)->orderByDesc('id')->get();
        abort_if($entries->isEmpty(), 404);

        return view('admin.customer-credit.show', [
            'phoneKey'  => $phoneKey,
            'entries'   => $entries,
            'name'      => $entries->pluck('customer_name')->filter()->first(),
            'balances'  => $entries->groupBy('currency_code')->map(fn ($g) => round($g->sum('amount_local'), 2)),
            'canManage' => Credit::canManage(),
        ]);
    }

    // POST /admin/customer-credit/{phoneKey}/payout — hand credit back as cash or transfer
    public function payout(Request $request, string $phoneKey)
    {
        abort_unless(Credit::canManage(), 403, 'Supervisor and above only.');

        $data = $request->validate([
            'currency'  => 'required|in:NGN,GHS,USD',
            'amount'    => 'required|numeric|min:0.01',
            'method'    => 'required|in:cash,transfer',
            'reference' => 'nullable|string|max:60',
            'notes'     => 'nullable|string|max:500',
        ]);

        $last = DB::table('customer_credit_ledger')->where('phone_key', $phoneKey)->orderByDesc('id')->first();
        abort_if(!$last, 404);

        try {
            Credit::spend([
                'phone' => $phoneKey, 'name' => $last->customer_name, 'email' => $last->customer_email,
                'currency' => $data['currency'], 'amount' => $data['amount'],
                'type' => 'payout_' . $data['method'], 'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('success', 'Credit paid out as ' . $data['method'] . ' and logged.');
    }

    // POST /admin/customer-credit/recheck — look at ONE invoice or order again and add any overpayment
    // that was not credited yet (e.g. the credit check could not run when the payment was confirmed).
    // Safe to run more than once: it only ever adds what is still missing.
    public function recheck(Request $request)
    {
        abort_unless(Credit::canManage(), 403, 'Supervisor and above only.');
        $data = $request->validate(['reference' => 'required|string|max:60']);
        $ref  = trim($data['reference']);

        $invoice = DB::table('invoices')->where('invoice_no', $ref)->first();
        if ($invoice) {
            $added = Credit::syncOverpayment('invoice', $invoice->id);
            $cur   = $invoice->currency_code ?? 'NGN';
        } else {
            $order = DB::table('orders')->where('order_ref', $ref)->first();
            if (!$order) return back()->with('error', "No invoice or order found with the reference {$ref}.");
            $added = Credit::syncOverpayment('order', $order->id);
            $cur   = $order->currency_code ?? 'NGN';
        }

        return back()->with('success', $added > 0
            ? 'Added ' . InvoiceController::formatLocal($added, $cur) . " of overpayment credit for {$ref}."
            : "Nothing to add for {$ref}: it is not overpaid, the overpayment was already credited, or the sale has no customer phone number.");
    }

    // POST /admin/customer-credit/adjust — add (or remove) credit by hand, with a reason
    public function adjust(Request $request)
    {
        abort_unless(Credit::canManage(), 403, 'Supervisor and above only.');

        $data = $request->validate([
            'phone'    => 'required|string|max:40',
            'name'     => 'nullable|string|max:120',
            'currency' => 'required|in:NGN,GHS,USD',
            'amount'   => 'required|numeric|not_in:0',
            'notes'    => 'required|string|max:500',
        ]);

        if (Credit::phoneKey($data['phone']) === '') {
            return back()->with('error', 'Enter a valid phone number.')->withInput();
        }

        $payload = [
            'phone' => $data['phone'], 'name' => $data['name'] ?? null, 'currency' => $data['currency'],
            'amount' => abs((float) $data['amount']), 'type' => 'adjustment', 'notes' => $data['notes'],
        ];

        try {
            (float) $data['amount'] > 0 ? Credit::issue($payload) : Credit::spend($payload);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('admin.customer-credit.show', Credit::phoneKey($data['phone']))
            ->with('success', 'Credit adjusted and logged.');
    }
}
