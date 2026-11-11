{{-- FILE: resources/views/admin/eotm/certificate.blade.php --}}
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Employee of the Month - {{ $winner->name }}</title>
<style>
  body{margin:0;background:#eef0f5;font-family:Georgia,serif;color:#0A1F5C}
  .bar{max-width:900px;margin:16px auto;display:flex;gap:10px;font-family:Arial,sans-serif}
  .bar a,.bar button{background:#0A1F5C;color:#fff;border:0;padding:9px 16px;border-radius:8px;text-decoration:none;font-size:14px;cursor:pointer}
  .cert{position:relative;max-width:900px;margin:0 auto 30px;background:#fff;border:10px double #C8960C;padding:44px 50px;text-align:center;overflow:hidden}
  .wm{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:92px;font-weight:700;color:rgba(10,31,92,.05);transform:rotate(-24deg);pointer-events:none;letter-spacing:6px;font-family:Arial,sans-serif}
  .cert img{height:78px;position:relative}
  h1{font-size:38px;margin:14px 0 2px;letter-spacing:3px;position:relative}
  .sub{color:#C8960C;letter-spacing:4px;font-family:Arial,sans-serif;font-size:14px;position:relative}
  .name{font-size:38px;margin:20px 0;border-bottom:2px solid #C8960C;display:inline-block;padding:0 34px 6px;position:relative}
  p{position:relative;font-size:17px;line-height:1.6;margin:8px 0}
  .meta{font-family:Arial,sans-serif;font-size:13px;color:#555}
  @media print{body{background:#fff}.bar{display:none}.cert{margin:0}}
</style></head><body>
<div class="bar"><button onclick="window.print()">Print / Save as PDF</button><a href="{{ route('admin.eotm.index', ['ym' => $cycle->ym]) }}">Back</a></div>
<div class="cert">
  <div class="wm">AUTO ZENITH PARTS</div>
  <img src="{{ asset('images/az-logo-doc.jpg') }}" alt="Auto Zenith Parts">
  <div class="sub">GASBOK ENGINEERING · AUTO ZENITH PARTS</div>
  <h1>EMPLOYEE OF THE MONTH</h1>
  <p>{{ \Carbon\Carbon::parse($cycle->ym.'-01')->format('F Y') }}</p>
  <p>This certificate of recognition is proudly presented to</p>
  <div class="name">{{ $winner->name }}</div>
  <p>in appreciation of outstanding performance, excellent service to customers, teamwork, and dedication to the company.</p>
  @if($nom)<p class="meta">Selected by performance score and the vote of fellow staff members.</p>@endif
  <p class="meta">Staff ID {{ $winner->id }} · {{ $winner->location }}</p>
</div>
</body></html>
