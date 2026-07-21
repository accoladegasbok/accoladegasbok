{{-- FILE: resources/views/admin/storage/scan-lookup.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Scan Lookup')
@section('page-title', 'Scan Lookup')
@section('page-sub', 'Scan or type ANY room code or bin code — a room code shows everything stored in it; a bin code goes straight to that bin')

@section('content')

@if($error ?? null)
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ $error }}</div>
@endif

<div class="stat-card max-w-xl">
  <form method="GET" action="{{ route('admin.storage.scan') }}">
    <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Room or Bin Code</label>
    <input type="text" name="code" id="lookupInput" autocomplete="off" autofocus
      placeholder="Scan or type a code, e.g. FE-LE1 or FE-LE1-A-01-02..."
      class="w-full border-2 border-gold rounded-lg px-4 py-3 text-base font-mono focus:outline-none focus:ring-2 focus:ring-gold">
  </form>
  <p class="text-xs text-gray-400 font-body mt-3">Scanning submits automatically — no need to click anything.</p>
</div>

<script>
document.getElementById('lookupInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        this.form.submit();
    }
});
</script>

@endsection
