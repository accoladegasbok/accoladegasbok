{{-- FILE: resources/views/admin/inventory/barcode.blade.php --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Barcode — {{ $part->part_code }}</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/JsBarcode/3.11.5/JsBarcode.all.min.js"></script>
  <style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f4f4f4; }
    .toolbar { text-align: center; margin-bottom: 16px; }
    .print-btn { background: #C8960C; color: #0A1F5C; border: none; padding: 10px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; }
    .label {
      width: 280px; background: #fff; border: 1px solid #ccc; border-radius: 6px;
      padding: 12px; margin: 0 auto; text-align: center;
    }
    .label .part-name { font-size: 12px; font-weight: bold; color: #0A1F5C; margin-bottom: 2px; }
    .label .part-sub { font-size: 10px; color: #888; margin-bottom: 8px; }
    .label .price { font-size: 16px; font-weight: bold; color: #0A1F5C; margin-top: 6px; }
    @media print { .no-print { display: none; } body { margin: 0; padding: 0; background: #fff; } }
  </style>
</head>
<body>
  <div class="toolbar no-print">
    <button class="print-btn" onclick="window.print()">🖨 Print Label</button>
  </div>

  <div class="label">
    <div class="part-name">{{ $part->part_name }}</div>
    <div class="part-sub">{{ $part->brand }} {{ $part->model }} {{ $part->year_from }}@if($part->year_to != $part->year_from)–{{ $part->year_to }}@endif · Grade {{ $part->condition_grade }}</div>
    <svg id="barcode"></svg>
    <div class="price">
        @php
            $priceLocal = $part->price_local ?? $part->price_usd;
            $sym = match($part->currency_code ?? 'USD') { 'NGN' => '₦', 'GHS' => 'GH₵', default => '$' };
        @endphp
        {{ $sym }}{{ ($part->currency_code ?? 'USD') === 'NGN' ? number_format($priceLocal) : number_format($priceLocal, 2) }}
    </div>
  </div>

  <script>
    JsBarcode("#barcode", "{{ $part->part_code }}", {
        format: "CODE128",
        width: 2,
        height: 50,
        displayValue: true,
        fontSize: 14,
    });
  </script>
</body>
</html>
