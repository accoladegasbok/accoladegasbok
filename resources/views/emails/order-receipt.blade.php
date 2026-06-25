{{-- FILE: resources/views/emails/order-receipt.blade.php --}}
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">

  <div style="background: #0A1F5C; color: #fff; padding: 20px; border-radius: 10px 10px 0 0; text-align: center;">
    <h1 style="margin: 0; font-size: 22px; letter-spacing: 1px;">AUTO ZENITH PARTS</h1>
    <p style="margin: 4px 0 0; color: #C8960C; font-size: 13px;">Order Receipt</p>
  </div>

  <div style="background: #f9f9f9; padding: 20px; border-radius: 0 0 10px 10px;">
    <p>Hi {{ $order->customer_name }},</p>
    <p>Thank you for your order! Here's a summary of your receipt for <strong>{{ $order->order_ref }}</strong>.</p>

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
          <td style="padding: 8px 4px; text-align: right; font-size: 14px;">₦{{ number_format($item->unit_price_ngn) }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div style="text-align: right; font-size: 16px; font-weight: bold; color: #0A1F5C; padding-top: 8px;">
      Total: ₦{{ number_format($order->total_amount_ngn) }}
    </div>

    <div style="text-align: center; margin: 24px 0;">
      <a href="{{ $receiptUrl }}" style="background: #C8960C; color: #0A1F5C; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block;">
        View Full Receipt
      </a>
    </div>

    <p style="font-size: 12px; color: #999; text-align: center; margin-top: 24px;">
      Auto Zenith Parts — thank you for your business.
    </p>
  </div>

</body>
</html>
