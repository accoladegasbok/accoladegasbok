<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * NEW: unified read-only reporting view across `invoices` and `orders`.
 *
 * This is the SAFE version of "unify invoices and orders" — a genuine
 * table merge was deliberately NOT attempted (live production data,
 * incomplete file visibility across this whole project, no staging
 * environment to test a migration this size). This view gives the
 * practical benefit — one place to query/report across every sale
 * regardless of which system created it — with ZERO risk, since it
 * doesn't move, alter, or touch a single existing row. It's purely a
 * saved SELECT statement; every existing controller, form, and report
 * keeps working exactly as it already does.
 *
 * Columns verified directly against both tables' real schema
 * (Schema::getColumnListing) before writing this — NOT guessed.
 * Notably: orders has NO `location` column at all (invoices does) —
 * orders track customer_city/customer_country instead, since they're
 * online/phone orders rather than tied to a specific physical branch.
 * That column is genuinely NULL for every order row here rather than
 * papering over the mismatch with an inaccurate substitute.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("DROP VIEW IF EXISTS unified_sales");

        DB::statement("
            CREATE VIEW unified_sales AS
            SELECT
                i.id                    AS source_id,
                'invoice'               AS sale_type,
                i.invoice_type          AS sale_subtype,
                i.invoice_no            AS sale_ref,
                i.customer_name,
                i.customer_phone,
                i.location              AS location,
                NULL                    AS customer_country,
                i.currency_code,
                i.subtotal_local        AS total_local,
                i.discount_amount_local AS discount_local,
                NULL                    AS payment_status,
                'confirmed'             AS effective_payment_status,
                i.payment_method,
                i.created_by,
                i.created_at,
                i.deleted_at
            FROM invoices i

            UNION ALL

            SELECT
                o.id                    AS source_id,
                'order'                 AS sale_type,
                'order'                 AS sale_subtype,
                o.order_ref             AS sale_ref,
                o.customer_name,
                o.customer_phone,
                NULL                    AS location,
                o.customer_country      AS customer_country,
                o.currency_code,
                COALESCE(o.total_amount_local, o.total_amount_ngn, o.total_amount_usd) AS total_local,
                o.discount_amount_local AS discount_local,
                o.payment_status,
                o.payment_status        AS effective_payment_status,
                o.payment_method,
                o.created_by,
                o.created_at,
                o.deleted_at
            FROM orders o
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS unified_sales");
    }
};
