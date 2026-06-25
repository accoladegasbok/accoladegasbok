{{-- FILE: resources/views/admin/assets/create.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Add Asset')
@section('page-title','Add Asset / Equipment')
@section('page-sub','Internal register only — never appears in customer search, POS, or sales')

@section('content')
<form method="POST" action="{{ route('admin.assets.store') }}" class="max-w-3xl">
@csrf

@if($errors->any())
<div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700 font-body mb-4">
  @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 space-y-4">
  <div>
    <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Name *</label>
    <input type="text" name="name" required placeholder="e.g. HP LaserJet Printer, Forklift, Air Compressor"
      class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Category *</label>
      <select name="category" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:border-gold">
        @foreach($categories as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
      </select>
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Location *</label>
      <select name="location" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:border-gold">
        @foreach($locations as $l)<option value="{{ $l }}">{{ $l }}</option>@endforeach
      </select>
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Status *</label>
      <select name="status" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:border-gold">
        @foreach($statuses as $s)<option value="{{ $s }}" {{ $s==='In Service'?'selected':'' }}>{{ $s }}</option>@endforeach
      </select>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Serial Number</label>
      <input type="text" name="serial_number" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Assigned To / Department</label>
      <input type="text" name="assigned_to" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Acquired Date</label>
      <input type="date" name="acquired_date" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Acquired Value</label>
      <input type="number" step="0.01" min="0" name="acquired_value" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Currency</label>
      <select name="acquired_currency" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:border-gold">
        <option value="USD">USD</option><option value="NGN">NGN</option><option value="GHS">GHS</option>
      </select>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Last Serviced</label>
      <input type="date" name="last_serviced_date" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Next Service Due</label>
      <input type="date" name="next_service_due" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
  </div>

  <div>
    <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Notes</label>
    <textarea name="notes" rows="3" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold"></textarea>
  </div>
</div>

<div class="flex gap-3 justify-end mt-5 pb-8">
  <a href="{{ route('admin.assets.index') }}" class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
  <button type="submit" class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">Add Asset</button>
</div>
</form>
@endsection
