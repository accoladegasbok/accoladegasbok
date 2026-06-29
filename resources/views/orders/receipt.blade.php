{{-- FILE: resources/views/orders/receipt.blade.php --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Receipt — {{ $order->order_ref }} — Auto Zenith Parts</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; color: #222; margin: 0; padding: 0; background: #f4f4f4; }

    .toolbar { text-align: center; padding: 16px; background: #fff; border-bottom: 1px solid #e2e8f0; }
    .print-btn { background: #C8960C; color: #0A1F5C; border: none; padding: 10px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; }

    .copy-page {
      max-width: 700px;
      margin: 20px auto;
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    }
    .copy-banner {
      padding: 6px 24px;
      text-align: center;
      font-size: 11px;
      font-weight: bold;
      letter-spacing: 1.5px;
      text-transform: uppercase;
      color: #fff;
    }
    .header { padding: 20px 24px; text-align: center; border-bottom: 3px solid; }
    .header h1 { margin: 0; font-size: 22px; letter-spacing: 1px; color: #0A1F5C; }
    .header p { margin: 4px 0 0; font-size: 12px; color: #888; }

    .body-content { padding: 20px 24px; }
    .row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13px; color: #555; }
    .row strong { color: #0A1F5C; }
    table { width: 100%; border-collapse: collapse; margin: 16px 0; }
    th { text-align: left; padding: 7px 4px; font-size: 10px; color: #888; border-bottom: 2px solid #0A1F5C; text-transform: uppercase; }
    td { padding: 8px 4px; font-size: 13px; border-bottom: 1px solid #eee; }
    .total { text-align: right; font-size: 18px; font-weight: bold; color: #0A1F5C; padding-top: 10px; }
    .footer-note { text-align: center; font-size: 10px; color: #999; margin-top: 16px; padding-top: 12px; border-top: 1px solid #eee; }

    /* ── Copy color themes ── */
    .copy-customer  .copy-banner { background: #0A1F5C; } .copy-customer  .header { border-bottom-color: #0A1F5C; }
    .copy-office    .copy-banner { background: #27500A; } .copy-office    .header { border-bottom-color: #27500A; }
    .copy-accounts  .copy-banner { background: #0C447C; } .copy-accounts  .header { border-bottom-color: #0C447C; }
    .copy-warehouse .copy-banner { background: #633806; } .copy-warehouse .header { border-bottom-color: #633806; }

    @media print {
      .toolbar { display: none; }
      body { background: #fff; }
      .copy-page { box-shadow: none; margin: 0; border-radius: 0; page-break-after: always; }
      .copy-page:last-child { page-break-after: auto; }
    }
  </style>
</head>
<body>

  <div class="toolbar">
    <button class="print-btn" onclick="window.print()">🖨 Print All 4 Copies</button>
  </div>

  @php
    $copies = [
      ['key' => 'customer',  'label' => 'Customer Copy'],
      ['key' => 'office',    'label' => 'Office Copy'],
      ['key' => 'accounts',  'label' => 'Accounts Copy'],
      ['key' => 'warehouse', 'label' => 'Warehouse / Driver Copy'],
    ];
  @endphp

  @foreach($copies as $copy)
  <div class="copy-page copy-{{ $copy['key'] }}">
    <div class="copy-banner">{{ $copy['label'] }}</div>

    <div class="header">
      <h1>AUTO ZENITH PARTS</h1>
      <p>Receipt — {{ $order->order_ref }}</p>
      <img src="https://api.qrserver.com/v1/create-qr-code/?size=70x70&data={{ urlencode(route('orders.receipt.public', $order->order_ref)) }}" alt="QR" style="margin-top:8px;">
    </div>

    <div class="body-content">
      <div class="row"><span>Customer</span><strong>{{ $order->customer_name }}</strong></div>
      <div class="row"><span>Phone</span><strong>{{ $order->customer_phone }}</strong></div>
      <div class="row"><span>Date</span><strong>{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y, H:i') }}</strong></div>
      <div class="row"><span>Payment Method</span><strong>{{ $order->payment_method === 'bank_transfer' ? 'Bank Transfer' : 'POS In-Store' }}</strong></div>
      <div class="row"><span>Payment Status</span><strong>{{ str_replace('_',' ', ucfirst($order->payment_status)) }}</strong></div>

      <table>
        <thead>
          <tr><th>Part</th><th>Vehicle</th><th style="text-align:right;">Price</th></tr>
        </thead>
        <tbody>
          @php $sym = ['NGN'=>'₦','GHS'=>'GH₵','USD'=>'$'][$order->currency_code ?? 'USD'] ?? '$'; @endphp
          @foreach($items as $item)
          <tr>
            <td>{{ $item->part_name }}<br><span style="font-size:10px;color:#999;">{{ $item->part_code }}</span></td>
            <td style="font-size:11px;color:#666;">{{ $item->brand }} {{ $item->model }} {{ $item->year_from }}@if($item->year_to != $item->year_from)–{{ $item->year_to }}@endif</td>
            <td style="text-align:right;">{{ $sym }}{{ ($order->currency_code ?? 'USD') === 'NGN' ? number_format($item->unit_price_local ?? $item->unit_price_ngn) : number_format($item->unit_price_local ?? $item->unit_price_usd, 2) }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>

      <div class="total">Total: {{ $sym }}{{ ($order->currency_code ?? 'USD') === 'NGN' ? number_format($order->total_amount_local ?? $order->total_amount_ngn) : number_format($order->total_amount_local ?? $order->total_amount_usd, 2) }}</div>

      {{-- ── Payment breakdown — shown on every receipt for consistency ── --}}
      @php $printPaySummary3 = \App\Http\Controllers\Admin\OrderAdminController::paymentSummary($order->id); @endphp
      <table style="margin-top: -8px;">
        @if($printPaySummary3['payments']->where('status', 'confirmed')->count())
        @foreach($printPaySummary3['payments']->where('status', 'confirmed') as $p)
        <tr style="font-size: 11px; color: #777;">
          <td style="border-bottom: none;">Less: Payment ({{ $p->payment_method }}, {{ \Carbon\Carbon::parse($p->created_at)->format('d M Y') }})</td>
          <td style="border-bottom: none; text-align: right;">– {{ $sym }}{{ ($order->currency_code ?? 'USD') === 'NGN' ? number_format($p->amount_local ?? $p->amount_ngn) : number_format($p->amount_local ?? $p->amount_ngn, 2) }}</td>
        </tr>
        @endforeach
        @else
        <tr style="font-size: 11px; color: #777;">
          <td style="border-bottom: none;">Payment Applied (Paid at point of sale)</td>
          <td style="border-bottom: none; text-align: right;">– {{ $sym }}{{ ($order->currency_code ?? 'USD') === 'NGN' ? number_format($order->total_amount_local ?? $order->total_amount_ngn) : number_format($order->total_amount_local ?? $order->total_amount_usd, 2) }}</td>
        </tr>
        @endif
      </table>
      <div class="total" style="{{ $printPaySummary3['balanceDue'] > 0 ? 'color:#c0392b;' : 'color:#27500A;' }}">
        {{ $printPaySummary3['balanceDue'] > 0 ? 'Balance Due: ' . $sym . (($order->currency_code ?? 'USD') === 'NGN' ? number_format($printPaySummary3['balanceDue']) : number_format($printPaySummary3['balanceDue'], 2)) : 'Balance: ' . $sym . '0' }}
      </div>

      <div class="footer-note">
        Thank you for shopping with Auto Zenith Parts.<br>
        Questions about this order? Reply to the original WhatsApp message or contact us directly.
      </div>
    </div>
  </div>
  @endforeach

</body>
</html>
