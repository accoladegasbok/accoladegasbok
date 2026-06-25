{{-- FILE: resources/views/admin/assets/edit.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Edit ' . $asset->asset_tag)
@section('page-title','Edit ' . $asset->name)
@section('page-sub', $asset->asset_tag)

@section('content')
<form method="POST" action="{{ route('admin.assets.update', $asset->id) }}" class="max-w-3xl">
@csrf @method('PUT')

@if($errors->any())
<div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700 font-body mb-4">
  @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 space-y-4">
  <div>
    <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Name *</label>
    <input type="text" name="name" required value="{{ old('name', $asset->name) }}"
      class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Category *</label>
      <select name="category" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:border-gold">
        @foreach($categories as $c)<option value="{{ $c }}" {{ old('category',$asset->category)===$c?'selected':'' }}>{{ $c }}</option>@endforeach
      </select>
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Location *</label>
      <select name="location" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:border-gold">
        @foreach($locations as $l)<option value="{{ $l }}" {{ old('location',$asset->location)===$l?'selected':'' }}>{{ $l }}</option>@endforeach
      </select>
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Status *</label>
      <select name="status" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:border-gold">
        @foreach($statuses as $s)<option value="{{ $s }}" {{ old('status',$asset->status)===$s?'selected':'' }}>{{ $s }}</option>@endforeach
      </select>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Serial Number</label>
      <input type="text" name="serial_number" value="{{ old('serial_number', $asset->serial_number) }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Assigned To / Department</label>
      <input type="text" name="assigned_to" value="{{ old('assigned_to', $asset->assigned_to) }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Acquired Date</label>
      <input type="date" name="acquired_date" value="{{ old('acquired_date', $asset->acquired_date) }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Acquired Value</label>
      <input type="number" step="0.01" min="0" name="acquired_value" value="{{ old('acquired_value', $asset->acquired_value) }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Currency</label>
      <select name="acquired_currency" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:border-gold">
        @foreach(['USD','NGN','GHS'] as $cur)<option value="{{ $cur }}" {{ old('acquired_currency',$asset->acquired_currency)===$cur?'selected':'' }}>{{ $cur }}</option>@endforeach
      </select>
    </div>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Last Serviced</label>
      <input type="date" name="last_serviced_date" value="{{ old('last_serviced_date', $asset->last_serviced_date) }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Next Service Due</label>
      <input type="date" name="next_service_due" value="{{ old('next_service_due', $asset->next_service_due) }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
  </div>

  <div>
    <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Notes</label>
    <textarea name="notes" rows="3" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">{{ old('notes', $asset->notes) }}</textarea>
  </div>
</div>

<div class="flex gap-3 justify-end mt-5 pb-8">
  <a href="{{ route('admin.assets.show', $asset->id) }}" class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
  <button type="submit" class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">Save Changes</button>
</div>
</form>
@endsection
