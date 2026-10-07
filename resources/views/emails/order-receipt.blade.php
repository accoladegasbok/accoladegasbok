{{-- FILE: resources/views/emails/order-receipt.blade.php --}}
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

  <div style="background: #0A1F5C; color: #fff; padding: 20px; border-radius: 10px 10px 0 0; text-align: center;">
    <a href="https://autozenithparts.com" style="text-decoration:none; display:inline-block; background:#ffffff; border-radius:10px; padding:8px 14px;"><img src="https://autozenithparts.com/images/az-logo-doc.jpg" width="130" height="79" alt="Auto Zenith Parts" style="display:block; border:0; outline:none;"></a>
    <p style="margin: 10px 0 0; color: #C8960C; font-size: 13px;">Order Receipt</p>
  </div>

  <div style="background: #f9f9f9; padding: 20px; border-radius: 0 0 10px 10px;">
    <p>Hi {{ $order->customer_name }},</p>
    <p>Thank you for your order! Here's a summary of your receipt for <strong>{{ $order->order_ref }}</strong>.</p>

    @php
      // The order's own currency (NGN / GHS / USD). Falls back to NGN for older orders.
      $cur = $order->currency_code ?? 'NGN';
      $fmt = fn ($n) => \App\Http\Controllers\Admin\InvoiceController::formatLocal((float) $n, $cur);
    @endphp
    <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
      <thead>
        <tr style="border-bottom: 2px solid #0A1F5C;">
          <th style="text-align: left; padding: 8px 4px; font-size: 12px; color: #888;">PART</th>
          <th style="text-align: right; padding: 8px 4px; font-size: 12px; color: #888;">PRICE</th>
        </tr>
      </thead>
      <tbody>
        @foreach($items as $item)
        <tr style="border-bottom: 1px solid #eee;">
          <td style="padding: 8px 4px; font-size: 14px;">
            {{ $item->part_name }}<br>
            <span style="font-size: 11px; color: #999;">{{ $item->brand }} {{ $item->model }} · {{ $item->part_code }}</span>
          </td>
          <td style="padding: 8px 4px; text-align: right; font-size: 14px;">{{ $fmt($item->unit_price_local ?? $item->unit_price_ngn ?? 0) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div style="text-align: right; font-size: 16px; font-weight: bold; color: #0A1F5C; padding-top: 8px;">
      Total: {{ $fmt($order->total_amount_local ?? $order->total_amount_ngn ?? 0) }}
    </div>

    <div style="text-align: center; margin: 24px 0;">
      <a href="{{ $receiptUrl }}" style="background: #C8960C; color: #0A1F5C; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block;">
        View Full Receipt
      </a>
    </div>

    <p style="font-size: 12px; color: #999; text-align: center; margin-top: 24px;">
      Auto Zenith Parts — thank you for your business.<br>info@autozenithparts.com · autozenithparts.com
    </p>
  </div>

</body>
</html>
