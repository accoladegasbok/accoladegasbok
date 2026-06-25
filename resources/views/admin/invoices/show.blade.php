{{-- FILE: resources/views/admin/invoices/show.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice {{ $invoiceNo }} — Auto Zenith Parts</title>
<style>
/* ── Reset & Base ─────────────────────────────────────── */
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: 'Arial', sans-serif; font-size: 11px; color: #1a1a2e; background: #f0f2f5; }

/* ── Print Controls (screen only) ────────────────────── */
.print-controls {
    position: fixed; top: 0; left: 0; right: 0; z-index: 999;
    background: #0d1b2a; color: white; padding: 12px 24px;
    display: flex; align-items: center; gap: 16px; flex-wrap: wrap;
    box-shadow: 0 2px 12px rgba(0,0,0,0.3);
}
.print-controls h2 { font-size: 14px; font-weight: 700; color: #c9a84c; margin-right: 8px; }
.copy-btn {
    padding: 7px 16px; border-radius: 6px; border: 2px solid transparent;
    font-size: 11px; font-weight: 700; cursor: pointer; transition: all 0.2s;
    text-transform: uppercase; letter-spacing: 0.5px;
}
.copy-btn.active { background: #c9a84c; color: #0d1b2a; border-color: #c9a84c; }
.copy-btn:not(.active) { background: transparent; color: #aaa; border-color: #444; }
.copy-btn:hover:not(.active) { border-color: #c9a84c; color: #c9a84c; }
.print-all-btn {
    padding: 7px 16px; background: #1a6b3c; color: white; border: none;
    border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer;
    text-transform: uppercase; letter-spacing: 0.5px;
}
.print-all-btn:hover { background: #22873d; }
.print-single-btn {
    padding: 7px 16px; background: #0d47a1; color: white; border: none;
    border-radius: 6px; font-size: 11px; font-weight: 700; cursor: pointer;
    text-transform: uppercase; letter-spacing: 0.5px;
}
.print-single-btn:hover { background: #1565c0; }
.sep { color: #444; }

/* ── Invoice Page Wrapper ─────────────────────────────── */
.invoice-pages { margin-top: 64px; padding: 20px; }

/* ── Single Invoice Card ──────────────────────────────── */
.invoice {
    background: white; width: 210mm; min-height: 148mm;
    margin: 0 auto 20px; padding: 16mm 14mm;
    box-shadow: 0 2px 16px rgba(0,0,0,0.12);
    position: relative; page-break-after: always;
}
.invoice:last-child { page-break-after: auto; }

/* ── Copy Banner ──────────────────────────────────────── */
.copy-banner {
    position: absolute; top: 0; right: 0;
    padding: 6px 20px; font-size: 9px; font-weight: 700;
    letter-spacing: 1.5px; text-transform: uppercase; color: white;
    border-bottom-left-radius: 8px;
}
.copy-customer  .copy-banner { background: #0d47a1; }
.copy-warehouse .copy-banner { background: #1a6b3c; }
.copy-accounts  .copy-banner { background: #6a1b9a; }
.copy-gate      .copy-banner { background: #b71c1c; }

/* ── Watermark ────────────────────────────────────────── */
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

/* ── Content above watermark ──────────────────────────── */
.invoice-content { position: relative; z-index: 1; }

/* ── Header ───────────────────────────────────────────── */
.inv-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; border-bottom: 2px solid #0d1b2a; padding-bottom: 12px; }
.brand-block .brand-name { font-size: 22px; font-weight: 900; color: #0d1b2a; letter-spacing: 2px; }
.brand-block .brand-name span { color: #c9a84c; }
.brand-block .tagline { font-size: 8px; color: #666; letter-spacing: 2px; text-transform: uppercase; margin-top: 1px; }
.brand-block .company { font-size: 9px; color: #444; margin-top: 3px; }
.brand-block .address { font-size: 8.5px; color: #555; margin-top: 2px; line-height: 1.5; }
.brand-block .contact { font-size: 8.5px; color: #555; margin-top: 3px; }

.inv-meta { text-align: right; }
.inv-meta .inv-title { font-size: 18px; font-weight: 900; color: #0d1b2a; letter-spacing: 3px; text-transform: uppercase; }
.inv-meta table { margin-top: 6px; }
.inv-meta td { padding: 1.5px 0; font-size: 9px; }
.inv-meta td:first-child { color: #777; padding-right: 10px; }
.inv-meta td:last-child { font-weight: 700; color: #0d1b2a; }

/* ── Customer & Payment Info ──────────────────────────── */
.inv-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; }
.info-box { background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 6px; padding: 8px 10px; }
.info-box h4 { font-size: 8px; text-transform: uppercase; letter-spacing: 1.5px; color: #888; margin-bottom: 5px; border-bottom: 1px solid #e0e0e0; padding-bottom: 3px; }
.info-box p { font-size: 9.5px; line-height: 1.6; color: #333; }
.info-box strong { color: #0d1b2a; }

/* ── Items Table ──────────────────────────────────────── */
.items-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
.items-table thead tr { background: #0d1b2a; color: white; }
.items-table thead th { padding: 7px 8px; text-align: left; font-size: 8.5px; letter-spacing: 0.8px; text-transform: uppercase; }
.items-table thead th:last-child { text-align: right; }
.items-table tbody tr:nth-child(even) { background: #f8f9fa; }
.items-table tbody tr { border-bottom: 1px solid #eee; }
.items-table tbody td { padding: 7px 8px; font-size: 9.5px; vertical-align: top; }
.items-table tbody td:last-child { text-align: right; font-weight: 700; }
.part-name { font-weight: 700; color: #0d1b2a; }
.part-sub { font-size: 8px; color: #888; margin-top: 1px; }
.grade-badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 7.5px; font-weight: 700; }
.grade-A { background: #e8f5e9; color: #2e7d32; }
.grade-B { background: #fff3e0; color: #e65100; }
.grade-C { background: #fce4ec; color: #c62828; }
.grade-New { background: #e3f2fd; color: #1565c0; }

/* ── Totals ───────────────────────────────────────────── */
.inv-totals { display: flex; justify-content: flex-end; margin-bottom: 14px; }
.totals-box { width: 220px; }
.totals-box table { width: 100%; }
.totals-box td { padding: 4px 8px; font-size: 10px; }
.totals-box td:last-child { text-align: right; font-weight: 700; }
.totals-box .total-row { background: #0d1b2a; color: white; border-radius: 4px; }
.totals-box .total-row td { font-size: 12px; padding: 6px 8px; }

/* ── Payment & Bank ───────────────────────────────────── */
.inv-payment { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px; }
.payment-box { border: 1px solid #e0e0e0; border-radius: 6px; padding: 8px 10px; }
.payment-box h4 { font-size: 8px; text-transform: uppercase; letter-spacing: 1.5px; color: #888; margin-bottom: 5px; border-bottom: 1px solid #e0e0e0; padding-bottom: 3px; }
.payment-box p { font-size: 9px; line-height: 1.7; color: #333; }

/* ── Warranty & Notes ─────────────────────────────────── */
.inv-warranty { background: #fff8e1; border: 1px solid #ffe082; border-radius: 6px; padding: 7px 10px; margin-bottom: 12px; }
.inv-warranty p { font-size: 8.5px; color: #5d4037; line-height: 1.5; }
.inv-warranty strong { color: #3e2723; }

/* ── Gate Pass Section (copy 4 only) ─────────────────── */
.gate-pass-section { border: 2px dashed #b71c1c; border-radius: 8px; padding: 10px; margin-bottom: 12px; }
.gate-pass-section h3 { font-size: 12px; font-weight: 900; color: #b71c1c; text-align: center; letter-spacing: 3px; margin-bottom: 8px; }
.gate-pass-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; }
.gate-field { border-bottom: 1px solid #999; padding-bottom: 16px; }
.gate-field label { font-size: 8px; color: #666; display: block; margin-bottom: 2px; text-transform: uppercase; }

/* ── Signatures ───────────────────────────────────────── */
.inv-signatures { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-top: 8px; }
.sig-box { text-align: center; }
.sig-line { border-top: 1px solid #999; margin-top: 28px; padding-top: 4px; }
.sig-label { font-size: 8px; color: #777; text-transform: uppercase; letter-spacing: 0.8px; }

/* ── Footer ───────────────────────────────────────────── */
.inv-footer { text-align: center; margin-top: 12px; padding-top: 8px; border-top: 1px solid #eee; }
.inv-footer p { font-size: 8px; color: #999; line-height: 1.6; }
.inv-footer .website { font-weight: 700; color: #c9a84c; }

/* ── Hidden copies ────────────────────────────────────── */
.hidden { display: none !important; }

/* ── PRINT STYLES ─────────────────────────────────────── */
@media print {
    body { background: white; }
    .print-controls { display: none !important; }
    .invoice-pages { margin-top: 0; padding: 0; }
    .invoice { box-shadow: none; margin: 0; page-break-after: always; }
    .hidden { display: none !important; }
}
</style>
</head>
<body>

{{-- ── Print Controls ──────────────────────────────────── --}}
<div class="print-controls" id="printControls">
    <h2>INVOICE {{ $invoiceNo }}</h2>
    @if(isset($invoice) && in_array(session('staff_role'), ['admin', 'manager']))
    <a href="{{ route('admin.invoices.manual.edit', $invoice->id) }}" style="background:#c9a84c;color:#0d1b2a;padding:7px 16px;border-radius:6px;font-size:11px;font-weight:700;text-decoration:none;text-transform:uppercase;letter-spacing:0.5px;">
        ✎ Edit Invoice
    </a>
    @endif
    <span class="sep">|</span>
    <span style="font-size:11px;color:#aaa;">Select copy to print:</span>
    <button class="copy-btn active" onclick="showCopy('customer')" id="btn-customer">
        📄 Customer Copy
    </button>
    <button class="copy-btn" onclick="showCopy('warehouse')" id="btn-warehouse">
        🏭 Warehouse Copy
    </button>
    <button class="copy-btn" onclick="showCopy('accounts')" id="btn-accounts">
        📊 Accounts Copy
    </button>
    <button class="copy-btn" onclick="showCopy('gate')" id="btn-gate">
        🚧 Gate Pass
    </button>
    <button class="copy-btn active" onclick="showCopy('all')" id="btn-all" style="border-color:#c9a84c;color:#c9a84c;">
        📋 All 4 Copies
    </button>

    <span class="sep">|</span>
    <button class="print-single-btn" onclick="window.print()">🖨 Print</button>
    <a href="{{ url()->previous() }}" style="color:#aaa;font-size:11px;text-decoration:none;">← Back</a>
</div>

{{-- ── Invoice Pages ────────────────────────────────────── --}}
<div class="invoice-pages">

@php
$copies = [
    'customer'  => ['label' => 'CUSTOMER COPY',           'color' => '#0d47a1'],
    'warehouse' => ['label' => 'WAREHOUSE CONTROL COPY',  'color' => '#1a6b3c'],
    'accounts'  => ['label' => 'ACCOUNTS COPY',           'color' => '#6a1b9a'],
    'gate'      => ['label' => 'SECURITY / GATE PASS',    'color' => '#b71c1c'],
];
$createdAt = $order->created_at ?? now();
$paymentMethod = $order->payment_method ?? 'Cash';

// ── QR code target — links back to wherever this invoice actually
// lives, whether it's a manual/service invoice row or an order-
// derived one. Same QR appears on all 4 copies.
$qrUrl = isset($invoice)
    ? route('admin.invoices.show.manual', $invoice->id)
    : (isset($order) ? route('admin.invoices.show', $order->id) : url()->current());
@endphp

@foreach($copies as $copyKey => $copyInfo)
<div class="invoice copy-{{ $copyKey }}" id="copy-{{ $copyKey }}">

    {{-- Watermark --}}
    <div class="watermark">{{ $copyInfo['label'] }}</div>

    {{-- Copy banner --}}
    <div class="copy-banner">{{ $copyInfo['label'] }}</div>

    <div class="invoice-content">

        {{-- Header --}}
        <div class="inv-header">
            <div class="brand-block">
                <div class="brand-name">AUTO <span>ZENITH</span> PARTS</div>
                <div class="tagline">Quality Used Auto Parts · Engine · Gearbox · Body</div>
                <div class="company">{{ $businessInfo['company'] }}{{ $businessInfo['rc'] ? ' · ' . $businessInfo['rc'] : '' }}</div>
                <div class="address">{{ $businessInfo['address'] }}</div>
                <div class="contact">📞 {{ $businessInfo['phone'] }} · 🌐 autozenithparts.com</div>
            </div>
            <div class="inv-meta">
                <div class="inv-title">INVOICE</div>
                <table>
                    <tr><td>Invoice No:</td><td>{{ $invoiceNo }}</td></tr>
                    <tr><td>Date:</td><td>{{ \Carbon\Carbon::parse($createdAt)->format('d M Y') }}</td></tr>
                    <tr><td>Time:</td><td>{{ \Carbon\Carbon::parse($createdAt)->format('h:i A') }}</td></tr>
                    <tr><td>Location:</td><td>{{ $location }}</td></tr>
                    <tr><td>Currency:</td><td>{{ $currency['code'] }}</td></tr>
                </table>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=60x60&data={{ urlencode($qrUrl) }}" alt="QR" style="margin-top:6px;">
            </div>
        </div>

        {{-- Customer & Payment Info --}}
        <div class="inv-parties">
            <div class="info-box">
                <h4>Bill To</h4>
                <p>
                    <strong>{{ $order->customer_name ?? 'Walk-in Customer' }}</strong><br>
                    @if(!empty($order->customer_phone)) 📞 {{ $order->customer_phone }}<br>@endif
                    @if(!empty($order->customer_email)) ✉ {{ $order->customer_email }}<br>@endif
                    @if(!empty($order->customer_address)) {{ $order->customer_address }}@endif
                </p>
            </div>
            <div class="info-box">
                <h4>Payment Details</h4>
                <p>
                    <strong>Method:</strong> {{ $paymentMethod }}<br>
                    <strong>Status:</strong> {{ $order->payment_status ?? 'PAID' }}<br>
                    @if(!empty($businessInfo['bank']))
                    <strong>{{ $businessInfo['bank'] }}:</strong> {{ $businessInfo['account'] }}<br>
                    <strong>Name:</strong> {{ $businessInfo['acct_name'] }}
                    @endif
                </p>
            </div>
        </div>

        {{-- Gate Pass extra info --}}
        @if($copyKey === 'gate')
        <div class="gate-pass-section">
            <h3>🚧 SECURITY / GATE PASS</h3>
            <div class="gate-pass-grid">
                <div class="gate-field">
                    <label>Customer Name</label>
                    <strong style="font-size:10px;">{{ $order->customer_name ?? '________________' }}</strong>
                </div>
                <div class="gate-field">
                    <label>Phone Number</label>
                    <strong style="font-size:10px;">{{ $order->customer_phone ?? '________________' }}</strong>
                </div>
                <div class="gate-field">
                    <label>Vehicle / Plate No.</label>
                    &nbsp;
                </div>
                <div class="gate-field">
                    <label>No. of Items</label>
                    <strong style="font-size:10px;">{{ $lineItems->count() }} item(s)</strong>
                </div>
                <div class="gate-field">
                    <label>Invoice No.</label>
                    <strong style="font-size:10px;">{{ $invoiceNo }}</strong>
                </div>
                <div class="gate-field">
                    <label>Exit Time</label>
                    &nbsp;
                </div>
            </div>
        </div>
        @endif

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:30px">#</th>
                    <th>Part Description</th>
                    <th style="width:60px">Part Code</th>
                    <th style="width:40px">Grade</th>
                    <th style="width:30px">Qty</th>
                    <th style="width:80px">Unit Price</th>
                    <th style="width:80px">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lineItems as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>
                        <div class="part-name">{{ $item->part_name }}</div>
                        <div class="part-sub">
                            @if(!empty($item->brand)){{ strtoupper($item->brand) }} {{ strtoupper($item->model) }} {{ $item->year_from }}@if($item->year_to && $item->year_to != $item->year_from)–{{ $item->year_to }}@endif · @endif
                            @if(!empty($item->engine_code_oem))Engine: {{ $item->engine_code_oem }} · @endif
                            @if(!empty($item->part_category)){{ $item->part_category }}@endif
                        </div>
                    </td>
                    <td style="font-family:monospace;font-size:8.5px;">{{ $item->part_code }}</td>
                    <td>
                        <span class="grade-badge grade-{{ $item->condition_grade }}">{{ $item->condition_grade }}</span>
                    </td>
                    <td style="text-align:center;">{{ $item->qty }}</td>
                    <td style="text-align:right;">{{ $item->unit_price_fmt }}</td>
                    <td>{{ $item->total_fmt }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="inv-totals">
            <div class="totals-box">
                <table>
                    <tr><td>Subtotal:</td><td>{{ $subtotalFmt }}</td></tr>
                    <tr><td>Discount:</td><td>{{ $currency['symbol'] }}0</td></tr>
                    <tr class="total-row"><td><strong>TOTAL:</strong></td><td><strong>{{ $subtotalFmt }}</strong></td></tr>
                </table>
            </div>
        </div>

        {{-- Warranty --}}
        <div class="inv-warranty">
            <p><strong>⚠ Warranty:</strong> {{ $businessInfo['warranty'] }}. Warranty is void if part is disassembled, modified, or damaged after installation. Proof of purchase (this invoice) required for all warranty claims.</p>
            @if(!empty($order->notes))<p style="margin-top:4px;"><strong>Notes:</strong> {{ $order->notes }}</p>@endif
        </div>

        {{-- Signatures --}}
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
                    @else Customer Signature
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

        {{-- Footer --}}
        <div class="inv-footer">
            <p>
                Thank you for your business! · <span class="website">autozenithparts.com</span>
                · WhatsApp: {{ $businessInfo['phone'] }}<br>
                This is a computer-generated invoice. No physical signature required unless specified.
                @if($copyKey === 'gate') · <strong style="color:#b71c1c;">GATE PASS — Present to security on exit</strong>@endif
            </p>
        </div>

    </div>{{-- end invoice-content --}}
</div>{{-- end invoice --}}
@endforeach

</div>{{-- end invoice-pages --}}

<script>
// ── Show/hide copies ─────────────────────────────────────
function showCopy(which) {
    const copies = ['customer','warehouse','accounts','gate'];
    const btns   = ['customer','warehouse','accounts','gate','all'];

    // Update buttons
    btns.forEach(b => {
        document.getElementById('btn-' + b)?.classList.remove('active');
    });
    document.getElementById('btn-' + which)?.classList.add('active');

    // Show/hide invoice divs
    copies.forEach(c => {
        const el = document.getElementById('copy-' + c);
        if (!el) return;
        if (which === 'all') {
            el.classList.remove('hidden');
        } else {
            el.classList.toggle('hidden', c !== which);
        }
    });
}

// Default: show all
showCopy('all');

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.key === '1') showCopy('customer');
    if (e.key === '2') showCopy('warehouse');
    if (e.key === '3') showCopy('accounts');
    if (e.key === '4') showCopy('gate');
    if (e.key === 'a' || e.key === 'A') showCopy('all');
    if (e.key === 'p' || e.key === 'P') window.print();
});
</script>
</body>
</html>
