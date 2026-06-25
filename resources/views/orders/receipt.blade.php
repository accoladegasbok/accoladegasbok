{{-- FILE: resources/views/orders/receipt.blade.php --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Receipt — {{ $order->order_ref }} — Auto Zenith Parts</title>
  <style>
    body { font-family: Arial, sans-serif; color: #222; max-width: 700px; margin: 30px auto; padding: 0 20px; }
    .header { background: #0A1F5C; color: #fff; padding: 24px; border-radius: 12px 12px 0 0; text-align: center; }
    .header h1 { margin: 0; font-size: 24px; letter-spacing: 1px; }
    .header p { margin: 6px 0 0; color: #C8960C; font-size: 13px; }
    .body { border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 12px 12px; padding: 24px; }
    .row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 13px; color: #555; }
    .row strong { color: #0A1F5C; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th { text-align: left; padding: 8px 4px; font-size: 11px; color: #888; border-bottom: 2px solid #0A1F5C; text-transform: uppercase; }
    td { padding: 10px 4px; font-size: 14px; border-bottom: 1px solid #eee; }
    .total { text-align: right; font-size: 20px; font-weight: bold; color: #0A1F5C; padding-top: 12px; }
    .print-btn { background: #C8960C; color: #0A1F5C; border: none; padding: 10px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; }
    .footer-note { text-align: center; font-size: 11px; color: #999; margin-top: 24px; }
    @media print { .no-print { display: none; } body { margin: 0; } }
  </style>
</head>
<body>

  <div class="no-print" style="text-align: right; margin-bottom: 12px;">
    <button class="print-btn" onclick="window.print()">🖨 Print Receipt</button>
  </div>

  <div class="header">
    <h1>AUTO ZENITH PARTS</h1>
    <p>Receipt — {{ $order->order_ref }}</p>
  </div>

  <div class="body">
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
        @foreach($items as $item)
        <tr>
          <td>{{ $item->part_name }}<br><span style="font-size:11px;color:#999;">{{ $item->part_code }}</span></td>
          <td style="font-size:12px;color:#666;">{{ $item->brand }} {{ $item->model }} {{ $item->year_from }}@if($item->year_to != $item->year_from)–{{ $item->year_to }}@endif</td>
          <td style="text-align:right;">₦{{ number_format($item->unit_price_ngn) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div class="total">Total: ₦{{ number_format($order->total_amount_ngn) }}</div>
    <div style="text-align:right; font-size:12px; color:#888;">(${{ number_format($order->total_amount_usd, 2) }} USD equivalent at time of order)</div>

    <div class="footer-note">
      Thank you for shopping with Auto Zenith Parts.<br>
      Questions about this order? Reply to the original WhatsApp message or contact us directly.
    </div>
  </div>

</body>
</html>
