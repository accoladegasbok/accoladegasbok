{{-- FILE: resources/views/emails/payment-reminder.blade.php --}}
<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; color: #222; max-width: 600px; margin: 0 auto;">
  <div style="background: #0A1F5C; color: #fff; padding: 20px; border-radius: 8px 8px 0 0;">
    <h2 style="margin:0;">Payment Reminder</h2>
  </div>
  <div style="border: 1px solid #e2e8f0; border-top: none; padding: 20px; border-radius: 0 0 8px 8px;">
    <p>Hi {{ $order->customer_name }},</p>
    <p>This is a friendly reminder that your order <strong>{{ $order->order_ref }}</strong> has an outstanding balance.</p>
    <div style="background: #f9f9f9; border-radius: 8px; padding: 16px; margin: 16px 0; text-align: center;">
      <div style="font-size: 13px; color: #888;">Amount Outstanding</div>
      <div style="font-size: 28px; font-weight: bold; color: #0A1F5C;">₦{{ number_format($balanceDue) }}</div>
    </div>
    <p>Please reach out to us or complete payment at your earliest convenience. Thank you for your business!</p>
    <p style="margin-top: 24px; color: #888; font-size: 12px;">Auto Zenith Parts</p>
  </div>
</body>
</html>
