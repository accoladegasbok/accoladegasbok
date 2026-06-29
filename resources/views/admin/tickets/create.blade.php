{{-- FILE: resources/views/admin/tickets/create.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Raise a Ticket')
@section('page-title','Raise a Ticket')
@section('page-sub','Request something from admin/manager — they\'ll be notified by email and SMS')

@section('content')
<form method="POST" action="{{ route('admin.tickets.store') }}" class="max-w-2xl">
@csrf

@if($errors->any())
<div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700 font-body mb-4">
  @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 space-y-4">
  <div>
    <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Category *</label>
    <select name="category" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:outline-none focus:border-gold">
      @foreach($categories as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach
    </select>
  </div>
  <div>
    <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Subject *</label>
    <input type="text" name="subject" required placeholder="Short summary, e.g. Delete invoice INV-2026-0042 (wrong customer)"
      class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
  </div>
  <div>
    <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Description</label>
    <textarea name="description" rows="4" placeholder="Explain what you need and why..."
      class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold"></textarea>
  </div>
</div>

<div class="flex gap-3 justify-end mt-5 pb-8">
  <a href="{{ route('admin.tickets.index') }}" class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-6 py-3 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
  <button type="submit" class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">Submit Ticket</button>
</div>
</form>
@endsection
