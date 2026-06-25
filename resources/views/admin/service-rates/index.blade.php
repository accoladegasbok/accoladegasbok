{{-- FILE: resources/views/admin/service-rates/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Service Rates')
@section('page-title','Fixed-Rate Services')
@section('page-sub','Manage labor/misc charge presets used on Quick Receipts')

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif

<div class="stat-card mb-6">
  <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Add a Service</h2>
  <form method="POST" action="{{ route('admin.service-rates.store') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
    @csrf
    <div>
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Name *</label>
      <input type="text" name="name" required placeholder="e.g. Brake Pad Replacement (Labor)"
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
    </div>
    <div>
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Category</label>
      <input type="text" name="category" placeholder="e.g. Brakes"
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
    </div>
    <div>
      <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Suggested Price</label>
      <input type="number" name="default_price" step="0.01" min="0" placeholder="e.g. 50.00"
        class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
    </div>
    <div>
      <button type="submit" class="w-full bg-gold text-navy font-display font-700 text-sm py-3 rounded-xl hover:bg-yellow-500 transition-colors">+ Add</button>
    </div>
  </form>
  <p class="text-xs text-gray-400 font-body mt-2">Price is a suggested starting point — staff can still adjust it per transaction since it isn't tied to a specific currency.</p>
</div>

<div class="stat-card overflow-hidden p-0">
  <table class="w-full text-sm font-body">
    <thead>
      <tr class="border-b border-gray-100 bg-gray-50">
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Name</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Category</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Suggested Price</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Active</th>
        <th class="px-4 py-3"></th>
      </tr>
    </thead>
    <tbody>
      @forelse($rates as $r)
      <tr class="border-b border-gray-50">
        <td class="px-4 py-3 font-500 text-navy">{{ $r->name }}</td>
        <td class="px-4 py-3 text-gray-500">{{ $r->category ?? '—' }}</td>
        <td class="px-4 py-3 font-mono text-gray-600">{{ $r->default_price ? number_format($r->default_price, 2) : '—' }}</td>
        <td class="px-4 py-3">
          <span class="badge {{ $r->is_active ? 'badge-green' : 'badge-gray' }}">{{ $r->is_active ? 'Active' : 'Inactive' }}</span>
        </td>
        <td class="px-4 py-3 text-right">
          <form method="POST" action="{{ route('admin.service-rates.destroy', $r->id) }}" onsubmit="return confirm('Remove this service?')" class="inline">
            @csrf @method('DELETE')
            <button type="submit" class="text-xs font-body border border-red-200 text-red-400 hover:border-red-400 hover:text-red-600 px-3 py-1.5 rounded-lg transition-colors">Delete</button>
          </form>
        </td>
      </tr>
      @empty
      <tr><td colspan="5" class="px-4 py-12 text-center text-gray-400 text-sm">No services set up yet.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

@endsection
