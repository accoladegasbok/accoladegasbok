{{-- FILE: resources/views/admin/invoices/invoice-pdf.blade.php --}}
{{-- 
    DomPDF-COMPATIBLE TEMPLATE — deliberately separate from the rich
    show.blade.php used for browser printing. DomPDF's rendering engine
    doesn't support flexbox, CSS grid, or many modern CSS3 features
    that show.blade.php relies on — this uses table-based layout and
    basic CSS only, which DomPDF handles reliably. Only renders the
    Customer Copy content (no multi-copy switcher, no print-controls
    toolbar — none of that makes sense in a downloaded PDF file).
--}}
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: 'Helvetica', Arial, sans-serif; font-size: 12px; color: #1a1a2e; }
    table { width: 100%; border-collapse: collapse; }
    .header-table td { vertical-align: top; }
    .brand-name { font-size: 26px; font-weight: bold; }
    .brand-name .zenith { color: #c9a84c; }
    .tagline { font-size: 10px; color: #666; margin-top: 2px; }
    .company-line { font-size: 11px; font-weight: bold; margin-top: 6px; }
    .company-sub { font-size: 10px; color: #444; }
    .inv-title { font-size: 22px; font-weight: bold; text-align: right; }
    .meta-table td { font-size: 11px; padding: 1px 0; }
    .meta-label { color: #888; text-align: right; padding-right: 8px; }
    .meta-value { font-weight: bold; text-align: right; }

    .parties-table { margin-top: 16px; }
    .party-box { width: 48%; border: 1px solid #ddd; padding: 8px; }
    .party-label { font-size: 9px; text-transform: uppercase; color: #999; letter-spacing: 1px; margin-bottom: 4px; }
    .party-name { font-weight: bold; font-size: 12px; }
    .party-detail { font-size: 10px; color: #444; }

    .items-table { margin-top: 16px; }
    .items-table th { background: #0d1b2a; color: white; font-size: 10px; text-transform: uppercase; padding: 6px; text-align: left; }
    .items-table td { padding: 6px; border-bottom: 1px solid #eee; font-size: 11px; }
    .items-table .num { text-align: right; }
    .part-name { font-weight: bold; }
    .part-sub { font-size: 9px; color: #777; }

    .totals-table { margin-top: 12px; width: 240px; float: right; }
    .totals-table td { padding: 4px 6px; font-size: 11px; }
    .totals-table .num { text-align: right; }
    .total-row td { background: #0d1b2a; color: white; font-weight: bold; font-size: 13px; }

    .footer { margin-top: 40px; padding-top: 10px; border-top: 1px solid #eee; text-align: center; font-size: 9px; color: #999; }
    .footer-addr { font-size: 9px; color: #333; margin-bottom: 6px; }
    .warranty-box { margin-top: 20px; background: #fffbe6; border: 1px solid #e6d68a; padding: 8px; font-size: 9px; color: #8a6d1f; }
</style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td style="width:60%;">
                <div class="brand-name">AUTO <span class="zenith">ZENITH</span> PARTS</div>
                <div class="tagline">QUALITY USED AUTO PARTS · ENGINE · GEARBOX · BODY</div>
                <div class="company-line">{{ $businessInfo['company'] ?? 'Auto Zenith Parts' }}</div>
                @if(!empty($businessInfo['rc']))<div class="company-sub">{{ $businessInfo['rc'] }}</div>@endif
                <div class="company-sub">{{ $businessInfo['address'] ?? '' }}</div>
                <div class="company-sub">{{ $businessInfo['phone'] ?? '' }}</div>
            </td>
            <td style="width:40%;">
                <div class="inv-title">{{ $isVehicleSale ?? false ? 'RECEIPT' : 'INVOICE' }}</div>
                <table class="meta-table">
                    <tr><td class="meta-label">Invoice No:</td><td class="meta-value">{{ $invoiceNo }}</td></tr>
                    <tr><td class="meta-label">Date:</td><td class="meta-value">{{ \Carbon\Carbon::parse($createdAt)->format('d M Y') }}</td></tr>
                    <tr><td class="meta-label">Location:</td><td class="meta-value">{{ $saleLocation }}</td></tr>
                    <tr><td class="meta-label">Currency:</td><td class="meta-value">{{ $currency['code'] }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="parties-table">
        <tr>
            <td class="party-box" style="width:48%;">
                <div class="party-label">Bill To</div>
                <div class="party-name">{{ $customerInfo->name ?? 'Walk-in Customer' }}</div>
                @if(!empty($customerInfo->phone))<div class="party-detail">{{ $customerInfo->phone }}</div>@endif
                @if(!empty($customerInfo->email))<div class="party-detail">{{ $customerInfo->email }}</div>@endif
                @if(!empty($customerInfo->address))<div class="party-detail">{{ $customerInfo->address }}</div>@endif
            </td>
            <td style="width:4%;"></td>
            <td class="party-box" style="width:48%;">
                <div class="party-label">Payment</div>
                <div class="party-name">{{ $paymentMethod }}</div>
                @if(!empty($businessInfo['bank']))
                <div class="party-detail">{{ $businessInfo['bank'] }}: {{ $businessInfo['account'] }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th style="width:45%;">Description</th>
                <th style="width:15%;">Code</th>
                <th style="width:8%;" class="num">Qty</th>
                <th style="width:13%;" class="num">Unit Price</th>
                <th style="width:14%;" class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lineItems as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    <div class="part-name">{{ $item->part_name }}</div>
                    @if(!empty($item->brand))
                    <div class="part-sub">{{ strtoupper($item->brand) }} {{ strtoupper($item->model ?? '') }}</div>
                    @endif
                </td>
                <td>{{ $item->part_code }}</td>
                <td class="num">{{ $item->qty }}</td>
                <td class="num">{{ $item->unit_price_fmt }}</td>
                <td class="num">{{ $item->total_fmt }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr><td>Subtotal:</td><td class="num">{{ $subtotalFmt }}</td></tr>
        @if(($discountLocal ?? 0) > 0)
        <tr><td>{{ $discountLabel }}</td><td class="num">-{{ $discountFmt }}</td></tr>
        @endif
        @if(($returnCreditApplied ?? 0) > 0)
        <tr><td>Return Credit Applied:</td><td class="num">-{{ $returnCreditFmt }}</td></tr>
        @endif
        <tr class="total-row"><td>TOTAL:</td><td class="num">{{ $totalFmt ?? $subtotalFmt }}</td></tr>
    </table>

    <div style="clear:both;"></div>

    <div class="warranty-box">
        Warranty: 10 days. Warranty is void if part is disassembled, modified, or damaged after installation. Proof of purchase (this invoice) required for all warranty claims.
    </div>

    <div class="footer">
        @if(!empty($footerAddresses))
        @foreach($footerAddresses as $addr)
        <div class="footer-addr">{{ $addr }}</div>
        @endforeach
        @endif
        <div style="margin-top:8px;">Thank you for your business! · autozenithparts.com</div>
        <div>This is a computer-generated {{ $isVehicleSale ?? false ? 'receipt' : 'invoice' }}. No physical signature required unless specified.</div>
    </div>

</body>
</html>
