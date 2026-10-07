{{-- FILE: resources/views/admin/invoices/_document.blade.php
     THE invoice / receipt body. One template for every copy on screen AND the downloaded PDF
     (invoice-pdf.blade.php includes this same file), so what is printed and what is downloaded
     can no longer drift apart.

     Expects the usual document variables from the parent view, plus:
       $copyKey   customer | warehouse | accounts | gate | waybill
       $docMode   'screen' (default) or 'pdf'  (the QR code is only drawn on screen)
     It is an INVOICE ("NOT YET PAID") while any balance remains and becomes a RECEIPT ("PAID")
     once the balance is zero. The reference number never changes. --}}
@php
    $docMode      = $docMode ?? 'screen';
    // Public pages (customer receipt link): hide email, address and the staff name.
    $docPublic    = $docPublic ?? false;
    $isVehicleSale = ($invoiceType ?? null) === 'vehicle';
    $money        = fn ($n) => \App\Http\Controllers\Admin\InvoiceController::formatLocal((float) $n, $currency['code']);

    // Payment state — computed once by the parent view when possible, otherwise here.
    $docPay       = $docPay ?? \App\Http\Controllers\Admin\InvoiceController::documentPaymentState($order ?? null, $invoice ?? null, $invoiceId ?? null);
    $docIsReceipt = ($docPay['status'] ?? 'UNPAID') === 'PAID';
    $isWaybill    = $copyKey === 'waybill';

    if ($isWaybill) {
        $docTitle = 'WAYBILL / PACKING LIST';
    } elseif ($isVehicleSale) {
        $docTitle = $docIsReceipt ? 'VEHICLE SALE RECEIPT' : 'VEHICLE SALE INVOICE';
    } else {
        $docTitle = $docIsReceipt ? 'RECEIPT' : 'INVOICE';
    }
    $docNoLabel = $isWaybill ? 'Document No' : ($docIsReceipt ? 'Receipt No' : 'Invoice No');

    $stampClass = $docPay['status'] === 'PAID' ? '' : ($docPay['status'] === 'PARTIAL' ? 'partial' : 'unpaid');
    $stampText  = $docPay['status'] === 'PAID' ? 'PAID' : ($docPay['status'] === 'PARTIAL' ? 'PARTIALLY PAID' : 'NOT YET PAID');

    $docCreatedAt = $createdAt ?? now();
    $docLocation  = $location ?? $saleLocation ?? '';
    $issuedBy     = ($invoice->created_by ?? $order->created_by ?? null);
    $docNotes     = ($invoice->notes ?? $order->notes ?? null);
    $docQrUrl     = $docQrUrlOverride ?? (isset($order) && $order
        ? route('admin.invoices.show', $order->id)
        : (isset($invoice) && $invoice ? route('admin.invoices.show.manual', $invoice->id) : url()->current()));

    $cName  = $customerInfo->name    ?? ($order->customer_name ?? null)    ?: 'Walk-in Customer';
    $cPhone = $customerInfo->phone   ?? ($order->customer_phone ?? null);
    $cEmail = $customerInfo->email   ?? ($order->customer_email ?? null);
    $cAddr  = $customerInfo->address ?? ($order->customer_address ?? null);

    // Company contact email, printed on every document (header and footer)
    $docEmail = $businessInfo['email'] ?? 'info@autozenithparts.com';

    // Company logo (Nigeria documents show the registration line inside the logo, so it is not repeated as text)
    $logoUri = \App\Http\Controllers\Admin\InvoiceController::logoDataUri(!empty($businessInfo['rc']));

    // Short warranty text for the document footer (full terms: autozenithparts.com/warranty)
    $docWarranty = 'Limited Warranty: Engines 90 days (USA) / 30 days (West Africa). Transmissions 90 days (USA); in West Africa 30 days only when installed by our recommended technician with our recommended oil. No warranty on transmissions bought and taken away; inspect properly before purchase. Mechanical parts 30 days. Electrical 14 days, exchange or credit only. Labor not covered. Void if the Auto Zenith tag or mark is removed or the part is disassembled. Returns within 7 days, uninstalled, 20% restocking fee. Full terms: autozenithparts.com/warranty';
@endphp

<div class="doc">

    {{-- ── Header ─────────────────────────────────────────────── --}}
    <table>
        <tr>
            <td style="width:58%;">
                @if($logoUri)
                <img src="{{ $logoUri }}" alt="Auto Zenith Parts" style="width:200px;">
                @else
                <div class="doc-brand">AUTO <span>ZENITH</span> PARTS</div>
                @endif
                <div class="doc-tagline">{{ $isVehicleSale ? 'Quality Used Vehicles · Sold As-Is' : 'Quality Used Auto Parts · Engine · Gearbox · Body' }}</div>
                <div class="doc-company">{{ $businessInfo['company'] ?? 'Auto Zenith Parts' }}{{ (!$logoUri && !empty($businessInfo['rc'])) ? ' · ' . $businessInfo['rc'] : '' }}</div>
                <div class="doc-small">{{ $businessInfo['address'] ?? '' }}</div>
                <div class="doc-small">Tel: {{ $businessInfo['phone'] ?? '' }}</div>
                <div class="doc-small">{{ $docEmail }} · autozenithparts.com</div>
            </td>
            <td style="width:42%; text-align:right;">
                <div class="doc-title">
                    {{ $docTitle }}
                    @if(!$isWaybill && isset($revisionNumber) && $revisionNumber > 1)
                    <span class="doc-rev">REV-{{ str_pad($revisionNumber - 1, 2, '0', STR_PAD_LEFT) }}</span>
                    @endif
                </div>
                @if(!$isWaybill)<div class="doc-stamp {{ $stampClass }}">{{ $stampText }}</div>@endif
                <table class="doc-meta" style="margin-top:4px;">
                    <tr><td class="k">{{ $docNoLabel }}:</td><td class="v">{{ $invoiceNo }}</td></tr>
                    <tr><td class="k">Date:</td><td class="v">{{ \Carbon\Carbon::parse($docCreatedAt)->format('d M Y') }}</td></tr>
                    <tr><td class="k">Time:</td><td class="v">{{ \Carbon\Carbon::parse($docCreatedAt)->format('h:i A') }}</td></tr>
                    <tr><td class="k">Location:</td><td class="v">{{ $docLocation }}</td></tr>
                    <tr><td class="k">Currency:</td><td class="v">{{ $currency['code'] }}</td></tr>
                    @if($issuedBy && !$docPublic)<tr><td class="k">Issued By:</td><td class="v">{{ $issuedBy }}</td></tr>@endif
                    @if(isset($lastEditLog) && $lastEditLog)
                    <tr><td class="k">Last Edited By:</td><td class="v">{{ $lastEditLog->edited_by }} ({{ $lastEditLog->staff_role }})</td></tr>
                    @if(!empty($lastEditLog->override_by))<tr><td class="k">Approved By:</td><td class="v">{{ $lastEditLog->override_by }}</td></tr>@endif
                    @endif
                </table>
                @if($docMode === 'screen')
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=65x65&data={{ urlencode($docQrUrl) }}" alt="QR" style="margin-top:6px;">
                @endif
            </td>
        </tr>
    </table>

    {{-- ── Parties ────────────────────────────────────────────── --}}
    <table style="margin-top:12px;">
        <tr>
            <td class="doc-box" style="width:49%;">
                <h4>{{ $isVehicleSale ? 'Buyer' : 'Bill To' }}</h4>
                <div class="nm">{{ $cName }}</div>
                @if(!empty($cPhone))<div class="doc-small">Tel: {{ $cPhone }}</div>@endif
                @if(!empty($cEmail) && !$docPublic)<div class="doc-small">{{ $cEmail }}</div>@endif
                @if(!empty($cAddr) && !$docPublic)<div class="doc-small">{{ $cAddr }}</div>@endif
            </td>
            <td style="width:2%;"></td>
            <td class="doc-box" style="width:49%;">
                <h4>Payment Details</h4>
                <div class="doc-small"><strong>Method:</strong> {{ $paymentMethod ?? 'Cash' }}</div>
                @if(!$isWaybill)<div class="doc-small"><strong>Status:</strong> {{ $stampText }}</div>@endif
                @if(!empty($businessInfo['bank']))
                <div class="doc-small"><strong>{{ $businessInfo['bank'] }}:</strong> {{ $businessInfo['account'] ?? '' }}</div>
                @if(!empty($businessInfo['acct_name']))<div class="doc-small"><strong>Name:</strong> {{ $businessInfo['acct_name'] }}</div>@endif
                @endif
            </td>
        </tr>
    </table>

    {{-- ── Gate pass block ────────────────────────────────────── --}}
    @if($copyKey === 'gate')
    <table class="doc-gate" style="margin-top:10px;">
        <tr><th colspan="3">SECURITY / GATE PASS</th></tr>
        <tr>
            <td><span class="lbl">Customer Name</span>{{ $cName }}</td>
            <td><span class="lbl">Phone Number</span>{{ $cPhone ?: '________________' }}</td>
            <td><span class="lbl">Vehicle / Plate No.</span>&nbsp;</td>
        </tr>
        <tr>
            <td><span class="lbl">No. of Items</span>{{ $lineItems->count() }} item(s)</td>
            <td><span class="lbl">{{ $docNoLabel }}</span>{{ $invoiceNo }}</td>
            <td><span class="lbl">Exit Time</span>&nbsp;</td>
        </tr>
    </table>
    @endif

    {{-- ── Line items ─────────────────────────────────────────── --}}
    <table class="doc-items">
        <thead>
            <tr>
                <th style="width:4%;">#</th>
                <th>{{ $isVehicleSale ? 'Vehicle Description' : 'Description' }}</th>
                @if(!$isVehicleSale)<th style="width:7%;">Grade</th>@endif
                <th style="width:6%;" class="c">Qty</th>
                @if(!$isWaybill)
                <th style="width:13%;" class="r">Unit Price</th>
                <th style="width:12%;" class="r">Discount</th>
                <th style="width:14%;" class="r">Amount</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($lineItems as $i => $item)
            @php
                $lineDisc = (float) ($item->discount_amount_local ?? 0);
                $hasCode  = !empty($item->part_code) && strtoupper((string) $item->part_code) !== 'MANUAL';
                $fitFrom  = $item->compat_year_from ?? $item->year_from ?? null;
                $fitTo    = $item->compat_year_to   ?? $item->year_to   ?? null;
                $addons   = \App\Data\EngineAddons::labels($item->inclusions ?? null);
            @endphp
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    {{-- Full description: never cut off, wraps onto as many lines as it needs. --}}
                    <div class="doc-name">{{ $item->part_name }}
                        @if(!empty($item->returned) && !$isWaybill)
                        <span class="doc-flag ret">RETURNED - REFUNDED{{ !empty($item->return_refund_method ?? null) ? ' VIA ' . strtoupper(str_replace('_', ' ', $item->return_refund_method)) : '' }}</span>
                        @endif
                    </div>

                    @if($isVehicleSale)
                        <div class="doc-ids">VIN <strong>{{ $item->vin ?? 'N/A' }}</strong></div>
                        <div class="doc-muted">
                            @if(!empty($item->colour)){{ $item->colour }}@endif
                            @if(!empty($item->mileage)) · {{ number_format($item->mileage) }} miles @endif
                        </div>
                    @else
                        {{-- Stock number and OUR reference, directly under the description --}}
                        @if($hasCode || !empty($item->source_ref))
                        <div class="doc-ids">
                            @if($hasCode)Stock # <strong>{{ $item->part_code }}</strong>@endif
                            @if(!empty($item->source_ref))@if($hasCode) &nbsp;·&nbsp; @endif Ref <strong>{{ $item->source_ref }}</strong>@endif
                        </div>
                        @endif
                        @if(!empty($item->brand))
                        <div class="doc-muted">Fits: {{ strtoupper($item->brand) }} {{ strtoupper($item->model ?? '') }}
                            {{ $fitFrom ?? '' }}@if($fitTo && $fitTo != $fitFrom)–{{ $fitTo }}@endif</div>
                        @endif
                        @if(!empty($item->engine_code_oem))
                        <div class="doc-muted">OEM: {{ $item->engine_code_oem }}{{ !empty($item->transmission_code_oem ?? null) ? ' / ' . $item->transmission_code_oem : '' }}</div>
                        @endif
                        @if(!empty($addons))
                        <div class="doc-muted">Includes: {{ implode(', ', $addons) }}</div>
                        @endif
                        @if(!empty($item->donor_vin) && !$isWaybill)
                        <div class="doc-muted">Harvested from VIN: {{ $item->donor_vin }}</div>
                        @endif
                    @endif
                </td>
                @if(!$isVehicleSale)<td class="doc-grade">{{ $item->condition_grade ?? '' }}</td>@endif
                <td class="c">{{ $item->qty }}</td>
                @if(!$isWaybill)
                <td class="r">{{ $item->unit_price_fmt }}</td>
                <td class="r">
                    @if($lineDisc > 0)
                        <span class="doc-disc">-{{ $money($lineDisc) }}</span>
                        @if(($item->discount_type ?? null) === 'percent')
                        <div class="doc-muted">({{ rtrim(rtrim(number_format((float) ($item->discount_value ?? 0), 2), '0'), '.') }}%)</div>
                        @endif
                    @else
                        <span class="doc-muted">-</span>
                    @endif
                </td>
                <td class="r">{{ $item->total_fmt }}</td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── Waybill notice (no prices on this copy) ────────────── --}}
    @if($isWaybill)
    <div class="doc-waybill">
        <strong>WAYBILL / PACKING LIST - NOT A PRICED DOCUMENT.</strong>
        This document lists the full description and quantity of goods being moved from
        <strong>{{ $saleLocation ?? $docLocation }}</strong> to their destination. It serves as evidence of
        lawful possession of these goods in transit and should be presented if requested by
        police or other authorities during movement between locations. No prices are shown
        on this copy - see Customer or Accounts Copy for pricing.
    </div>
    @endif

    {{-- ── Totals, shipping, tax, paid and due ────────────────── --}}
    @if(!$isWaybill)
    @php
        $pendingTotal = $docPay['summary']
            ? $docPay['summary']['payments']->where('status', 'pending')->sum('amount_local')
            : 0;
        $confirmedList = $docPay['summary']
            ? $docPay['summary']['payments']->where('status', 'confirmed')
            : collect();
    @endphp
    <table style="margin-top:10px;">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%;">
                <table class="doc-totals">
                    <tr><td>Subtotal:</td><td class="r">{{ $subtotalFmt }}</td></tr>
                    @if(($discountLocal ?? 0) > 0)
                    <tr><td>{{ $discountLabel }}</td><td class="r">-{{ $discountFmt }}</td></tr>
                    @endif
                    @if(($returnCreditApplied ?? 0) > 0)
                    <tr><td>Return Credit Applied:</td><td class="r">-{{ $returnCreditFmt }}</td></tr>
                    @endif
                    <tr><td>Shipping:</td><td class="r">{{ $shippingFmt ?? $money(0) }}</td></tr>
                    @if(!empty($taxLabel))
                    <tr><td>{{ $taxLabel }}</td><td class="r">{{ $taxFmt }}</td></tr>
                    @endif
                    <tr class="grand"><td>TOTAL:</td><td class="r">{{ $totalFmt ?? $subtotalFmt }}</td></tr>

                    @if($confirmedList->count())
                        @foreach($confirmedList as $p)
                        <tr class="sub"><td>Payment ({{ $p->payment_method }}, {{ \Carbon\Carbon::parse($p->created_at)->format('d M Y') }})</td><td class="r">{{ $money($p->amount_local) }}</td></tr>
                        @endforeach
                    @elseif(!empty($docPay['legacy']) || !empty($docPay['unknown']))
                    <tr class="sub"><td>Payment applied (paid at point of sale)</td><td class="r">{{ $totalFmt ?? $subtotalFmt }}</td></tr>
                    @endif
                    @if($pendingTotal > 0)
                    <tr class="sub"><td>Awaiting confirmation (not yet counted)</td><td class="r">{{ $money($pendingTotal) }}</td></tr>
                    @endif
                    <tr class="paid"><td>AMOUNT PAID:</td><td class="r">{{ !empty($docPay['unknown']) ? ($totalFmt ?? $subtotalFmt) : $money($docPay['confirmed']) }}</td></tr>
                    <tr class="due" style="color: {{ $docPay['balance'] > 0 ? '#c0392b' : '#1b9e5c' }};"><td>AMOUNT DUE:</td><td class="r">{{ $money($docPay['balance']) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>
    @endif

    {{-- ── Warranty / notes (not on the waybill: it is a plain packing list) ── --}}
    @if(!$isWaybill)
    @if($isVehicleSale)
    <div class="doc-warranty asis">
        <strong>SOLD AS-IS - NO WARRANTY:</strong> This vehicle is sold as-is, with no warranty implied or expressed, either written or verbal, covering mechanical, electrical, or any other condition. Buyer accepts full responsibility for the vehicle's condition from the point of sale. This document does not constitute a warranty of any kind.
        @if(!empty($docNotes))<br><strong>Notes:</strong> {{ $docNotes }}@endif
    </div>
    @else
    <div class="doc-warranty">
        {{ $docWarranty }}
        @if(!empty($docNotes))<br><strong>Notes:</strong> {{ $docNotes }}@endif
    </div>
    @endif
    @endif

    {{-- ── Signatures ─────────────────────────────────────────── --}}
    <table class="doc-sig">
        <tr>
            <td><div class="line">Issued By (Staff)</div></td>
            <td><div class="line">
                @if($copyKey === 'gate') Security Officer
                @elseif($copyKey === 'accounts') Accounts Officer
                @elseif($copyKey === 'warehouse') Warehouse Officer
                @else {{ $isVehicleSale ? 'Buyer Signature' : 'Customer Signature' }}
                @endif
            </div></td>
            <td><div class="line">{{ $copyKey === 'gate' ? 'Gate Stamp / Time Out' : 'Authorised Stamp' }}</div></td>
        </tr>
    </table>

    {{-- ── Footer ─────────────────────────────────────────────── --}}
    <div class="doc-foot">
        @if(!empty($footerAddresses))
        <table class="doc-foot-addr">
            @foreach(array_chunk($footerAddresses, 2) as $pair)
            <tr>
                @foreach($pair as $addr)
                @php $ap = explode(' — ', $addr, 2); @endphp
                <td style="width:50%;"><strong>{{ $ap[0] }}</strong><br>{{ $ap[1] ?? '' }}</td>
                @endforeach
                @if(count($pair) < 2)<td style="width:50%;"></td>@endif
            </tr>
            @endforeach
        </table>
        @endif
        <div style="margin-top:6px;">Thank you for your business! · autozenithparts.com · {{ $docEmail }} · WhatsApp: {{ $businessInfo['phone'] ?? '' }}</div>
        <div>This is a computer-generated {{ $isWaybill ? 'packing list' : ($docIsReceipt ? 'receipt' : 'invoice') }}. No physical signature required unless specified.@if($copyKey === 'gate') · GATE PASS - present to security on exit.@endif</div>
    </div>

</div>
