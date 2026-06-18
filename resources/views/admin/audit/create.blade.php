{{-- FILE: resources/views/admin/audit/create.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Start New Audit')
@section('page-title','Start New Audit')
@section('page-sub','Choose a location and category to begin counting')
@section('content')

@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl mb-5">
  {{ session('error') }}
</div>
@endif

<form method="POST" action="{{ route('admin.audit.store') }}" class="max-w-xl">
  @csrf
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-5">
    <div class="mb-4">
      <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Warehouse Location *</label>
      <select name="location" required
        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-gold">
        <option value="">Select Location</option>
        @foreach($locations as $loc)
          <option value="{{ $loc }}">{{ $loc }}</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="block text-xs text-gray-500 font-body uppercase tracking-wider mb-1">Part Category *</label>
      <select name="category" required
        class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-gold">
        <option value="">Select Category</option>
        @foreach($categories as $cat)
          <option value="{{ $cat }}">{{ $cat === 'Consumable' ? 'Consumable (Oils, Fluids & Filters)' : $cat }}</option>
        @endforeach
      </select>
    </div>
  </div>

  <p class="text-xs text-gray-400 mb-4">
    This will create a count sheet of every available part in this location and category, snapshotting the system's expected quantities at this moment.
  </p>

  <div class="flex gap-3 justify-end">
    <a href="{{ route('admin.audit.index') }}"
      class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">
      Cancel
    </a>
    <button type="submit"
      class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">
      Start Audit
    </button>
  </div>
</form>

@endsection
