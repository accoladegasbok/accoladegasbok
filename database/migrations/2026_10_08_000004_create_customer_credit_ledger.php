<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ONE ledger for all customer credit: overpayments and return store-credit in, credit used on
 * invoices, and pay-outs (cash / transfer) out. Kept per currency — never blended.
 *   amount_local  + = credit added      - = credit used or paid out
 *   balance       = SUM(amount_local) for a phone + currency
 * The customer is identified by the LAST 10 DIGITS of their phone ("0803..." and "+234 803..." match).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_credit_ledger')) {
            Schema::create('customer_credit_ledger', function (Blueprint $t) {
                $t->id();
                $t->string('phone_key', 20)->index();
                $t->string('customer_name', 120)->nullable();
                $t->string('customer_email', 150)->nullable();
                $t->string('currency_code', 3);
                $t->decimal('amount_local', 14, 2);
                $t->string('entry_type', 30);           // overpayment | return_credit | used_invoice | used_order | payout_cash | payout_transfer | adjustment
                $t->string('source_type', 20)->nullable(); // invoice | order | return
                $t->unsignedBigInteger('source_id')->nullable();
                $t->string('reference', 60)->nullable();   // invoice no / order ref / transfer ref
                $t->text('notes')->nullable();
                $t->unsignedBigInteger('staff_id')->nullable();
                $t->string('staff_name', 80)->nullable();
                $t->string('authorized_by', 80)->nullable();
                $t->timestamp('created_at')->nullable();
                $t->index(['entry_type', 'source_type', 'source_id']);
            });
        }

        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'account_credit_applied_local')) {
            Schema::table('invoices', function (Blueprint $t) {
                $t->decimal('account_credit_applied_local', 14, 2)->default(0);
            });
        }

        // Move existing, still-unused return store-credits into the ledger (so nothing owed is lost).
        if (Schema::hasTable('returns') && Schema::hasColumn('returns', 'refund_method')) {
            $rows = DB::table('returns as r')
                ->leftJoin('invoices as i', 'i.id', '=', 'r.invoice_id')
                ->leftJoin('orders as o', 'o.id', '=', 'r.order_id')
                ->where('r.status', 'resolved')
                ->where('r.refund_method', 'store_credit')
                ->where('r.refund_amount_local', '>', 0)
                ->whereNull('r.credit_applied_at')
                ->select('r.id', 'r.refund_amount_local',
                    DB::raw('COALESCE(i.customer_phone, o.customer_phone) as phone'),
                    DB::raw('COALESCE(i.customer_name, o.customer_name) as name'),
                    DB::raw('COALESCE(i.customer_email, o.customer_email) as email'),
                    DB::raw("COALESCE(i.currency_code, o.currency_code, 'NGN') as currency"),
                    DB::raw('COALESCE(i.invoice_no, o.order_ref) as ref'))
                ->get();

            foreach ($rows as $r) {
                $digits = preg_replace('/\D/', '', (string) $r->phone);
                if ($digits === '') continue; // no phone = nobody to attach the credit to
                DB::table('customer_credit_ledger')->insert([
                    'phone_key'      => strlen($digits) > 10 ? substr($digits, -10) : $digits,
                    'customer_name'  => $r->name,
                    'customer_email' => $r->email,
                    'currency_code'  => $r->currency,
                    'amount_local'   => $r->refund_amount_local,
                    'entry_type'     => 'return_credit',
                    'source_type'    => 'return',
                    'source_id'      => $r->id,
                    'reference'      => $r->ref,
                    'notes'          => 'Moved from the old return-credit list',
                    'staff_name'     => 'System',
                    'created_at'     => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_credit_ledger');
        if (Schema::hasColumn('invoices', 'account_credit_applied_local')) {
            Schema::table('invoices', fn (Blueprint $t) => $t->dropColumn('account_credit_applied_local'));
        }
    }
};
