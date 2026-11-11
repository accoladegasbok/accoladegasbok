{{-- FILE: resources/views/admin/training/course-form.blade.php --}}
@extends('admin.layouts.admin')
@section('title', $course ? 'Edit Course' : 'New Course')
@section('page-title', $course ? 'Edit Course' : 'New Training Course')
@section('page-sub', 'The training administrator sets length, repeat time and whether the provider is internal or external')

@section('content')
@if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ $errors->first() }}</div>@endif
@php $v = fn($k, $d = '') => old($k, $course->$k ?? $d); @endphp
<form method="POST" enctype="multipart/form-data" action="{{ $course ? route('admin.training.update', $course->id) : route('admin.training.store') }}" class="space-y-5 text-sm font-body max-w-4xl">
  @csrf
  <div class="bg-white rounded-2xl border border-gray-200 p-5 grid sm:grid-cols-2 gap-4">
    <label class="sm:col-span-2">Course title<input name="title" value="{{ $v('title') }}" required class="w-full border border-gray-200 rounded-lg px-3 py-2"></label>
    <label>Area<input name="area" list="areas" value="{{ $v('area') }}" placeholder="Stock keeping, Customer service, Auto mechanic…" class="w-full border border-gray-200 rounded-lg px-3 py-2">
      <datalist id="areas"><option>Stock keeping</option><option>Inventory management</option><option>Customer service</option><option>Auto mechanic</option><option>Repairs</option><option>Parts identification</option><option>Safety</option><option>Company policies</option></datalist></label>
    <label>Pass mark (%)<input type="number" name="pass_mark" value="{{ $v('pass_mark', 80) }}" class="w-full border border-gray-200 rounded-lg px-3 py-2"></label>
    <label class="sm:col-span-2">Description<textarea name="description" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2">{{ $v('description') }}</textarea></label>
  </div>

  <div class="bg-white rounded-2xl border border-gray-200 p-5 grid sm:grid-cols-2 gap-4">
    <label>Who delivers it?
      <select name="provider_type" id="ptype" class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-white">
        <option value="internal" {{ $v('provider_type','internal')==='internal'?'selected':'' }}>Internal (our own staff / video)</option>
        <option value="external" {{ $v('provider_type')==='external'?'selected':'' }}>External (outside trainer / company)</option></select></label>
    <label>Provider name<input name="provider_name" value="{{ $v('provider_name') }}" placeholder="e.g. Toyota Nigeria Academy" class="w-full border border-gray-200 rounded-lg px-3 py-2"></label>
    <label>Repeat every (days) - leave empty for one-time<input type="number" name="recurrence_days" value="{{ $v('recurrence_days') }}" placeholder="e.g. 180" class="w-full border border-gray-200 rounded-lg px-3 py-2"></label>
    <label>Length (minutes)<input type="number" name="duration_minutes" value="{{ $v('duration_minutes') }}" class="w-full border border-gray-200 rounded-lg px-3 py-2"></label>
  </div>

  <div class="bg-white rounded-2xl border border-gray-200 p-5 space-y-3">
    <label>Training content
      <select name="video_source" id="vsrc" class="w-full border border-gray-200 rounded-lg px-3 py-2 bg-white">
        <option value="upload" {{ $v('video_source','upload')==='upload'?'selected':'' }}>Upload a video</option>
        <option value="youtube" {{ $v('video_source')==='youtube'?'selected':'' }}>YouTube link (unlisted video works well)</option>
        <option value="none" {{ $v('video_source')==='none'?'selected':'' }}>No video - classroom / external session (manager marks it done)</option></select></label>
    <div id="boxUpload"><input type="file" name="video" accept="video/mp4,video/webm,video/quicktime" class="text-xs">
      @if($course && $course->video_path)<div class="text-xs text-green-700 mt-1">A video is already uploaded. Choose a file only to replace it.</div>@endif
      <div class="text-[11px] text-gray-400 mt-1">Large files need a high upload limit on the server (upload_max_filesize / post_max_size). If an upload fails, use the YouTube option.</div></div>
    <div id="boxYt"><input name="youtube_url" value="{{ old('youtube_url', ($course && $course->youtube_id) ? 'https://youtu.be/'.$course->youtube_id : '') }}" placeholder="https://youtu.be/..." class="w-full border border-gray-200 rounded-lg px-3 py-2"></div>
  </div>

  <div class="bg-white rounded-2xl border border-gray-200 p-5">
    <label>Written lesson (optional - shown to staff above the quiz)
      <textarea name="lesson_text" rows="12" class="w-full border border-gray-200 rounded-lg px-3 py-2 font-mono text-xs" placeholder="## Heading&#10;A paragraph of text.&#10;- a bullet point">{{ $v('lesson_text') }}</textarea></label>
    <div class="text-[11px] text-gray-400 mt-1">Start a line with ## for a heading and - for a bullet. Blank lines separate paragraphs.</div>
  </div>

  <div class="bg-white rounded-2xl border border-gray-200 p-5">
    <label>Company policy to read and accept (optional)<textarea name="policy_text" rows="4" class="w-full border border-gray-200 rounded-lg px-3 py-2" placeholder="Staff must tick that they read and accept this before the course can be completed.">{{ $v('policy_text') }}</textarea></label>
  </div>

  <div class="bg-white rounded-2xl border border-gray-200 p-5">
    <div class="flex items-center justify-between mb-2"><div class="font-display font-700 text-navy">Quiz questions</div><button type="button" id="addQ" class="text-xs bg-navy text-white px-3 py-1 rounded">+ Add question</button></div>
    <div id="qs" class="space-y-4"></div>
    <p class="text-[11px] text-gray-400 mt-2">Empty questions are ignored. Choose the right answer with the radio button. With no questions, finishing the video completes the course.</p>
  </div>

  <div class="flex gap-3"><button class="bg-gold text-navy font-display font-700 px-6 py-2.5 rounded-xl">Save course</button>
    <a href="{{ route('admin.training.index', ['tab' => 'courses']) }}" class="px-4 py-2.5 text-gray-500">Cancel</a></div>
</form>
@endsection

@push('scripts')
<script>
(function(){
  const existing = @json($questions->map(fn($q) => ['q' => $q->question, 'o' => $q->options, 'c' => $q->correct_index])->values());
  const box = document.getElementById('qs'); let n = 0;
  function add(q){
    const i = n++; const o = (q && q.o && q.o.length ? q.o : ['', '', '', '']);
    while (o.length < 4) o.push('');
    const d = document.createElement('div'); d.className = 'border border-gray-200 rounded-xl p-3';
    d.innerHTML = '<input name="q['+i+']" placeholder="Question" class="w-full border border-gray-200 rounded px-2 py-1 mb-2">' +
      o.map((t, k) => '<div class="flex items-center gap-2 mb-1"><input type="radio" name="correct['+i+']" value="'+k+'" '+((q ? q.c : 0)==k?'checked':'')+'><input name="opt['+i+'][]" placeholder="Answer '+(k+1)+'" class="flex-1 border border-gray-200 rounded px-2 py-1"></div>').join('');
    box.appendChild(d);
    d.querySelector('input[name^="q["]').value = q ? q.q : '';
    d.querySelectorAll('input[name^="opt"]').forEach((el, k) => el.value = o[k] || '');
  }
  (existing.length ? existing : [null, null, null]).forEach(add);
  document.getElementById('addQ').onclick = () => add(null);
  const vs = document.getElementById('vsrc');
  const sync = () => { document.getElementById('boxUpload').style.display = vs.value === 'upload' ? '' : 'none'; document.getElementById('boxYt').style.display = vs.value === 'youtube' ? '' : 'none'; };
  vs.onchange = sync; sync();
})();
</script>
@endpush
