<?php
// FILE: app/Services/CustomerCreditService.php
//
// The single place that reads and writes customer credit. Every change is a ledger row, so the
// ledger doubles as the audit log ("every use is logged"). Credit is kept per currency.

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CustomerCreditService
{
    /** Roles that may apply credit, pay it out or adjust it. */
    public const MANAGER_ROLES = ['admin', 'manager', 'supervisor'];

    public static function canManage(): bool
    {
        return in_array(Session::get('staff_role'), self::MANAGER_ROLES, true);
    }

    /** Last 10 digits, so "0803 123 4567" and "+234 803 123 4567" are the same customer. */
    public static function phoneKey(?string $phone): string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);
        return strlen($digits) > 10 ? substr($digits, -10) : $digits;
    }

    public static function balance(?string $phone, string $currency): float
    {
        $key = self::phoneKey($phone);
        if ($key === '') return 0.0;

        return round((float) DB::table('customer_credit_ledger')
            ->where('phone_key', $key)->where('currency_code', $currency)->sum('amount_local'), 2);
    }

    /** [currency => balance] for one customer, only currencies with something in them. */
    public static function balances(?string $phone): array
    {
        $key = self::phoneKey($phone);
        if ($key === '') return [];

        return DB::table('customer_credit_ledger')->where('phone_key', $key)
            ->select('currency_code', DB::raw('ROUND(SUM(amount_local), 2) as bal'))
            ->groupBy('currency_code')->pluck('bal', 'currency_code')
            ->filter(fn ($b) => (float) $b != 0.0)->map(fn ($b) => (float) $b)->all();
    }

    /** Add credit. Returns the new ledger row id. */
    public static function issue(array $d): int
    {
        return self::write($d, abs((float) $d['amount']));
    }

    /**
     * Use credit or pay it out. Re-checks the balance inside a lock, so two staff can't spend
     * the same credit twice. Throws if there isn't enough.
     */
    public static function spend(array $d): int
    {
        $amount = abs((float) $d['amount']);

        return DB::transaction(function () use ($d, $amount) {
            $key = self::phoneKey($d['phone']);
            $bal = (float) DB::table('customer_credit_ledger')
                ->where('phone_key', $key)->where('currency_code', $d['currency'])
                ->lockForUpdate()->sum('amount_local');

            if ($amount > round($bal, 2) + 0.001) {
                throw new \RuntimeException('Not enough customer credit (available ' . number_format($bal, 2) . ' ' . $d['currency'] . ').');
            }
            return self::write($d, -$amount);
        });
    }

    private static function write(array $d, float $signedAmount): int
    {
        return DB::table('customer_credit_ledger')->insertGetId([
            'phone_key'      => self::phoneKey($d['phone']),
            'customer_name'  => $d['name'] ?? null,
            'customer_email' => $d['email'] ?? null,
            'currency_code'  => $d['currency'],
            'amount_local'   => round($signedAmount, 2),
            'entry_type'     => $d['type'],
            'source_type'    => $d['source_type'] ?? null,
            'source_id'      => $d['source_id'] ?? null,
            'reference'      => $d['reference'] ?? null,
            'notes'          => $d['notes'] ?? null,
            'staff_id'       => Session::get('staff_id'),
            'staff_name'     => Session::get('staff_name') ?? 'System',
            'authorized_by'  => $d['authorized_by'] ?? Session::get('staff_name'),
            'created_at'     => now(),
        ]);
    }

    /**
     * Overpayment -> credit. If confirmed payments on an invoice/order add up to MORE than it is
     * worth, the extra becomes credit (e.g. paid 500,000 for 480,000 -> 20,000 credit). Safe to
     * call after every confirmation: it only ever adds what has not been credited yet.
     * Returns the amount newly credited (0 if none).
     */
    public static function syncOverpayment(string $type, int $id): float
    {
        if ($type === 'invoice') {
            $doc = DB::table('invoices')->where('id', $id)->first();
            if (!$doc) return 0.0;
            $total = (float) ($doc->subtotal_local ?? $doc->subtotal_usd ?? 0);
            $paid  = (float) DB::table('invoice_payments')->where('invoice_id', $id)->where('status', 'confirmed')->sum('amount_local');
            $ref   = $doc->invoice_no;
        } else {
            $doc = DB::table('orders')->where('id', $id)->first();
            if (!$doc) return 0.0;
            $total = (float) ($doc->total_amount_local ?? $doc->total_amount_ngn ?? $doc->total_amount_usd ?? 0);
            $paid  = (float) DB::table('order_payments')->where('order_id', $id)->where('status', 'confirmed')->get()
                ->sum(fn ($p) => (float) ($p->amount_local ?? $p->amount_ngn ?? $p->amount_usd ?? 0));
            $ref   = $doc->order_ref;
        }

        $excess = round($paid - $total, 2);
        if ($excess <= 0 || self::phoneKey($doc->customer_phone) === '') return 0.0;

        $already = (float) DB::table('customer_credit_ledger')
            ->where('entry_type', 'overpayment')->where('source_type', $type)->where('source_id', $id)->sum('amount_local');
        $new = round($excess - $already, 2);
        if ($new <= 0) return 0.0;

        self::issue([
            'phone'       => $doc->customer_phone,
            'name'        => $doc->customer_name,
            'email'       => $doc->customer_email,
            'currency'    => $doc->currency_code ?? 'NGN',
            'amount'      => $new,
            'type'        => 'overpayment',
            'source_type' => $type,
            'source_id'   => $id,
            'reference'   => $ref,
            'notes'       => 'Paid more than the amount due',
        ]);
        return $new;
    }
}
