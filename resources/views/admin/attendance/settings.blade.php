{{-- FILE: resources/views/admin/attendance/settings.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Attendance Settings')
@section('page-title', 'Attendance - Sites & Phones')
@section('page-sub', 'Set each branch location, shift time and who may clock in; approve staff phones')

@section('header-actions')
  <a href="{{ route('admin.attendance.report') }}" class="bg-white border border-gray-200 text-navy font-display font-700 text-xs px-3 py-2 rounded-xl hover:border-navy">Back to report</a>
@endsection

@section('content')
@if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ $errors->first() }}</div>@endif

<h2 class="font-display font-700 text-navy text-lg mb-2">Phones waiting and registered</h2>
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-x-auto mb-8">
  <table class="w-full text-sm font-body">
    <thead class="bg-gray-50 text-[11px] uppercase text-gray-500"><tr><th class="text-left px-4 py-2">Staff</th><th class="text-left">Phone</th><th class="text-left">Registered</th><th class="text-left">Last used</th><th></th></tr></thead>
    <tbody>
    @forelse($passkeys as $p)
      <tr class="border-t border-gray-100">
        <td class="px-4 py-2"><b class="text-navy">{{ $p->staff_name }}</b><div class="text-[11px] text-gray-400">{{ $p->staff_location }}</div></td>
        <td class="text-xs">{{ $p->label }}<div class="text-[10px] text-gray-400 truncate max-w-[260px]">{{ $p->user_agent }}</div></td>
        <td class="text-xs">{{ \Carbon\Carbon::parse($p->created_at)->format('d M Y H:i') }}</td>
        <td class="text-xs">{{ $p->last_used_at ? \Carbon\Carbon::parse($p->last_used_at)->format('d M H:i') : '—' }}</td>
        <td class="px-2 py-1 flex gap-2 justify-end">
          @unless($p->approved)<form method="POST" action="{{ route('admin.attendance.passkey.approve', $p->id) }}">@csrf<button class="text-xs bg-green-600 text-white px-3 py-1 rounded">Approve</button></form>@else<span class="text-xs text-green-700 font-700 self-center">Approved</span>@endunless
          <form method="POST" action="{{ route('admin.attendance.passkey.revoke', $p->id) }}" onsubmit="return confirm('Remove this phone?')">@csrf<button class="text-xs bg-red-100 text-red-700 px-3 py-1 rounded">Remove</button></form>
        </td>
      </tr>
    @empty
      <tr><td colspan="5" class="px-4 py-5 text-center text-gray-400">No phones registered yet.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@if($noPhone->count())
<p class="text-xs text-gray-500 font-body -mt-6 mb-8">Without an approved phone yet: {{ $noPhone->pluck('name')->implode(', ') }}.</p>
@endif

<h2 class="font-display font-700 text-navy text-lg mb-2">Branch sites</h2>
<div class="grid md:grid-cols-2 gap-4">
@foreach($locs as $l)
  @php $s = $sites[$l] ?? null; $days = explode(',', $s->working_days ?? '1,2,3,4,5,6'); @endphp
  <form method="POST" action="{{ route('admin.attendance.site.save') }}" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 text-sm font-body">
    @csrf <input type="hidden" name="location" value="{{ $l }}">
    <div class="font-display font-700 text-navy mb-3">{{ $l }} @if(!$s)<span class="text-xs text-amber-600 font-body">not set up</span>@endif</div>
    <div class="grid grid-cols-2 gap-3">
      <label>Latitude<input name="lat" value="{{ $s->lat ?? '' }}" class="lat w-full border border-gray-200 rounded px-2 py-1"></label>
      <label>Longitude<input name="lng" value="{{ $s->lng ?? '' }}" class="lng w-full border border-gray-200 rounded px-2 py-1"></label>
      <label>Allowed distance (m)<input type="number" name="radius_m" value="{{ $s->radius_m ?? 150 }}" class="w-full border border-gray-200 rounded px-2 py-1"></label>
      <label>Shift starts<input type="time" name="shift_start" value="{{ $s->shift_start ?? '08:30' }}" class="w-full border border-gray-200 rounded px-2 py-1"></label>
      <label>Grace (minutes)<input type="number" name="grace_minutes" value="{{ $s->grace_minutes ?? 15 }}" class="w-full border border-gray-200 rounded px-2 py-1"></label>
      <div class="flex items-end"><button type="button" class="useHere text-xs border border-navy text-navy px-2 py-1 rounded">Use my current location</button></div>
    </div>
    <div class="flex flex-wrap gap-3 mt-3 text-xs">
      @foreach([1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'] as $n => $d)
        <label><input type="checkbox" name="working_days[]" value="{{ $n }}" {{ in_array((string)$n, $days) ? 'checked' : '' }}> {{ $d }}</label>
      @endforeach
    </div>
    <div class="mt-3 space-y-1 text-xs">
      <label class="block"><input type="checkbox" name="enforce_geofence" value="1" {{ ($s->enforce_geofence ?? 1) ? 'checked' : '' }}> Staff must be at the branch to clock in</label>
      <label class="block"><input type="checkbox" name="require_biometric" value="1" {{ ($s->require_biometric ?? 0) ? 'checked' : '' }}> Fingerprint / face unlock is compulsory (turn on after everyone's phone is approved)</label>
    </div>
    <button class="mt-3 bg-navy text-white font-display font-700 text-sm px-4 py-2 rounded-lg">Save {{ $l }}</button>
  </form>
@endforeach
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.useHere').forEach(b => b.addEventListener('click', () => {
  const f = b.closest('form');
  navigator.geolocation.getCurrentPosition(p => { f.querySelector('.lat').value = p.coords.latitude.toFixed(7); f.querySelector('.lng').value = p.coords.longitude.toFixed(7); },
    () => alert('Location blocked in this browser.'), { enableHighAccuracy: true });
}));
</script>
@endpush
