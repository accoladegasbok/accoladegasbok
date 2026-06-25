{{-- FILE: resources/views/admin/orders/print.blade.php --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Print Receipt — {{ $order->order_ref }}</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: Arial, sans-serif; color: #222; margin: 0; padding: 16px; background: #f4f4f4; }
    .toolbar { text-align: center; margin-bottom: 16px; }
    .print-btn { background: #C8960C; color: #0A1F5C; border: none; padding: 10px 28px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; }

    .copy { background: #fff; border: 2px solid #ccc; border-radius: 10px; padding: 18px 22px; margin: 0 auto 18px; max-width: 720px; page-break-after: always; }
    .copy:last-child { page-break-after: auto; }

    .copy-label { display: inline-block; padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: bold; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 10px; }

    .copy.customer { border-color: #0A1F5C; } .copy.customer .copy-label { background: #0A1F5C; color: #fff; }
    .copy.office    { border-color: #C8960C; } .copy.office .copy-label    { background: #C8960C; color: #0A1F5C; }
    .copy.accounts  { border-color: #27500A; } .copy.accounts .copy-label  { background: #EAF3DE; color: #27500A; }
    .copy.store     { border-color: #0C447C; } .copy.store .copy-label     { background: #E6F1FB; color: #0C447C; }

    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0A1F5C; padding-bottom: 10px; margin-bottom: 10px; }
    .header h1 { font-size: 18px; color: #0A1F5C; margin: 0; letter-spacing: 0.5px; }
    .header .ref { font-size: 12px; color: #888; margin-top: 2px; }
    .meta { text-align: right; font-size: 12px; color: #555; }

    .row { display: flex; justify-content: space-between; padding: 3px 0; font-size: 12px; color: #555; }
    .row strong { color: #222; }

    table { width: 100%; border-collapse: collapse; margin: 12px 0; }
    th { text-align: left; padding: 6px 4px; font-size: 10px; color: #888; border-bottom: 1.5px solid #0A1F5C; text-transform: uppercase; }
    td { padding: 7px 4px; font-size: 12px; border-bottom: 1px solid #eee; }

    .total-row { text-align: right; font-size: 16px; font-weight: bold; color: #0A1F5C; padding-top: 8px; }
    .signature-row { display: flex; justify-content: space-between; margin-top: 24px; font-size: 11px; color: #888; }
    .signature-row div { border-top: 1px solid #ccc; padding-top: 4px; width: 45%; text-align: center; }

    @media print {
      body { background: #fff; padding: 0; }
      .toolbar { display: none; }
      .copy { border-width: 1.5px; box-shadow: none; margin-bottom: 0; }
    }
  </style>
</head>
<body>

  <div class="toolbar">
    <button class="print-btn" onclick="window.print()">🖨 Print All 4 Copies</button>
  </div>

  @php
    $copies = [
        ['label' => 'Customer Copy',  'class' => 'customer'],
        ['label' => 'Office Copy',    'class' => 'office'],
        ['label' => 'Accounts Copy',  'class' => 'accounts'],
        ['label' => 'Store Room Copy','class' => 'store'],
    ];
  @endphp

  @foreach($copies as $copy)
  <div class="copy {{ $copy['class'] }}">
    <span class="copy-label">{{ $copy['label'] }}</span>

    <div class="header">
      <div>
        <h1>AUTO ZENITH PARTS</h1>
        <div class="ref">Order Receipt — {{ $order->order_ref }}</div>
      </div>
      <div class="meta">
        {{ \Carbon\Carbon::parse($order->created_at)->format('d M Y, H:i') }}<br>
        {{ $order->customer_country ?? '' }}
      </div>
    </div>

    <div class="row"><span>Customer</span><strong>{{ $order->customer_name }}</strong></div>
    <div class="row"><span>Phone</span><strong>{{ $order->customer_phone }}</strong></div>
    @if($order->customer_email)<div class="row"><span>Email</span><strong>{{ $order->customer_email }}</strong></div>@endif
    <div class="row"><span>Payment Method</span><strong>{{ $order->payment_method === 'bank_transfer' ? 'Bank Transfer' : 'POS In-Store' }}</strong></div>
    <div class="row"><span>Payment Status</span><strong>{{ str_replace('_',' ', ucfirst($order->payment_status)) }}</strong></div>
    @if($order->transfer_reference)
    <div class="row"><span>Transfer Ref</span><strong>{{ $order->transfer_reference }}</strong></div>
    @endif

    <table>
      <thead>
        <tr><th>Part</th><th>Vehicle</th><th style="text-align:right;">Price</th></tr>
      </thead>
      <tbody>
        @foreach($items as $item)
        <tr>
          <td>{{ $item->part_name }}<br><span style="font-size:10px;color:#999;">{{ $item->part_code }}</span></td>
          <td style="font-size:11px;color:#666;">{{ $item->brand }} {{ $item->model }} {{ $item->year_from }}@if($item->year_to != $item->year_from)–{{ $item->year_to }}@endif</td>
          <td style="text-align:right;">₦{{ number_format($item->unit_price_ngn) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="total-row">Total: ₦{{ number_format($order->total_amount_ngn) }}</div>
    <div style="text-align:right; font-size:11px; color:#888;">(${{ number_format($order->total_amount_usd, 2) }} USD equivalent)</div>

    <div class="signature-row">
      <div>Staff Signature</div>
      <div>Customer Signature</div>
    </div>
  </div>
  @endforeach

</body>
</html>
