{{-- FILE: resources/views/admin/training/watch.blade.php --}}
@extends('admin.layouts.admin')
@section('title', $course->title)
@section('page-title', $course->title)
@section('page-sub', ucfirst($course->provider_type) . ' training' . ($course->provider_name ? ' - ' . $course->provider_name : '') . ' · pass mark ' . $course->pass_mark . '%')

@section('header-actions')
  <a href="{{ route('admin.training.mine') }}" class="bg-white border border-gray-200 text-navy font-display font-700 text-xs px-3 py-2 rounded-xl">My Training</a>
@endsection

@section('content')
@if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ $errors->first() }}</div>@endif
@php $hasVideo = $course->video_source !== 'none'; $stamp = $staff->name . ' · ID ' . $staff->id . ' · ' . now()->format('d M Y'); @endphp

<div class="max-w-4xl space-y-5">
  @if($course->description)<p class="text-sm text-gray-600 font-body">{{ $course->description }}</p>@endif

  @if(!empty($course->lesson_text))
  <div class="bg-white rounded-2xl border border-gray-200 p-5 font-body text-sm text-gray-700 leading-relaxed">
    @foreach(preg_split('/\R/', trim($course->lesson_text)) as $ln)
      @php $t = trim($ln); @endphp
      @if($t === '')<div class="h-2"></div>
      @elseif(str_starts_with($t, '## '))<h3 class="font-display font-700 text-navy text-base mt-4 mb-1">{{ substr($t, 3) }}</h3>
      @elseif(str_starts_with($t, '- '))<div class="flex gap-2 pl-2"><span class="text-gold">&#9679;</span><span>{{ substr($t, 2) }}</span></div>
      @else<p>{{ $t }}</p>@endif
    @endforeach
  </div>
  @endif

  @if($hasVideo)
  <div id="stage" class="relative bg-black rounded-2xl overflow-hidden select-none" oncontextmenu="return false">
    <div class="aspect-video w-full relative">
      @if($course->video_source === 'upload')
        <video id="vid" class="absolute inset-0 w-full h-full" playsinline disablePictureInPicture controlsList="nodownload noplaybackrate" preload="metadata" src="{{ route('admin.training.video', $course->id) }}"></video>
      @else
        <div id="ytbox" class="absolute inset-0 w-full h-full"></div>
      @endif
      {{-- Watermark: logo + moving staff stamp. Sits above the player so screen recordings carry it too. --}}
      <div class="absolute inset-0 pointer-events-none">
        <img src="{{ asset('images/az-logo-doc.jpg') }}" alt="" class="absolute top-3 left-3 h-9 w-auto rounded opacity-70">
        <div id="wmText" class="absolute text-white text-[11px] sm:text-xs font-700 px-2 py-1 rounded bg-black/30" style="top:20%;left:10%;opacity:.55;transition:all 3s ease-in-out;text-shadow:0 1px 2px #000">{{ $stamp }}</div>
        <div class="absolute bottom-14 right-3 text-white/50 text-[10px] font-700 tracking-widest">AUTO ZENITH PARTS · CONFIDENTIAL TRAINING</div>
      </div>
    </div>
    <div class="flex items-center gap-3 bg-navy px-3 py-2 text-white text-xs font-body">
      <button id="bPlay" type="button" class="w-16 bg-gold text-navy font-display font-700 py-1.5 rounded-lg">Play</button>
      <div id="bar" class="relative flex-1 h-2.5 bg-white/20 rounded-full cursor-pointer"><div id="barMax" class="absolute inset-y-0 left-0 bg-white/30 rounded-full"></div><div id="barPos" class="absolute inset-y-0 left-0 bg-gold rounded-full"></div></div>
      <span id="tm" class="tabular-nums w-24 text-right">0:00 / 0:00</span>
      <button id="bFs" type="button" class="px-2 py-1 border border-white/40 rounded-lg">Full screen</button>
    </div>
  </div>
  <p class="text-xs text-gray-500 font-body">You cannot skip ahead of what you have watched. Your progress is saved - you can close this page and continue later.</p>
  @else
  <div class="bg-white rounded-2xl border border-gray-200 p-5 text-sm font-body">
    @if(!empty($course->lesson_text))
      Read the lesson above carefully, then answer the quiz below. You need {{ $course->pass_mark }}% to pass.
    @else
      This is a {{ $course->provider_type }} session{{ $course->provider_name ? ' with ' . $course->provider_name : '' }}. Attend it and tell your manager; they will mark it complete after you finish.
      @if($qs->isNotEmpty()) You can also answer the quiz below when your manager tells you to.@endif
    @endif
  </div>
  @endif

  <form id="quizForm" method="POST" action="{{ route('admin.training.submit', $a->id) }}" class="bg-white rounded-2xl border border-gray-200 p-5 space-y-4 text-sm font-body {{ $hasVideo && !$a->video_done ? 'hidden' : '' }}">
    @csrf
    @if($course->policy_text)
    <div><div class="font-display font-700 text-navy mb-1">Company policy</div>
      <div class="border border-gray-200 rounded-lg p-3 max-h-48 overflow-y-auto whitespace-pre-line text-gray-700 bg-gray-50">{{ $course->policy_text }}</div>
      <label class="block mt-2"><input type="checkbox" name="accept_policy" value="1"> I have read and I accept this policy.</label></div>
    @endif
    @if($qs->isNotEmpty())
      <div class="font-display font-700 text-navy">Quiz</div>
      @foreach($qs as $i => $q)
        <div><div class="font-700 mb-1">{{ $i + 1 }}. {{ $q->question }}</div>
          @foreach($q->options as $k => $o)<label class="block py-0.5"><input type="radio" name="ans[{{ $q->id }}]" value="{{ $k }}" required> {{ $o }}</label>@endforeach</div>
      @endforeach
    @else
      <div class="text-gray-600">No quiz for this course. Confirm completion below.</div>
    @endif
    <button class="bg-navy text-white font-display font-700 px-6 py-2.5 rounded-xl">Submit</button>
  </form>
</div>
@endsection

@push('scripts')
@if($hasVideo)
@if($course->video_source === 'youtube')<script src="https://www.youtube.com/iframe_api"></script>@endif
<script>
(function(){
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const pingUrl = @json(route('admin.training.ping', $a->id));
  let maxWatched = {{ (int) $a->max_position }};
  let done = {{ $a->video_done ? 'true' : 'false' }};
  let playing = false, adapter = null, lastSent = 0;
  const fmt = s => { s = Math.max(0, Math.floor(s || 0)); return Math.floor(s/60) + ':' + String(s%60).padStart(2,'0'); };
  const el = id => document.getElementById(id);

  // moving watermark stamp
  const wm = el('wmText');
  setInterval(() => { wm.style.top = (12 + Math.random()*62) + '%'; wm.style.left = (4 + Math.random()*55) + '%'; }, 6000);

  function send(){
    if (!adapter) return;
    const body = new URLSearchParams({ position: adapter.time(), duration: adapter.dur() });
    fetch(pingUrl, { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body })
      .then(r => r.json()).then(j => {
        if (j.max !== undefined) maxWatched = Math.max(maxWatched, j.max);
        if (j.done && !done) { done = true; el('quizForm').classList.remove('hidden'); el('quizForm').scrollIntoView({ behavior: 'smooth' }); }
      }).catch(() => {});
  }
  setInterval(() => { if (playing) send(); }, 8000);
  window.addEventListener('beforeunload', () => { if (adapter && navigator.sendBeacon) { const f = new FormData(); f.append('position', adapter.time()); f.append('duration', adapter.dur()); f.append('_token', csrf); navigator.sendBeacon(pingUrl, f); } });

  function ui(){
    if (!adapter) return;
    const d = adapter.dur() || 1, t = adapter.time();
    // never let the learner be ahead of what the server accepted (+ a few seconds of buffer)
    if (t > maxWatched + 6 && !done) { adapter.seek(maxWatched); }
    else if (t > maxWatched) maxWatched = Math.max(maxWatched, Math.floor(t));
    el('barPos').style.width = (100 * t / d) + '%'; el('barMax').style.width = (100 * Math.min(1, (done ? d : maxWatched) / d)) + '%';
    el('tm').textContent = fmt(t) + ' / ' + fmt(d);
    el('bPlay').textContent = playing ? 'Pause' : 'Play';
  }
  setInterval(ui, 500);

  el('bPlay').onclick = () => { if (!adapter) return; playing ? adapter.pause() : adapter.play(); };
  el('bFs').onclick = () => { const s = el('stage'); (s.requestFullscreen || s.webkitRequestFullscreen).call(s); };
  el('bar').onclick = e => {
    if (!adapter) return;
    const r = el('bar').getBoundingClientRect(), want = (e.clientX - r.left) / r.width * adapter.dur();
    adapter.seek(done ? want : Math.min(want, maxWatched));
  };

  @if($course->video_source === 'upload')
  const v = el('vid');
  adapter = { play: () => v.play(), pause: () => v.pause(), time: () => v.currentTime || 0, dur: () => v.duration || 0, seek: t => { v.currentTime = t; } };
  v.addEventListener('loadedmetadata', () => { if (maxWatched > 3 && !done) v.currentTime = Math.max(0, maxWatched - 2); });
  v.addEventListener('play', () => { playing = true; });
  v.addEventListener('pause', () => { playing = false; send(); });
  v.addEventListener('ended', () => { playing = false; send(); });
  v.addEventListener('ratechange', () => { if (v.playbackRate !== 1) v.playbackRate = 1; });
  v.addEventListener('seeking', () => { if (!done && v.currentTime > maxWatched + 6) v.currentTime = maxWatched; });
  @else
  window.onYouTubeIframeAPIReady = function(){
    const p = new YT.Player('ytbox', { videoId: @json($course->youtube_id), width: '100%', height: '100%',
      playerVars: { controls: 0, disablekb: 1, rel: 0, modestbranding: 1, playsinline: 1, fs: 0, iv_load_policy: 3 },
      events: { onReady: () => { adapter = { play: () => p.playVideo(), pause: () => p.pauseVideo(), time: () => p.getCurrentTime() || 0, dur: () => p.getDuration() || 0, seek: t => p.seekTo(t, true) };
                  if (maxWatched > 3 && !done) p.seekTo(Math.max(0, maxWatched - 2), true); },
                onStateChange: e => { playing = (e.data === 1); if (e.data === 2 || e.data === 0) send(); if (e.data === 1) p.setPlaybackRate(1); } } });
  };
  @endif
})();
</script>
@endif
@endpush
