{{-- FILE: resources/views/admin/transfers/waybill.blade.php --}}
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Waybill — {{ $transfer->transfer_no }}</title>
  <style>
    body { font-family: Arial, sans-serif; color: #222; max-width: 700px; margin: 30px auto; padding: 0 20px; }
    .header { background: #0A1F5C; color: #fff; padding: 24px; border-radius: 12px 12px 0 0; display: flex; justify-content: space-between; align-items: center; }
    .header h1 { margin: 0; font-size: 22px; letter-spacing: 1px; }
    .header p { margin: 4px 0 0; color: #C8960C; font-size: 12px; }
    .body { border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 12px 12px; padding: 24px; }
    .route { display: flex; align-items: center; justify-content: center; gap: 16px; background: #f4f4f4; border-radius: 10px; padding: 16px; margin-bottom: 20px; }
    .route .loc { text-align: center; }
    .route .loc-name { font-weight: bold; color: #0A1F5C; font-size: 15px; }
    .route .loc-label { font-size: 10px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }
    .route .arrow { font-size: 20px; color: #C8960C; }
    table { width: 100%; border-collapse: collapse; margin: 16px 0; }
    th { text-align: left; padding: 8px 4px; font-size: 11px; color: #888; border-bottom: 2px solid #0A1F5C; text-transform: uppercase; }
    td { padding: 10px 4px; font-size: 14px; border-bottom: 1px solid #eee; }
    .no-price-note { background: #FAEEDA; color: #633806; border: 1px solid #FAC775; border-radius: 8px; padding: 10px 14px; font-size: 12px; text-align: center; margin: 16px 0; font-weight: bold; }
    .signature-row { display: flex; justify-content: space-between; margin-top: 32px; font-size: 11px; color: #888; }
    .signature-row div { border-top: 1px solid #ccc; padding-top: 6px; width: 30%; text-align: center; }
    .print-btn { background: #C8960C; color: #0A1F5C; border: none; padding: 10px 24px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; }
    .qr-box { text-align: center; }
    @media print { .no-print { display: none; } body { margin: 0; } }
  </style>
</head>
<body>

  <div class="no-print" style="text-align: right; margin-bottom: 12px;">
    <button class="print-btn" onclick="window.print()">🖨 Print Waybill</button>
  </div>

  <div class="header">
    <div>
      <h1>AUTO ZENITH PARTS</h1>
      <p>{{ $fromBusinessInfo['company'] ?? 'Gasbok Engineering Nig Limited' }}</p>
      <p>Stock Transfer Waybill — {{ $transfer->transfer_no }}</p>
    </div>
    <div class="qr-box">
      <img src="https://api.qrserver.com/v1/create-qr-code/?size=90x90&data={{ urlencode(route('admin.transfers.show', $transfer->id)) }}" alt="QR code" style="background:#fff; border-radius:6px; padding:4px;">
    </div>
  </div>

  <div class="body">
    {{-- ── Full From/To letterhead — company name, phone, and the
         actual street address, not just a bare location name ── --}}
    <div style="display:flex; gap:16px; margin-bottom: 20px;">
      <div style="flex:1; border:1px solid #e2e8f0; border-radius:10px; padding:14px;">
        <div style="font-size:10px; color:#888; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Shipped From</div>
        <div style="font-weight:bold; color:#0A1F5C; font-size:14px;">{{ $transfer->from_location }}</div>
        <div style="font-size:12px; color:#555; margin-top:2px;">{{ $fromBusinessInfo['company'] ?? '' }}</div>
        <div style="font-size:12px; color:#555;">{{ $fromAddress ?? 'No address on file for this location' }}</div>
        @if(!empty($fromBusinessInfo['phone']))<div style="font-size:12px; color:#555;">📞 {{ $fromBusinessInfo['phone'] }}</div>@endif
      </div>
      <div style="flex:1; border:1px solid #e2e8f0; border-radius:10px; padding:14px;">
        <div style="font-size:10px; color:#888; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Shipped To</div>
        <div style="font-weight:bold; color:#0A1F5C; font-size:14px;">{{ $transfer->to_location }}</div>
        <div style="font-size:12px; color:#555; margin-top:2px;">{{ $toBusinessInfo['company'] ?? '' }}</div>
        <div style="font-size:12px; color:#555;">{{ $toAddress ?? 'No address on file for this location' }}</div>
        @if(!empty($toBusinessInfo['phone']))<div style="font-size:12px; color:#555;">📞 {{ $toBusinessInfo['phone'] }}</div>@endif
      </div>
    </div>

    <div class="route">
      <div class="loc">
        <div class="loc-label">From</div>
        <div class="loc-name">{{ $transfer->from_location }}</div>
      </div>
      <div class="arrow">→</div>
      <div class="loc">
        <div class="loc-label">To</div>
        <div class="loc-name">{{ $transfer->to_location }}</div>
      </div>
    </div>

    <div class="no-price-note">⚠ NO PRICING INFORMATION — This document confirms quantity and description only.</div>

    <table>
      <thead>
        <tr><th>Part</th><th>Code</th><th>Grade</th></tr>
      </thead>
      <tbody>
        @foreach($items as $item)
        <tr>
          <td>{{ $item->part_name }}<br><span style="font-size:11px;color:#999;">{{ $item->brand }} {{ $item->model }}</span></td>
          <td style="font-family:monospace;">{{ $item->part_code }}</td>
          <td>{{ $item->condition_grade }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>

    <div style="text-align:right; font-size:13px; color:#555; margin-top:8px;">
      Total items: <strong>{{ $items->count() }}</strong>
    </div>

    @if($transfer->notes)
    <div style="margin-top:16px; background:#f9f9f9; border-radius:8px; padding:10px 14px; font-size:12px; color:#666;">
      <strong>Notes:</strong> {{ $transfer->notes }}
    </div>
    @endif

    <div class="signature-row">
      <div>Dispatched By</div>
      <div>Carrier / Driver</div>
      <div>Received By</div>
    </div>
  </div>

</body>
</html>
