{{-- FILE: resources/views/admin/attendance/clock.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Clock In')
@section('page-title', 'Clock In / Out')
@section('page-sub', 'Your phone fingerprint, a selfie and your location - no WhatsApp photo needed')

@section('content')
@if($notReady)
  <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl px-4 py-3 text-sm font-body">Attendance is not set up yet. Ask the admin to run the database update.</div>
@else
@php
  $tz = $site->timezone;
  $myKeys = $passkeys; $approvedKey = $passkeys->firstWhere('approved', 1);
  $fmt = fn($t) => $t ? \Carbon\Carbon::parse($t)->setTimezone($tz)->format('g:i A') : '—';
@endphp

@if($trainingState === 'red')
<div class="bg-red-600 text-white rounded-2xl px-5 py-4 mb-4 font-body">
  <div class="font-display font-700 text-lg">You cannot clock in - training overdue</div>
  <div class="text-sm mt-1">Finish these today: <b>{{ $overdue->pluck('title')->implode(', ') }}</b>. Until then you are marked absent. <a href="{{ route('admin.training.mine') }}" class="underline font-700">Go to My Training</a></div>
</div>
@elseif($trainingState === 'amber')
<div class="bg-amber-100 border border-amber-300 text-amber-900 rounded-2xl px-5 py-3 mb-4 text-sm font-body">Training due within 7 days. <a href="{{ route('admin.training.mine') }}" class="underline font-700">Open My Training</a></div>
@endif

<div class="grid lg:grid-cols-3 gap-4">
  <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
      <div>
        <div class="font-display font-700 text-navy text-lg">{{ $staff->name }} <span class="text-xs text-gray-400 font-body">· {{ $staff->location }}</span></div>
        <div class="text-xs text-gray-500 font-body">Shift starts {{ $site->shift_start }} (grace {{ $site->grace_minutes }} min) · {{ \Carbon\Carbon::now($tz)->format('D d M Y') }}</div>
      </div>
      <div id="clockNow" class="font-display font-700 text-navy text-2xl tabular-nums"></div>
    </div>

    <div class="rounded-xl bg-gray-50 border border-gray-200 px-4 py-3 text-sm font-body mb-4">
      @if(!$rec)
        <b>Not clocked in yet today.</b>
      @elseif($rec->status === 'blocked')
        <span class="text-red-600 font-700">Marked absent - overdue training.</span> {{ $rec->note }}
      @elseif($rec->status === 'excused' && !$rec->clock_in_at)
        <span class="text-blue-700 font-700">Excused today.</span> {{ $rec->note }}
      @else
        In: <b>{{ $fmt($rec->clock_in_at) }}</b> @if($rec->status==='late')<span class="text-amber-600 font-700">(late {{ $rec->late_minutes }} min)</span>@endif
        · Out: <b>{{ $fmt($rec->clock_out_at) }}</b>
        · Verified by {{ $rec->verified_by === 'passkey' ? 'phone fingerprint/face' : 'selfie' }}
      @endif
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
      <div>
        <video id="cam" playsinline muted autoplay class="w-full aspect-[4/3] bg-black rounded-xl object-cover"></video>
        <canvas id="snap" class="hidden"></canvas>
        <div id="camMsg" class="text-xs text-gray-500 font-body mt-1">Starting camera…</div>
      </div>
      <div class="flex flex-col gap-3">
        <div class="text-xs font-body text-gray-600" id="gpsMsg">Finding your location…</div>
        <div class="text-xs font-body text-gray-600">
          Fingerprint / face:
          @if($approvedKey) <b class="text-green-700">phone registered</b>
          @elseif($passkeys->count()) <b class="text-amber-600">waiting for manager approval</b>
          @else <b class="text-gray-500">no phone registered</b>@endif
        </div>
        <button id="btnIn" type="button" class="bg-navy hover:bg-navy-light text-white font-display font-700 py-3 rounded-xl text-base disabled:opacity-40" {{ ($rec && ($rec->clock_in_at || $rec->status==='blocked')) || $trainingState==='red' ? 'disabled' : '' }}>Clock In</button>
        <button id="btnOut" type="button" class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 py-3 rounded-xl text-base disabled:opacity-40" {{ !$rec || !$rec->clock_in_at || $rec->clock_out_at ? 'disabled' : '' }}>Clock Out</button>
        <div id="result" class="text-sm font-body hidden rounded-xl px-3 py-2"></div>
      </div>
    </div>
  </div>

  <div class="space-y-4">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
      <div class="font-display font-700 text-navy mb-2">My phone</div>
      <p class="text-xs text-gray-500 font-body mb-3">Register this phone once. Afterwards every clock-in asks for your fingerprint or face unlock. The website never sees your fingerprint.</p>
      @foreach($passkeys as $p)
        <div class="text-xs font-body flex items-center justify-between border-b border-gray-100 py-1.5">
          <span>{{ $p->label ?: 'Phone' }}</span>
          <span class="{{ $p->approved ? 'text-green-700' : 'text-amber-600' }} font-700">{{ $p->approved ? 'Approved' : 'Pending' }}</span>
        </div>
      @endforeach
      <button id="btnReg" type="button" class="mt-3 w-full border border-navy text-navy font-display font-700 text-sm py-2 rounded-xl hover:bg-navy hover:text-white transition-colors">Register this phone</button>
      <div id="regMsg" class="text-xs font-body mt-2"></div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
      <div class="font-display font-700 text-navy mb-2">Last 14 days</div>
      @forelse($history as $h)
        <div class="flex items-center justify-between text-xs font-body py-1 border-b border-gray-100">
          <span>{{ \Carbon\Carbon::parse($h->work_date)->format('D d M') }}</span>
          <span>{{ $fmt($h->clock_in_at) }} – {{ $fmt($h->clock_out_at) }}</span>
          <span class="font-700 {{ $h->status==='late' ? 'text-amber-600' : ($h->status==='blocked' ? 'text-red-600' : 'text-green-700') }}">{{ ucfirst($h->status) }}</span>
        </div>
      @empty
        <div class="text-xs text-gray-400 font-body">No records yet.</div>
      @endforelse
    </div>
  </div>
</div>
@endif
@endsection

@push('scripts')
@if(!$notReady)
<script>
(function(){
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const urls = { challenge: @json(route('admin.attendance.challenge')), in: @json(route('admin.attendance.in')), out: @json(route('admin.attendance.out')), reg: @json(route('admin.attendance.passkey.register')) };
  const hasKey = @json((bool) $approvedKey);
  const cam = document.getElementById('cam'), snap = document.getElementById('snap');
  let gps = null, stream = null;

  // live clock (display only - the server records the real time)
  const tick = () => { document.getElementById('clockNow').textContent = new Date().toLocaleTimeString([], {hour:'2-digit', minute:'2-digit', second:'2-digit'}); };
  tick(); setInterval(tick, 1000);

  const b64u = {
    enc: b => btoa(String.fromCharCode(...new Uint8Array(b))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,''),
    dec: s => { s = s.replace(/-/g,'+').replace(/_/g,'/'); s += '='.repeat((4 - s.length % 4) % 4); return Uint8Array.from(atob(s), c => c.charCodeAt(0)); }
  };
  const say = (msg, ok) => { const r = document.getElementById('result'); r.className = 'text-sm font-body rounded-xl px-3 py-2 ' + (ok ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'); r.textContent = msg; };

  navigator.mediaDevices && navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false })
    .then(s => { stream = s; cam.srcObject = s; document.getElementById('camMsg').textContent = 'Camera ready - face the camera.'; })
    .catch(() => { document.getElementById('camMsg').textContent = 'Camera blocked. Allow camera access in your browser settings.'; });

  function locate(){
    return new Promise(res => {
      if (!navigator.geolocation) { document.getElementById('gpsMsg').textContent = 'Location not supported.'; return res(null); }
      navigator.geolocation.getCurrentPosition(p => {
        gps = { lat: p.coords.latitude, lng: p.coords.longitude, accuracy: p.coords.accuracy };
        document.getElementById('gpsMsg').textContent = 'Location found (±' + Math.round(p.coords.accuracy) + ' m).'; res(gps);
      }, () => { document.getElementById('gpsMsg').textContent = 'Location blocked. Allow location access to clock in.'; res(null); }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 });
    });
  }
  locate();

  async function getJson(u){ const r = await fetch(u, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' }); return r.json(); }

  async function register(){
    const m = document.getElementById('regMsg');
    try {
      if (!window.PublicKeyCredential) { m.textContent = 'This browser does not support fingerprint sign-in. Use Chrome or Safari on a phone.'; return; }
      const c = await getJson(urls.challenge + '?purpose=register');
      const cred = await navigator.credentials.create({ publicKey: {
        challenge: b64u.dec(c.challenge), rp: { name: 'Auto Zenith Parts', id: c.rpId },
        user: { id: b64u.dec(c.user.id), name: c.user.name, displayName: c.user.displayName },
        pubKeyCredParams: [{ type: 'public-key', alg: -7 }],
        authenticatorSelection: { authenticatorAttachment: 'platform', userVerification: 'required', residentKey: 'discouraged' },
        attestation: 'none', timeout: 60000 } });
      const resp = cred.response;
      if (!resp.getPublicKey || !resp.getAuthenticatorData) { m.textContent = 'This browser is too old. Update Chrome / Safari.'; return; }
      const body = { credential_id: b64u.enc(cred.rawId), client_data: b64u.enc(resp.clientDataJSON), auth_data: b64u.enc(resp.getAuthenticatorData()),
                     public_key: b64u.enc(resp.getPublicKey()), label: (navigator.userAgentData && navigator.userAgentData.platform || 'Phone') + ' ' + new Date().toLocaleDateString() };
      const r = await fetch(urls.reg, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body) });
      const j = await r.json(); m.textContent = j.message; if (j.ok) setTimeout(() => location.reload(), 1500);
    } catch (e) { m.textContent = 'Not registered: ' + (e.message || e); }
  }

  async function punch(dir){
    const btn = document.getElementById(dir === 'in' ? 'btnIn' : 'btnOut'); btn.disabled = true;
    try {
      say('Checking…', true);
      if (!gps) await locate();
      const fd = new FormData();
      if (gps) { fd.append('lat', gps.lat); fd.append('lng', gps.lng); fd.append('accuracy', gps.accuracy); }
      if (stream && cam.videoWidth) {
        snap.width = 640; snap.height = Math.round(640 * cam.videoHeight / cam.videoWidth);
        snap.getContext('2d').drawImage(cam, 0, 0, snap.width, snap.height);
        const blob = await new Promise(r => snap.toBlob(r, 'image/jpeg', 0.8));
        fd.append('photo', blob, 'selfie.jpg');
      }
      if (hasKey) {
        const c = await getJson(urls.challenge + '?purpose=assert');
        const a = await navigator.credentials.get({ publicKey: { challenge: b64u.dec(c.challenge), rpId: c.rpId, userVerification: 'required', timeout: 60000,
          allowCredentials: c.credentials.map(id => ({ type: 'public-key', id: b64u.dec(id) })) } });
        fd.append('credential_id', b64u.enc(a.rawId)); fd.append('client_data', b64u.enc(a.response.clientDataJSON));
        fd.append('auth_data', b64u.enc(a.response.authenticatorData)); fd.append('signature', b64u.enc(a.response.signature));
      }
      const r = await fetch(urls[dir], { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: fd });
      const j = await r.json(); say(j.message, j.ok);
      if (j.ok || r.status === 423) setTimeout(() => location.reload(), 2200); else btn.disabled = false;
    } catch (e) { say('Cancelled: ' + (e.message || e), false); btn.disabled = false; }
  }

  document.getElementById('btnReg').addEventListener('click', register);
  document.getElementById('btnIn').addEventListener('click', () => punch('in'));
  document.getElementById('btnOut').addEventListener('click', () => punch('out'));
})();
</script>
@endif
@endpush
