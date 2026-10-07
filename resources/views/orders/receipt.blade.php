{{-- FILE: resources/views/orders/receipt.blade.php
     The customer's public receipt page (the link sent by email and WhatsApp).
     Renders the shared document (admin/invoices/_document.blade.php) in public mode. --}}
@php
    $docPay = \App\Http\Controllers\Admin\InvoiceController::documentPaymentState($order, null, null);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $docPay['status'] === 'PAID' ? 'Receipt' : 'Invoice' }} {{ $order->order_ref }} — Auto Zenith Parts</title>
  <link rel="icon" type="image/png" sizes="64x64" href="{{ asset('images/az-favicon-64.png') }}">
  <style>
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; margin: 0; padding: 16px; background: #f4f4f4; color: #1a1a2e; }
    .toolbar { text-align: center; margin-bottom: 16px; }
    .print-btn { background: #C8960C; color: #0A1F5C; border: none; padding: 10px 26px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; }
    .page { background: #fff; max-width: 780px; margin: 0 auto; padding: 22px 26px; border-radius: 10px; border: 1px solid #ddd; }
@include('admin.invoices._document-styles')
    @page { size: A4; margin: 12mm; }
    @media print {
      body { background: #fff; padding: 0; }
      .toolbar { display: none; }
      .page { border: none; border-radius: 0; padding: 0; max-width: none; }
    }
  </style>
</head>
<body>

  <div class="toolbar">
    <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
  </div>

  <div class="page">
    @include('admin.invoices._document', [
        'copyKey'          => 'customer',
        'docMode'          => 'screen',
        'docPublic'        => true,
        'docQrUrlOverride' => route('orders.receipt.public', $order->order_ref),
    ])
  </div>

</body>
</html>
