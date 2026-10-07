{{-- FILE: resources/views/admin/orders/print.blade.php
     Print page for an order. The receipt itself is the shared document (admin/invoices/_document.blade.php),
     the same one used by the invoice/receipt page and the downloaded PDF. --}}
@php
    // Computed once here and reused by the document below.
    $docPay = \App\Http\Controllers\Admin\InvoiceController::documentPaymentState($order, null, null);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $docPay['status'] === 'PAID' ? 'Receipt' : 'Invoice' }} {{ $invoiceNo }} — Auto Zenith Parts</title>
  <link rel="icon" type="image/png" sizes="64x64" href="{{ asset('images/az-favicon-64.png') }}">
  <style>
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; margin: 0; padding: 16px; background: #f4f4f4; color: #1a1a2e; }
    .toolbar { text-align: center; margin-bottom: 16px; }
    .toolbar a, .toolbar button { display: inline-block; margin: 0 4px; padding: 10px 22px; border-radius: 8px; font-weight: bold; font-size: 14px; cursor: pointer; text-decoration: none; border: none; }
    .print-btn { background: #C8960C; color: #0A1F5C; }
    .more-btn  { background: #fff; color: #0A1F5C; border: 1.5px solid #0A1F5C !important; }
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
    <button class="print-btn" onclick="window.print()">Print Receipt</button>
    <a class="more-btn" href="{{ route('admin.invoices.show', $order->id) }}">All copies (Customer, Gate Pass, Waybill...)</a>
    <a class="more-btn" href="{{ route('admin.orders.show', $order->id) }}">&larr; Back to order</a>
  </div>

  <div class="page">
    @include('admin.invoices._document', ['copyKey' => 'customer', 'docMode' => 'screen'])
  </div>

</body>
</html>
