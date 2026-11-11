{{-- FILE: resources/views/admin/training/certificate.blade.php --}}
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Certificate - {{ $course->title }}</title>
<style>
  body{margin:0;background:#eef0f5;font-family:Georgia,serif;color:#0A1F5C}
  .bar{max-width:900px;margin:16px auto;display:flex;gap:10px;font-family:Arial,sans-serif}
  .bar a,.bar button{background:#0A1F5C;color:#fff;border:0;padding:9px 16px;border-radius:8px;text-decoration:none;font-size:14px;cursor:pointer}
  .cert{position:relative;max-width:900px;margin:0 auto 30px;background:#fff;border:10px double #C8960C;padding:44px 50px;text-align:center;overflow:hidden}
  .wm{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:92px;font-weight:700;color:rgba(10,31,92,.05);transform:rotate(-24deg);pointer-events:none;letter-spacing:6px;font-family:Arial,sans-serif}
  .cert img{height:78px;position:relative}
  h1{font-size:40px;margin:14px 0 4px;letter-spacing:3px;position:relative}
  .name{font-size:34px;margin:18px 0;border-bottom:2px solid #C8960C;display:inline-block;padding:0 30px 6px;position:relative}
  p{position:relative;font-size:17px;line-height:1.6;margin:8px 0}
  .meta{font-family:Arial,sans-serif;font-size:13px;color:#555}
  @media print{body{background:#fff}.bar{display:none}.cert{margin:0;border-width:10px}}
</style></head><body>
<div class="bar"><button onclick="window.print()">Print / Save as PDF</button><a href="{{ route('admin.training.mine') }}">My Training</a></div>
<div class="cert">
  <div class="wm">AUTO ZENITH PARTS</div>
  <img src="{{ asset('images/az-logo-doc.jpg') }}" alt="Auto Zenith Parts">
  <h1>CERTIFICATE OF COMPLETION</h1>
  <p>This certifies that</p>
  <div class="name">{{ $staff->name }}</div>
  <p>has successfully completed the training course</p>
  <p style="font-size:24px;font-weight:700">{{ $course->title }}</p>
  <p class="meta">{{ ucfirst($course->provider_type) }} training{{ $course->provider_name ? ' · ' . $course->provider_name : '' }}{{ $a->score !== null ? ' · Score ' . $a->score . '%' : '' }}@if($course->policy_text) · Company policy accepted @endif</p>
  <p class="meta">Completed {{ \Carbon\Carbon::parse($a->completed_at)->format('d F Y') }}@if($course->recurrence_days) · Retraining due {{ \Carbon\Carbon::parse($a->completed_at)->addDays($course->recurrence_days)->format('d F Y') }}@endif</p>
  <p class="meta">Staff ID {{ $staff->id }} · Certificate #{{ str_pad($a->id, 6, '0', STR_PAD_LEFT) }}</p>
</div>
</body></html>
