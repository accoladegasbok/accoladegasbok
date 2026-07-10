{{-- FILE: resources/views/admin/part-names/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Part Names Manager')
@section('page-title','Part Names Manager')
@section('page-sub','Admin only — merge duplicate/inconsistent names so the catalog stays clean and consistent')

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>
@endif

<form method="GET" class="mb-4">
  <input type="text" name="q" value="{{ $q }}" placeholder="Search part names..."
    class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-full sm:w-96 focus:outline-none focus:border-gold">
</form>

<div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 text-sm font-body mb-5">
  Tick 2 or more names below that mean the same thing, type the ONE canonical name you want them all to become, then click Merge. Every part currently tagged with the old names is instantly retagged — nothing is deleted, just renamed.
</div>

{{-- ── Add New Part Name ─────────────────────────────────────── --}}
<div class="stat-card mb-5">
    <h3 class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-3">+ Add New Part Name</h3>
    <p class="text-xs text-gray-400 mb-3">Add a new standardised part name to the global list. Once added it appears in all dropdowns across harvest, manual add and invoices.</p>
    <form method="POST" action="{{ route('admin.part-names.store') }}" class="flex gap-3 items-end">
        @csrf
        <div class="flex-1">
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">New Part Name *</label>
            <input type="text" name="name" required
                   placeholder="e.g. Laptop Motherboard, Electric Motor, Control Panel..."
                   class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:border-yellow-400">
        </div>
        <div>
            <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1.5">Category</label>
            <select name="category" class="border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm bg-white focus:outline-none focus:border-yellow-400">
                <option value="">Select category</option>
                @foreach(['Engine','Transmission','Electrical','Body','Suspension','Cooling','Brakes','Interior','Fuel','Exhaust','Wheels','Electronics','Computers','General'] as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-gold text-navy font-display font-700 text-sm px-5 py-2.5 rounded-xl hover:bg-yellow-400 transition-colors whitespace-nowrap">
            Add Part Name
        </button>
    </form>
</div>

<form method="POST" action="{{ route('admin.part-names.merge') }}" id="mergeForm">
@csrf
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden mb-5">
  <table class="w-full text-sm font-body">
    <thead>
      <tr class="bg-gray-50 border-b border-gray-200">
        <th class="px-4 py-3"></th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Part Name</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider"># Parts</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Total Stock</th>
        <th class="px-4 py-3"></th>
      </tr>
    </thead>
    <tbody>
      @forelse($names as $n)
      <tr class="border-b border-gray-50 hover:bg-gray-50">
        <td class="px-4 py-3"><input type="checkbox" name="from_names[]" value="{{ $n->part_name }}" class="accent-gold"></td>
        <td class="px-4 py-3 font-700 text-navy">{{ $n->part_name }}</td>
        <td class="px-4 py-3 text-gray-500">{{ $n->part_count }}</td>
        <td class="px-4 py-3 text-gray-500">{{ $n->total_stock }}</td>
        <td class="px-4 py-3 text-right">
          <button type="button" onclick="quickRename('{{ $n->part_name }}')" class="text-xs font-body text-gold hover:text-yellow-600">Rename</button>
        </td>
      </tr>
      @empty
      <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400 text-sm">No part names found.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="bg-white rounded-2xl border-2 border-gold shadow-sm p-5 flex items-end gap-3">
  <div class="flex-1">
    <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Canonical Name (what all checked names become)</label>
    <input type="text" name="to_name" required placeholder="e.g. Headlight"
      class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-gold">
  </div>
  <button type="submit" class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">
    Merge Checked Names
  </button>
</div>
</form>

{{-- Quick single rename (hidden form, triggered by the Rename link per row) --}}
<form method="POST" action="{{ route('admin.part-names.rename-one') }}" id="quickRenameForm" class="hidden">
  @csrf
  <input type="hidden" name="old_name" id="quickRenameOldName">
</form>

<script>
function quickRename(oldName) {
    const newName = prompt(`Rename "${oldName}" to:`, oldName);
    if (!newName || newName === oldName) return;
    document.getElementById('quickRenameOldName').value = oldName;
    const form = document.getElementById('quickRenameForm');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'new_name';
    input.value = newName;
    form.appendChild(input);
    form.submit();
}
</script>

@endsection
