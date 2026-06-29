{{-- FILE: resources/views/admin/tabs/create.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Open New Tab')
@section('page-title','Open New Tab')
@section('page-sub','One running tab for a customer visiting multiple times today')

@section('content')

@if(session('existing_tab'))
@php $existing = session('existing_tab'); @endphp
<div class="bg-amber-50 border-2 border-amber-300 rounded-2xl p-5 mb-5 max-w-xl">
  <div class="font-display font-700 text-navy text-sm mb-2">⚠ This customer already has an open tab</div>
  <p class="text-sm text-gray-600 font-body mb-4">
    <strong>{{ $existing->customer_name }}</strong> ({{ $existing->customer_phone }}) already has open tab
    <strong>{{ $existing->tab_no }}</strong>, opened {{ \Carbon\Carbon::parse($existing->created_at)->diffForHumans() }}.
    Is this the same visit/day, or a genuinely new tab?
  </p>
  <div class="flex gap-3">
    <a href="{{ route('admin.tabs.show', $existing->id) }}" class="flex-1 text-center bg-navy text-white font-display font-700 text-sm py-2.5 rounded-xl hover:bg-navy-light transition-colors">
      Continue Existing Tab {{ $existing->tab_no }}
    </a>
    <form method="POST" action="{{ route('admin.tabs.store') }}" class="flex-1">
      @csrf
      <input type="hidden" name="customer_name" value="{{ old('customer_name') }}">
      <input type="hidden" name="customer_phone" value="{{ old('customer_phone') }}">
      <input type="hidden" name="customer_email" value="{{ old('customer_email') }}">
      <input type="hidden" name="location" value="{{ old('location') }}">
      <input type="hidden" name="notes" value="{{ old('notes') }}">
      <input type="hidden" name="force_new" value="1">
      <button type="submit" class="w-full border border-amber-400 text-amber-700 font-body font-500 text-sm py-2.5 rounded-xl hover:bg-amber-100 transition-colors">
        No — Open a Genuinely New Tab
      </button>
    </form>
  </div>
</div>
@endif

<form method="POST" action="{{ route('admin.tabs.store') }}" class="max-w-xl">
@csrf
@if($errors->any())
<div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700 font-body mb-4">
  @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 space-y-4">
  <div>
    <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Customer Name *</label>
    <input type="text" name="customer_name" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
  </div>
  <div class="grid grid-cols-2 gap-3">
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Phone *</label>
      <input type="text" name="customer_phone" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Email</label>
      <input type="email" name="customer_email" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
  </div>
  <div>
    <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Location *</label>
    <select name="location" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:border-gold">
      <option value="">Select location...</option>
      @foreach($locations as $loc)<option value="{{ $loc }}">{{ $loc }}</option>@endforeach
    </select>
  </div>
  <div>
    <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Notes</label>
    <textarea name="notes" rows="2" placeholder="e.g. Customer's car in for full service, multiple visits expected today" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold"></textarea>
  </div>
</div>

<div class="flex gap-3 justify-end mt-5 pb-8">
  <a href="{{ route('admin.tabs.index') }}" class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
  <button type="submit" class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">Open Tab</button>
</div>
</form>
@endsection
