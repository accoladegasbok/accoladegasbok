{{-- FILE: resources/views/admin/invoices/show.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice {{ $invoiceNo }} — Auto Zenith Parts</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: 'Arial', sans-serif; font-size: 16px; color: #1a1a2e; background: #f0f2f5; }
.print-controls {
    position: fixed; top: 0; left: 0; right: 0; z-index: 999;
    background: #0d1b2a; color: white; padding: 12px 24px;
    display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
    box-shadow: 0 2px 12px rgba(0,0,0,0.3);
}
.print-controls h2 { font-size: 15px; font-weight: 700; color: #c9a84c; margin-right: 8px; }
.copy-btn {
    padding: 7px 16px; border-radius: 6px; border: 2px solid transparent;
    font-size: 12px; font-weight: 700; cursor: pointer; transition: all 0.2s;
    text-transform: uppercase; letter-spacing: 0.5px;
}
.copy-btn.active { background: #c9a84c; color: #0d1b2a; border-color: #c9a84c; }
.copy-btn:not(.active) { background: transparent; color: #aaa; border-color: #444; }
.copy-btn:hover:not(.active) { border-color: #c9a84c; color: #c9a84c; }
.print-all-btn {
    padding: 7px 16px; background: #1a6b3c; color: white; border: none;
    border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;
    text-transform: uppercase; letter-spacing: 0.5px;
}
.print-all-btn:hover { background: #22873d; }
.print-single-btn {
    padding: 7px 16px; background: #0d47a1; color: white; border: none;
    border-radius: 6px; font-size: 12px; font-weight: 700; cursor: pointer;
    text-transform: uppercase; letter-spacing: 0.5px;
}
.print-single-btn:hover { background: #1565c0; }
.sep { color: #444; }
.invoice-pages { margin-top: 64px; padding: 20px; }
.payments-panel {
    max-width: 560px; margin: 84px auto 0; padding: 16px 20px;
    background: #fff; border: 1px solid #e2e2e2; border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06); position: relative; z-index: 1;
}
@page {
    size: A4;
    /* FIXED (round 3): reverted to standard A4 margins — header/footer
       no longer repeat as separate content at all (replaced by a
       repeating watermark + page number instead, per explicit
       request), so there's no need to reserve extra margin space for
       repeating text anymore. */
    margin: 15mm 12mm;
}
@media print { .payments-panel { display: none !important; } }
.invoice {
    background: white; width: 210mm; min-height: 297mm; /* FIXED: was 148mm — half of real A4 height (297mm), causing every printed copy to look small/cramped on a full A4 sheet */
    margin: 0 auto 20px; padding: 16mm 14mm;
    box-shadow: 0 2px 16px rgba(0,0,0,0.12);
    position: relative; page-break-after: always;
}
.invoice:last-child { page-break-after: auto; }
.copy-banner {
    position: absolute; top: 0; right: 0;
    padding: 6px 20px; font-size: 10px; font-weight: 700;
    letter-spacing: 1.5px; text-transform: uppercase; color: white;
    border-bottom-left-radius: 8px;
}
.copy-customer  .copy-banner { background: #0d47a1; }
.copy-warehouse .copy-banner { background: #1a6b3c; }
.copy-accounts  .copy-banner { background: #6a1b9a; }
.copy-gate      .copy-banner { background: #b71c1c; }
.copy-waybill   .copy-banner { background: #8a6d1f; }
.watermark {
    position: absolute; top: 50%; left: 50%;
    transform: translate(-50%, -50%) rotate(-35deg);
    font-size: 60px; font-weight: 900; opacity: 0.04;
    letter-spacing: 4px; white-space: nowrap; pointer-events: none;
    text-transform: uppercase; z-index: 0;
}
.copy-customer  .watermark { color: #0d47a1; }
.copy-warehouse .watermark { color: #1a6b3c; }
.copy-accounts  .watermark { color: #6a1b9a; }
.copy-gate      .watermark { color: #b71c1c; }
.copy-waybill   .watermark { color: #8a6d1f; }
.invoice-content { position: relative; z-index: 1; }

/* ── HEADER ─────────────────────────────────────────────────────── */
.inv-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; border-bottom: 2.5px solid #0d1b2a; padding-bottom: 12px; }
.brand-block .brand-name { font-size: 31px; font-weight: 900; color: #0d1b2a; letter-spacing: 2px; }
.brand-block .brand-name span { color: #c9a84c; }
.brand-block .tagline { font-size: 12px; color: #666; letter-spacing: 2px; text-transform: uppercase; margin-top: 2px; }
.brand-block .company { font-size: 13px; color: #444; margin-top: 4px; font-weight: 600; }
.brand-block .address { font-size: 13px; color: #555; margin-top: 2px; line-height: 1.6; }
.brand-block .contact { font-size: 13px; color: #555; margin-top: 4px; }

/* ── META (right side of header) ────────────────────────────────── */
.inv-meta { text-align: right; }
.inv-meta .inv-title { font-size: 26px; font-weight: 900; color: #0d1b2a; letter-spacing: 3px; text-transform: uppercase; }
.inv-meta table { margin-top: 6px; }
.inv-meta td { padding: 3px 0; font-size: 13px; }
.inv-meta td:first-child { color: #777; padding-right: 10px; }
.inv-meta td:last-child { font-weight: 700; color: #0d1b2a; }

/* ── BILL TO / PAYMENT DETAILS ──────────────────────────────────── */
.inv-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; }
.info-box { background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 6px; padding: 9px 11px; }
.info-box h4 { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #888; margin-bottom: 6px; border-bottom: 1px solid #e0e0e0; padding-bottom: 4px; }
.info-box p { font-size: 13px; line-height: 1.7; color: #333; }
.info-box strong { color: #0d1b2a; }

/* ── LINE ITEMS TABLE ───────────────────────────────────────────── */
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
.items-table thead tr { background: #0d1b2a; color: white; }
.items-table thead th { padding: 10px 11px; text-align: left; font-size: 12px; letter-spacing: 0.8px; text-transform: uppercase; }
.items-table thead th:last-child { text-align: right; }
.items-table tbody tr:nth-child(even) { background: #f8f9fa; }
.items-table tbody tr { border-bottom: 1px solid #eee; }
.items-table tbody td { padding: 10px 11px; font-size: 14px; vertical-align: top; }
.items-table tbody td:last-child { text-align: right; font-weight: 700; }
.part-name { font-weight: 700; color: #0d1b2a; font-size: 14px; }
.part-sub { font-size: 11px; color: #888; margin-top: 3px; }
.grade-badge { display: inline-block; padding: 2px 7px; border-radius: 3px; font-size: 11px; font-weight: 700; }
.grade-A { background: #e8f5e9; color: #2e7d32; }
.grade-B { background: #fff3e0; color: #e65100; }
.grade-C { background: #fce4ec; color: #c62828; }
.grade-New { background: #e3f2fd; color: #1565c0; }

/* ── TOTALS ─────────────────────────────────────────────────────── */
.inv-totals { display: flex; justify-content: flex-end; margin-bottom: 14px; }
.totals-box { width: 240px; }
.totals-box table { width: 100%; }
.totals-box td { padding: 6px 11px; font-size: 14px; }
.totals-box td:last-child { text-align: right; font-weight: 700; }
.totals-box .total-row { background: #0d1b2a; color: white; border-radius: 4px; }
.totals-box .total-row td { font-size: 17px; padding: 8px 11px; }

/* ── PAYMENT SECTION ────────────────────────────────────────────── */
.inv-payment { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; }
.payment-box { border: 1px solid #e0e0e0; border-radius: 6px; padding: 9px 11px; }
.payment-box h4 { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: #888; margin-bottom: 6px; border-bottom: 1px solid #e0e0e0; padding-bottom: 4px; }
.payment-box p { font-size: 13px; line-height: 1.8; color: #333; }

/* ── WARRANTY BOX ───────────────────────────────────────────────── */
.inv-warranty { background: #fff8e1; border: 1px solid #ffe082; border-radius: 6px; padding: 8px 11px; margin-bottom: 12px; }
.inv-warranty p { font-size: 12px; color: #5d4037; line-height: 1.6; }
.inv-warranty strong { color: #3e2723; }

/* ── GATE PASS ──────────────────────────────────────────────────── */
.gate-pass-section { border: 2px dashed #b71c1c; border-radius: 8px; padding: 10px; margin-bottom: 12px; }
.gate-pass-section h3 { font-size: 16px; font-weight: 900; color: #b71c1c; text-align: center; letter-spacing: 3px; margin-bottom: 9px; }
.gate-pass-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
.gate-field { border-bottom: 1px solid #999; padding-bottom: 16px; }
.gate-field label { font-size: 11px; color: #666; display: block; margin-bottom: 3px; text-transform: uppercase; }

/* ── SIGNATURES ─────────────────────────────────────────────────── */
.inv-signatures { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-top: 8px; }
.sig-box { text-align: center; }
.sig-line { border-top: 1px solid #999; margin-top: 28px; padding-top: 5px; }
.sig-label { font-size: 11px; color: #777; text-transform: uppercase; letter-spacing: 0.8px; }

/* ── FOOTER ─────────────────────────────────────────────────────── */
.inv-footer { text-align: center; margin-top: 12px; padding-top: 8px; border-top: 1px solid #eee; }
.inv-footer p { font-size: 11px; color: #999; line-height: 1.7; }
.inv-footer .website { font-weight: 700; color: #c9a84c; }

.hidden { display: none !important; }

/* ── MOBILE (screen, not print) ────────────────────────────────────
   The invoice is a fixed 210mm (A4) print layout by design — great on
   paper/desktop, unusable on a phone screen. This reflows everything
   to a single scrollable column below ~600px viewport width without
   touching anything print-related above. */
@media screen and (max-width: 600px) {
    body { font-size: 14px; }
    .print-controls { padding: 10px 12px; gap: 8px; }
    .print-controls h2 { font-size: 13px; margin-right: 4px; width: 100%; }
    .copy-btn, .print-all-btn, .print-single-btn { padding: 6px 10px; font-size: 11px; }
    .invoice-pages { margin-top: 96px; padding: 8px; }
    .payments-panel { margin: 100px 8px 0; max-width: none; }

    .invoice {
        width: 100%; min-height: 0;
        padding: 16px 14px; margin: 0 auto 16px;
    }

    .inv-header { flex-direction: column; gap: 10px; }
    .inv-meta { text-align: left; width: 100%; }
    .inv-meta table { width: 100%; }
    .brand-block .brand-name { font-size: 24px; }
    .inv-meta .inv-title { font-size: 20px; }

    .inv-parties { grid-template-columns: 1fr; }

    /* Items table: allow horizontal scroll instead of squeezing
       columns unreadably small — safer for barcodes/part codes/prices
       than trying to wrap every column. */
    .items-table { display: block; overflow-x: auto; white-space: nowrap; }
    .items-table thead, .items-table tbody { display: table; width: 100%; }
    .items-table tbody td { font-size: 12px; padding: 8px 6px; white-space: normal; }
    .part-name { font-size: 13px; }

    .inv-totals { justify-content: stretch; }
    .totals-box { width: 100%; }

    .gate-pass-grid { grid-template-columns: 1fr 1fr; }
    .inv-signatures { grid-template-columns: 1fr; gap: 20px; }
}

@media print {
    body { background: white; font-size: 16px; }
    .print-controls { display: none !important; }
    .invoice-pages { margin-top: 0; padding: 0; }
    .invoice { box-shadow: none; margin: 0; page-break-after: always; padding-top: 0; padding-bottom: 0; }

    /* NEW: makes the header and footer repeat on EVERY physical
       printed page, not just once per copy. position:fixed pulls them
       out of normal document flow; the negative top/bottom offsets
       place them within the @page margin space reserved above,
       matching each element's own approximate height. This is the
       standard, reliable cross-browser technique for repeating
       print headers/footers — Chrome and Edge (Chromium) both
       support it well. */
    /* FIXED (round 3): the compact mini-header/footer from the
       previous round were STILL showing up as visible duplicate
       content on page 1 alongside the full rich header/footer —
       correct behavior for the mechanism, but not what was actually
       wanted. Per explicit request: no repeating header/footer text
       at all anymore. Instead, the existing watermark now repeats on
       every physical page (changed to position:fixed instead of
       absolute, which only ever centered once within a copy's total
       height), plus a running page number.
       NOTE ON PAGE NUMBERING: counter(page) below reliably shows the
       CURRENT page number in Chrome/Edge. counter(pages) (the TOTAL
       page count, for "Page 2 of 3") requires a two-pass print layout
       that most browser print engines — including Chrome — don't
       fully support yet. It's included below as a best-effort attempt
       and may simply not render the "of Y" part depending on the
       browser/version actually used to print. */
    .watermark { position: fixed !important; }

    @page {
        @bottom-right {
            content: "Page " counter(page) " of " counter(pages);
            font-size: 9px; color: #999;
        }
    }
    /* Fallback page-number element for browsers that don't support the
       @page margin-box syntax above at all — shows current page only
       (reliable), positioned in normal print flow at the corner. */
    .page-number-fallback {
        position: fixed; bottom: 4mm; right: 12mm;
        font-size: 9px; color: #999;
    }
    .page-number-fallback::after {
        content: "Page " counter(page);
    }
    .hidden { display: none !important; }
    /* Ensure font sizes are preserved on print */
    .part-name { font-size: 14px !important; }
    .items-table tbody td { font-size: 14px !important; }
    .totals-box .total-row td { font-size: 17px !important; }

    /* ── LEGIBILITY: force all real content to solid black, bold ──
       This document may need to survive a police-checkpoint photocopy
       or a cheap/low-toner printer, so nothing informational should
       ever fade to light grey. Grade/copy-type color coding is kept
       (still useful for quick visual sorting), and the background
       watermark is deliberately exempted — it's a faint security
       texture, not content, and forcing it to bold black would just
       obscure the page. */
    .invoice, .invoice p, .invoice td, .invoice th, .invoice div, .invoice span, .invoice strong,
    .invoice label {
        color: #000 !important;
    }
    .invoice .watermark {
        color: inherit !important; /* restores each copy type's own faint watermark color, untouched by the rule above */
    }
    .invoice td, .invoice th, .invoice p, .invoice .part-name, .invoice .part-sub,
    .brand-block .company, .brand-block .address, .brand-block .contact, .brand-block .tagline,
    .inv-meta td, .info-box h4, .info-box p, .payment-box h4, .payment-box p,
    .sig-label, .gate-field label, .inv-footer p, .inv-footer div {
        font-weight: 700 !important;
    }
    /* Grade/copy badges keep their own color-coded backgrounds for
       fast visual scanning — only their text needs to stay legible,
       which the black override above already handles. */
}
</style>
</head>
<body>

<div class="print-controls" id="printControls">
    {{-- FIXED: every invoice rendered here is created only at the point
         of full payment (no partial-payment path exists for manual/
         service/car-sale invoices) — so it's always actually a receipt,
         not just for vehicle sales. --}}
    <h2>RECEIPT {{ $invoiceNo }}</h2>
    @if(isset($invoice) && in_array(session('staff_role'), ['admin', 'manager']))
    <a href="{{ route('admin.invoices.manual.edit', $invoice->id) }}" style="background:#c9a84c;color:#0d1b2a;padding:7px 16px;border-radius:6px;font-size:12px;font-weight:700;text-decoration:none;text-transform:uppercase;letter-spacing:0.5px;">
        ✎ Edit Invoice
    </a>
    @endif
    {{-- NEW: Return button — no return workflow was reachable from an
         invoice at all before this. Pre-selects this invoice on the
         Returns creation form so staff don't have to search for it
         again by phone/invoice number. --}}
    @if(isset($invoice) && !$isVehicleSale)
    <a href="{{ route('admin.returns.create', ['invoice_id' => $invoice->id]) }}" style="background:#fff;color:#a32d2d;border:1.5px solid #a32d2d;padding:6px 16px;border-radius:6px;font-size:12px;font-weight:700;text-decoration:none;text-transform:uppercase;letter-spacing:0.5px;">
        ↩ Log Return
    </a>
    @endif
    <span class="sep">|</span>
    <span style="font-size:12px;color:#aaa;">Select copy to print:</span>
    <button class="copy-btn active" onclick="showCopy('customer')" id="btn-customer">📄 Customer Copy</button>
    <button class="copy-btn" onclick="showCopy('warehouse')" id="btn-warehouse">🏭 Warehouse Copy</button>
    <button class="copy-btn" onclick="showCopy('accounts')" id="btn-accounts">📊 Accounts Copy</button>
    <button class="copy-btn" onclick="showCopy('gate')" id="btn-gate">🚧 Gate Pass</button>
    <button class="copy-btn" onclick="showCopy('waybill')" id="btn-waybill">📦 Waybill / Packing List</button>
    <button class="copy-btn active" onclick="showCopy('all')" id="btn-all" style="border-color:#c9a84c;color:#c9a84c;">📋 All 5 Copies</button>
    <span class="sep">|</span>
    <button class="print-single-btn" onclick="window.print()">🖨 Print</button>
    <a href="{{ url()->previous() }}" style="color:#aaa;font-size:12px;text-decoration:none;">← Back</a>
</div>

@php
    $resolvedInvoiceId = $invoice->id ?? $invoiceId ?? null;
    $paySummary = ($resolvedInvoiceId && !isset($order)) ? \App\Http\Controllers\Admin\InvoiceController::invoicePaymentSummary($resolvedInvoiceId) : null;
@endphp
@if(isset($order) && $order)
<div class="payments-panel" style="text-align:center;">
    <div style="display:flex; gap:10px; justify-content:center; margin-bottom:10px;">
        <a href="{{ route('admin.orders.show', $order->id) }}" style="display:inline-block; background:#0d1b2a; color:#fff; font-weight:700; font-size:13px; padding:10px 20px; border-radius:8px; text-decoration:none;">
            Manage Payments on Order {{ $order->order_ref }} →
        </a>
        @if(in_array(session('staff_role'), ['admin','manager','supervisor']))
        <a href="{{ route('admin.orders.edit', $order->id) }}" style="display:inline-block; background:#c9a84c; color:#0d1b2a; font-weight:700; font-size:13px; padding:10px 20px; border-radius:8px; text-decoration:none;">
            ✎ Edit Order
        </a>
        @endif
    </div>
    {{-- NEW: same PDF letterhead + Send Customer Copy capability the
         invoice branch below already has — was previously only
         available for manual invoices, not orders viewed through
         this same unified template. --}}
    <div style="display:flex; gap:10px; justify-content:center;">
        <a href="{{ route('admin.orders.download-pdf', $order->id) }}" target="_blank"
           style="display:inline-block; background:#f5f0e6; border:1px solid #d9c99a; color:#8a6d1f; font-weight:700; font-size:13px; padding:8px 16px; border-radius:8px; text-decoration:none;">
            📄 Download PDF
        </a>
        <form method="POST" action="{{ route('admin.orders.send-customer-copy', $order->id) }}" onsubmit="return confirm('Email the customer their receipt now?')">
            @csrf
            <button type="submit" style="background:#eef4fb; border:1px solid #b3d1f0; color:#1a4d8f; font-weight:700; font-size:13px; padding:8px 16px; border-radius:8px; cursor:pointer;">
                ✉ Send Customer Copy
            </button>
        </form>
    </div>
    @if(session('whatsapp_reminder_link'))
    <a href="{{ session('whatsapp_reminder_link') }}" target="_blank"
       style="display:block; text-align:center; margin-top:10px; background:#e8f7ee; border:1px solid #25D366; color:#1b7a3d; font-size:12px; font-weight:700; padding:8px; border-radius:6px; text-decoration:none;">
        💬 Message Customer via WhatsApp (opens pre-filled — tap Send to actually deliver it)
    </a>
    @endif
</div>
@elseif($resolvedInvoiceId && $paySummary)
<div class="payments-panel">
    <div style="display:flex; justify-content:space-between; gap:12px; margin-bottom:12px;">
        <div style="flex:1; background:#f5f5f5; border-radius:8px; padding:10px; text-align:center;">
            <div style="font-size:11px; color:#999; text-transform:uppercase;">Total</div>
            <div style="font-size:18px; font-weight:700; color:#0d1b2a;">{{ $subtotalFmt }}</div>
        </div>
        <div style="flex:1; background:#e8f7ee; border-radius:8px; padding:10px; text-align:center;">
            <div style="font-size:11px; color:#999; text-transform:uppercase;">Paid</div>
            <div style="font-size:18px; font-weight:700; color:#1b9e5c;">{{ $currency['symbol'] }}{{ number_format($paySummary['confirmedPaid']) }}</div>
        </div>
        <div style="flex:1; background:{{ $paySummary['balanceDue'] > 0 ? '#fdecec' : '#e8f7ee' }}; border-radius:8px; padding:10px; text-align:center;">
            <div style="font-size:11px; color:#999; text-transform:uppercase;">Balance</div>
            <div style="font-size:18px; font-weight:700; color:{{ $paySummary['balanceDue'] > 0 ? '#c0392b' : '#1b9e5c' }};">{{ $currency['symbol'] }}{{ number_format($paySummary['balanceDue']) }}</div>
        </div>
    </div>
    {{-- NEW: a real Send button for emailing the customer their copy —
         previously there was no way to send a receipt directly, only
         print/download it manually. Available regardless of payment
         status, unlike the reminder button below. --}}
    <form method="POST" action="{{ route('admin.invoices.send-customer-copy', $resolvedInvoiceId) }}" onsubmit="return confirm('Email the customer their receipt now?')" style="margin-bottom:8px;">
        @csrf
        <button type="submit" style="width:100%; background:#eef4fb; border:1px solid #b3d1f0; color:#1a4d8f; font-size:12px; font-weight:700; padding:8px; border-radius:6px; cursor:pointer;">✉ Send Customer Copy (Email)</button>
    </form>
    {{-- NEW: real, letterheaded, downloadable PDF — separate from the
         browser print dialog, and the same PDF now gets attached to
         the email above instead of plain HTML. --}}
    <a href="{{ route('admin.invoices.download-pdf', $resolvedInvoiceId) }}" target="_blank"
       style="display:block; text-align:center; width:100%; background:#f5f0e6; border:1px solid #d9c99a; color:#8a6d1f; font-size:12px; font-weight:700; padding:8px; border-radius:6px; text-decoration:none; margin-bottom:8px; box-sizing:border-box;">
        📄 Download PDF
    </a>
    @if($paySummary['balanceDue'] > 0)
    <form method="POST" action="{{ route('admin.invoices.send-reminder', $resolvedInvoiceId) }}" onsubmit="return confirm('Send a payment reminder email, and get a WhatsApp link ready to send manually?')" style="margin-bottom:8px;">
        @csrf
        <button type="submit" style="width:100%; background:#fff8e6; border:1px solid #e6c656; color:#8a6d1f; font-size:12px; font-weight:700; padding:8px; border-radius:6px; cursor:pointer;">📩 Send Payment Reminder (Email)</button>
    </form>
    {{-- FIXED: sendReminder() already generates a real WhatsApp link
         (message pre-filled, staff taps Send manually) — it just never
         had anywhere to actually show up until now. --}}
    @if(session('whatsapp_reminder_link'))
    <a href="{{ session('whatsapp_reminder_link') }}" target="_blank"
       style="display:block; text-align:center; width:100%; background:#e8f7ee; border:1px solid #25D366; color:#1b7a3d; font-size:12px; font-weight:700; padding:8px; border-radius:6px; text-decoration:none; margin-bottom:12px;">
        💬 Message Customer via WhatsApp (opens pre-filled — tap Send to actually deliver it)
    </a>
    @endif
    @endif
    @if($paySummary['payments']->count())
    <table style="width:100%; font-size:12px; margin-bottom:12px; border-collapse:collapse;">
        <thead><tr style="background:#f5f5f5; color:#999; text-transform:uppercase;"><th style="padding:6px; text-align:left;">Amount</th><th style="padding:6px; text-align:left;">Method</th><th style="padding:6px; text-align:left;">Proof</th><th style="padding:6px; text-align:left;">Status</th><th></th></tr></thead>
        <tbody>
        @foreach($paySummary['payments'] as $p)
        <tr style="border-top:1px solid #eee;">
            <td style="padding:6px; font-weight:700;">{{ $currency['symbol'] }}{{ number_format($p->amount_local) }}</td>
            <td style="padding:6px;">{{ $p->payment_method }}</td>
            <td style="padding:6px;">@if($p->proof_path)<a href="{{ asset(config('media.prefix') . '/' . $p->proof_path) }}" target="_blank" style="color:#c9a84c;">View →</a>@else —@endif</td>
            <td style="padding:6px;"><span style="padding:2px 6px; border-radius:4px; font-size:11px; background:{{ $p->status==='confirmed' ? '#e8f7ee' : ($p->status==='rejected' ? '#fdecec' : '#fff8e6') }}; color:{{ $p->status==='confirmed' ? '#1b9e5c' : ($p->status==='rejected' ? '#c0392b' : '#8a6d1f') }};">{{ ucfirst($p->status) }}</span></td>
            <td style="padding:6px; text-align:right;">
                @if($p->status === 'pending')
                <form method="POST" action="{{ route('admin.invoices.payments.confirm', [$resolvedInvoiceId, $p->id]) }}" style="display:inline;">@csrf<button style="color:#1b9e5c; border:none; background:none; cursor:pointer; font-size:12px;">✓</button></form>
                <form method="POST" action="{{ route('admin.invoices.payments.reject', [$resolvedInvoiceId, $p->id]) }}" style="display:inline;" onsubmit="return confirm('Reject this payment?')">@csrf<button style="color:#c0392b; border:none; background:none; cursor:pointer; font-size:12px;">✕</button></form>
                @endif
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif
    @if($paySummary['balanceDue'] > 0)
    <form method="POST" action="{{ route('admin.invoices.payments.add', $resolvedInvoiceId) }}" enctype="multipart/form-data" style="border:2px solid #c9a84c; border-radius:8px; padding:12px;">
        @csrf
        <div style="font-size:12px; font-weight:700; text-transform:uppercase; margin-bottom:8px; color:#0d1b2a;">Record a Payment (Full or Partial)</div>
        <div style="display:flex; gap:8px; margin-bottom:8px;">
            <input type="number" name="amount_local" step="0.01" min="0.01" max="{{ $paySummary['balanceDue'] }}" required placeholder="Amount ({{ $currency['code'] }})" style="flex:1; border:1px solid #ddd; border-radius:6px; padding:6px 8px; font-size:13px;">
            <select name="payment_method" required style="flex:1; border:1px solid #ddd; border-radius:6px; padding:6px 8px; font-size:13px;">
                <option value="Cash">Cash</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Card">Card</option>
                <option value="POS">POS</option>
                <option value="Mobile Money">Mobile Money</option>
                <option value="Other">Other</option>
            </select>
        </div>
        <input type="file" name="proof" accept="image/*,application/pdf" style="width:100%; margin-bottom:8px; font-size:12px;">
        <input type="text" name="notes" placeholder="Notes (optional)" style="width:100%; border:1px solid #ddd; border-radius:6px; padding:6px 8px; font-size:13px; margin-bottom:8px;">
        <button type="submit" style="width:100%; background:#c9a84c; color:#0d1b2a; font-weight:700; font-size:13px; padding:8px; border:none; border-radius:6px; cursor:pointer;">Record Payment</button>
        <p style="font-size:11px; color:#999; margin-top:6px;">Recorded as "Pending" until a staff member confirms it — that's what actually reduces the balance.</p>
    </form>
    @else
    <div style="text-align:center; color:#1b9e5c; font-weight:700; font-size:13px; padding:10px;">✓ Fully paid</div>
    @endif
</div>
@endif

<div class="invoice-pages">

@php
$copies = [
    'customer'  => ['label' => 'CUSTOMER COPY',           'color' => '#0d47a1'],
    'warehouse' => ['label' => 'WAREHOUSE CONTROL COPY',  'color' => '#1a6b3c'],
    'accounts'  => ['label' => 'ACCOUNTS COPY',           'color' => '#6a1b9a'],
    'gate'      => ['label' => 'SECURITY / GATE PASS',    'color' => '#b71c1c'],
    'waybill'   => ['label' => 'WAYBILL / PACKING LIST — NO PRICES', 'color' => '#8a6d1f'],
];
$createdAt = $order->created_at ?? now();
$paymentMethod = $order->payment_method ?? 'Cash';
$qrUrl = isset($invoice)
    ? route('admin.invoices.show.manual', $invoice->id)
    : (isset($order) ? route('admin.invoices.show', $order->id) : url()->current());

$isVehicleSale = ($invoiceType ?? null) === 'vehicle';
@endphp

@foreach($copies as $copyKey => $copyInfo)
<div class="invoice copy-{{ $copyKey }}" id="copy-{{ $copyKey }}">
    <div class="watermark">{{ $copyInfo['label'] }}</div>
    <div class="page-number-fallback"></div>
    <div class="copy-banner">{{ $copyInfo['label'] }}</div>
    <div class="invoice-content">

        <div class="inv-header">
            <div class="brand-block">
                <div class="brand-name">AUTO <span>ZENITH</span> PARTS</div>
                <div class="tagline">{{ $isVehicleSale ? 'Quality Used Vehicles · Sold As-Is' : 'Quality Used Auto Parts · Engine · Gearbox · Body' }}</div>
                <div class="company">{{ $businessInfo['company'] }}{{ $businessInfo['rc'] ? ' · ' . $businessInfo['rc'] : '' }}</div>
                <div class="address">{{ $businessInfo['address'] }}</div>
                <div class="contact">📞 {{ $businessInfo['phone'] }} · 🌐 autozenithparts.com</div>
            </div>
            <div class="inv-meta">
                <div class="inv-title">{{ $isVehicleSale ? 'VEHICLE SALE RECEIPT' : 'RECEIPT' }}</div>
                <table>
                    <tr><td>Receipt No:</td><td>{{ $invoiceNo }}</td></tr>
                    <tr><td>Date:</td><td>{{ \Carbon\Carbon::parse($createdAt)->format('d M Y') }}</td></tr>
                    <tr><td>Time:</td><td>{{ \Carbon\Carbon::parse($createdAt)->format('h:i A') }}</td></tr>
                    <tr><td>Location:</td><td>{{ $location }}</td></tr>
                    <tr><td>Currency:</td><td>{{ $currency['code'] }}</td></tr>
                </table>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=65x65&data={{ urlencode($qrUrl) }}" alt="QR" style="margin-top:6px;">
            </div>
        </div>

        @php
            $resolvedInvoiceId2 = $invoice->id ?? $invoiceId ?? null;
            if (isset($order) && $order) {
                $printPaySummary = \App\Http\Controllers\Admin\OrderAdminController::paymentSummary($order->id);
                $printPaySummary['payments'] = $printPaySummary['payments']->map(function ($p) {
                    $p->amount_local = $p->amount_local ?? $p->amount_ngn;
                    return $p;
                });
            } else {
                $printPaySummary = $resolvedInvoiceId2 ? \App\Http\Controllers\Admin\InvoiceController::invoicePaymentSummary($resolvedInvoiceId2) : null;
            }

            // Real 3-state payment status — was hardcoded to always show
            // 'PAID' for every manual invoice (since $order is never set
            // for those, the old fallback silently defaulted to PAID
            // regardless of actual payment state). Now derived from the
            // real confirmed-payments total vs balance due.
            $confirmedPaidAmt = $printPaySummary
                ? $printPaySummary['payments']->where('status', 'confirmed')->sum('amount_local')
                : 0;
            $balanceDueAmt = $printPaySummary['balanceDue'] ?? 0;
            if ($balanceDueAmt <= 0 && $confirmedPaidAmt > 0) {
                $paymentStatusLabel = 'PAID';
            } elseif ($confirmedPaidAmt > 0 && $balanceDueAmt > 0) {
                $paymentStatusLabel = 'PARTIAL';
            } else {
                $paymentStatusLabel = 'UNPAID';
            }
        @endphp

        <div class="inv-parties">
            <div class="info-box">
                <h4>{{ $isVehicleSale ? 'Buyer' : 'Bill To' }}</h4>
                <p>
                    <strong>{{ $customerInfo->name ?? $order->customer_name ?? 'Walk-in Customer' }}</strong><br>
                    @if(!empty($customerInfo->phone ?? $order->customer_phone ?? null)) 📞 {{ $customerInfo->phone ?? $order->customer_phone }}<br>@endif
                    @if(!empty($customerInfo->email ?? $order->customer_email ?? null)) ✉ {{ $customerInfo->email ?? $order->customer_email }}<br>@endif
                    @if(!empty($customerInfo->address ?? $order->customer_address ?? null)) {{ $customerInfo->address ?? $order->customer_address }}@endif
                </p>
            </div>
            <div class="info-box">
                <h4>Payment Details</h4>
                <p>
                    <strong>Method:</strong> {{ $paymentMethod }}<br>
                    <strong>Status:</strong> <span style="color: {{ $paymentStatusLabel === 'PAID' ? '#1b9e5c' : ($paymentStatusLabel === 'PARTIAL' ? '#e65100' : '#c0392b') }}; font-weight:700;">{{ $paymentStatusLabel }}</span><br>
                    @if(!empty($businessInfo['bank']))
                    <strong>{{ $businessInfo['bank'] }}:</strong> {{ $businessInfo['account'] }}<br>
                    <strong>Name:</strong> {{ $businessInfo['acct_name'] }}
                    @endif
                </p>
            </div>
        </div>

        @if($copyKey === 'gate')
        <div class="gate-pass-section">
            <h3>🚧 SECURITY / GATE PASS</h3>
            <div class="gate-pass-grid">
                <div class="gate-field"><label>Customer Name</label><strong style="font-size:11px;">{{ $order->customer_name ?? '________________' }}</strong></div>
                <div class="gate-field"><label>Phone Number</label><strong style="font-size:11px;">{{ $order->customer_phone ?? '________________' }}</strong></div>
                <div class="gate-field"><label>Vehicle / Plate No.</label>&nbsp;</div>
                <div class="gate-field"><label>No. of Items</label><strong style="font-size:11px;">{{ $lineItems->count() }} item(s)</strong></div>
                <div class="gate-field"><label>Receipt No.</label><strong style="font-size:11px;">{{ $invoiceNo }}</strong></div>
                <div class="gate-field"><label>Exit Time</label>&nbsp;</div>
            </div>
        </div>
        @endif

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:30px">#</th>
                    <th>{{ $isVehicleSale ? 'Vehicle Description' : 'Part Description' }}</th>
                    <th style="width:95px">{{ $isVehicleSale ? 'VIN' : 'Part Code' }}</th>
                    @if(!$isVehicleSale)<th style="width:44px">Grade</th>@endif
                    <th style="width:32px">Qty</th>
                    @if($copyKey !== 'waybill')
                    <th style="width:88px">Unit Price</th>
                    <th style="width:88px">Total</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($lineItems as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <div class="part-name">
                            {{ $item->part_name }}
                            @if(($item->discount_amount_local ?? 0) > 0 && $copyKey !== 'waybill')
                            <span style="display:inline-block; background:#fff3e0; color:#e65100; font-size:9px; font-weight:700; padding:1px 5px; border-radius:3px; margin-left:4px; vertical-align:middle;">
                                @if($item->discount_type === 'percent')
                                    -{{ rtrim(rtrim(number_format((float) $item->discount_value, 2), '0'), '.') }}%
                                @else
                                    -{{ $currency['symbol'] }}{{ $currency['code'] === 'NGN' ? number_format($item->discount_amount_local) : number_format($item->discount_amount_local, 2) }}
                                @endif
                            </span>
                            @endif
                            {{-- NEW: returned & refunded badge --}}
                            @if(!empty($item->returned) && $copyKey !== 'waybill')
                            <span style="display:inline-block; background:#fdecea; color:#a32d2d; font-size:9px; font-weight:700; padding:1px 5px; border-radius:3px; margin-left:4px; vertical-align:middle;">
                                ↩ RETURNED — REFUNDED{{ $item->return_refund_method ? ' VIA ' . strtoupper(str_replace('_',' ', $item->return_refund_method)) : '' }}
                            </span>
                            @endif
                        </div>
                        <div class="part-sub">
                            @if($isVehicleSale)
                                @if(!empty($item->colour)){{ $item->colour }} · @endif
                                @if(!empty($item->mileage)){{ number_format($item->mileage) }} miles @endif
                            @else
                                @if(!empty($item->brand)){{ strtoupper($item->brand) }} {{ strtoupper($item->model) }} {{ $item->year_from }}@if($item->year_to && $item->year_to != $item->year_from)–{{ $item->year_to }}@endif · @endif
                                @if(!empty($item->engine_code_oem))Engine: {{ $item->engine_code_oem }} · @endif
                                @if(!empty($item->part_category)){{ $item->part_category }}@endif
                            @endif
                        </div>
                    </td>
                    <td>
                        @if(!$isVehicleSale)
                        <div style="font-family:monospace;font-size:10px;">{{ $item->part_code }}</div>
                        @if(!empty($item->brand))
                        <div style="font-size:9px;color:#666;margin-top:2px;">
                            Fits: {{ $item->brand }} {{ $item->model }}
                            {{ $item->compat_year_from ?? $item->year_from }}@if(($item->compat_year_to ?? $item->year_to) != ($item->compat_year_from ?? $item->year_from))–{{ $item->compat_year_to ?? $item->year_to }}@endif
                        </div>
                        @endif
                        @if(!empty($item->engine_code_oem))
                        <div style="font-size:9px;color:#999;">OEM: {{ $item->engine_code_oem }}{{ $item->transmission_code_oem ? ' / '.$item->transmission_code_oem : '' }}</div>
                        @endif
                        @else
                        <div style="font-family:monospace;font-size:10px;">{{ $item->vin ?? 'N/A' }}</div>
                        @endif
                    </td>
                    @if(!$isVehicleSale)
                    <td><span class="grade-badge grade-{{ $item->condition_grade }}">{{ $item->condition_grade }}</span></td>
                    @endif
                    <td style="text-align:center;">{{ $item->qty }}</td>
                    @if($copyKey !== 'waybill')
                    <td style="text-align:right;">{{ $item->unit_price_fmt }}</td>
                    <td>{{ $item->total_fmt }}</td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>

        @if($copyKey === 'waybill')
        <div style="border: 2px dashed #8a6d1f; border-radius: 8px; padding: 10px 14px; margin-bottom: 12px; background: #fffbf0;">
            <p style="font-size: 11px; color: #5d4e1f; line-height: 1.6;">
                <strong>⚠ WAYBILL / PACKING LIST — NOT A PRICED DOCUMENT.</strong>
                This document lists the full description and quantity of goods being moved from
                <strong>{{ $saleLocation }}</strong> to their destination. It serves as evidence of
                lawful possession of these goods in transit and should be presented if requested by
                police or other authorities during movement between locations. No prices are shown
                on this copy — see Customer or Accounts Copy for pricing.
            </p>
        </div>
        @endif

        @if($copyKey !== 'waybill')
        <div class="inv-totals">
            <div class="totals-box">
                <table>
                    <tr><td>Subtotal:</td><td>{{ $subtotalFmt }}</td></tr>
                    @if(($discountLocal ?? 0) > 0)
                    <tr><td>{{ $discountLabel }}</td><td>-{{ $discountFmt }}</td></tr>
                    @endif
                    @if(($returnCreditApplied ?? 0) > 0)
                    <tr style="color:#1a6b3c;"><td>Return Credit Applied:</td><td>-{{ $returnCreditFmt }}</td></tr>
                    @endif
                    <tr class="total-row"><td><strong>TOTAL:</strong></td><td><strong>{{ $totalFmt ?? $subtotalFmt }}</strong></td></tr>
                </table>
            </div>
        </div>

        <div class="inv-totals" style="margin-top: -6px;">
            <div class="totals-box" style="border-top: 1px dashed #ccc; padding-top: 8px;">
                <table>
                    @if($printPaySummary && $printPaySummary['payments']->where('status', 'confirmed')->count())
                    @foreach($printPaySummary['payments']->where('status', 'confirmed') as $p)
                    <tr style="font-size: 12px; color: #555;">
                        <td>Less: Payment ({{ $p->payment_method }}, {{ \Carbon\Carbon::parse($p->created_at)->format('d M Y') }})</td>
                        <td>– {{ $currency['symbol'] }}{{ $currency['code'] === 'NGN' ? number_format($p->amount_local) : number_format($p->amount_local, 2) }}</td>
                    </tr>
                    @endforeach
                    @else
                    <tr style="font-size: 12px; color: #555;">
                        <td>Payment Applied (Paid at point of sale)</td>
                        <td>– {{ $totalFmt ?? $subtotalFmt }}</td>
                    </tr>
                    @endif
                    <tr class="total-row" style="{{ ($printPaySummary['balanceDue'] ?? 0) > 0 ? 'color:#c0392b;' : 'color:#1b9e5c;' }}">
                        <td><strong>{{ ($printPaySummary['balanceDue'] ?? 0) > 0 ? 'BALANCE DUE:' : 'BALANCE:' }}</strong></td>
                        <td><strong>
                            {{ ($printPaySummary['balanceDue'] ?? 0) > 0
                                ? $currency['symbol'] . ($currency['code'] === 'NGN' ? number_format($printPaySummary['balanceDue']) : number_format($printPaySummary['balanceDue'], 2))
                                : $currency['symbol'] . '0' }}
                        </strong></td>
                    </tr>
                </table>
            </div>
        </div>
        @endif

        @if($isVehicleSale)
        <div class="inv-warranty" style="background:#fdecea; border-color:#f5b3ab;">
            <p><strong style="color:#a32d2d;">⚠ SOLD AS-IS — NO WARRANTY:</strong> This vehicle is sold as-is, with no warranty implied or expressed, either written or verbal, covering mechanical, electrical, or any other condition. Buyer accepts full responsibility for the vehicle's condition from the point of sale. This receipt does not constitute a warranty of any kind.</p>
            @if(!empty($order->notes))<p style="margin-top:4px;"><strong>Notes:</strong> {{ $order->notes }}</p>@endif
        </div>
        @else
        <div class="inv-warranty">
            <p><strong>⚠ Warranty:</strong> {{ $businessInfo['warranty'] }}. Warranty is void if part is disassembled, modified, or damaged after installation. No returns on any electrical parts (brain box/PCM, alternators, starters, fuel pumps, sensors, etc.) — buyer must fully test before leaving our facility. The warranty period applies only to engines and mechanical parts that cannot be tested unless installed. No warranty on automatic transmissions. Proof of purchase (this invoice) required for all warranty claims.</p>
            @if(!empty($order->notes))<p style="margin-top:4px;"><strong>Notes:</strong> {{ $order->notes }}</p>@endif
        </div>
        @endif

        <div class="inv-signatures">
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-label">Issued By (Staff)</div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-label">
                    @if($copyKey === 'gate') Security Officer
                    @elseif($copyKey === 'accounts') Accounts Officer
                    @elseif($copyKey === 'warehouse') Warehouse Officer
                    @else {{ $isVehicleSale ? 'Buyer Signature' : 'Customer Signature' }}
                    @endif
                </div>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <div class="sig-label">
                    @if($copyKey === 'gate') Gate Stamp / Time Out
                    @else Authorised Stamp
                    @endif
                </div>
            </div>
        </div>

        <div class="inv-footer">
            @if(!empty($footerAddresses))
            <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap: 4px 16px; text-align:left; max-width: 480px; margin: 0 auto 8px; border-top: 1px solid #ccc; border-bottom: 1px solid #ccc; padding: 6px 0;">
                @foreach($footerAddresses as $addr)
                    @php
                        // Split "Label — Address details" into a bold
                        // label line and a normal address line, for a
                        // cleaner, easier-to-scan layout than one long
                        // run-on line.
                        $parts = explode(' — ', $addr, 2);
                    @endphp
                    <div style="font-size: 10px; line-height: 1.5;">
                        <div style="font-weight: 700; color:#000;">📍 {{ $parts[0] }}</div>
                        <div style="color:#000;">{{ $parts[1] ?? '' }}</div>
                    </div>
                @endforeach
            </div>
            @endif
            <p>
                Thank you for your business! · <span class="website">autozenithparts.com</span>
                · WhatsApp: {{ $businessInfo['phone'] }}<br>
                This is a computer-generated receipt. No physical signature required unless specified.
                @if($copyKey === 'gate') · <strong style="color:#b71c1c;">GATE PASS — Present to security on exit</strong>@endif
            </p>
        </div>

    </div>
</div>
@endforeach

</div>

<script>
function showCopy(which) {
    const copies = ['customer','warehouse','accounts','gate','waybill'];
    const btns   = ['customer','warehouse','accounts','gate','waybill','all'];
    btns.forEach(b => { document.getElementById('btn-' + b)?.classList.remove('active'); });
    document.getElementById('btn-' + which)?.classList.add('active');
    copies.forEach(c => {
        const el = document.getElementById('copy-' + c);
        if (!el) return;
        if (which === 'all') { el.classList.remove('hidden'); }
        else { el.classList.toggle('hidden', c !== which); }
    });
}
showCopy('all');
document.addEventListener('keydown', function(e) {
    if (e.key === '1') showCopy('customer');
    if (e.key === '2') showCopy('warehouse');
    if (e.key === '3') showCopy('accounts');
    if (e.key === '4') showCopy('gate');
    if (e.key === '5') showCopy('waybill');
    if (e.key === 'a' || e.key === 'A') showCopy('all');
    if (e.key === 'p' || e.key === 'P') window.print();
});
</script>
</body>
</html>
