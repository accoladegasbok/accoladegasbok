{{-- FILE: resources/views/admin/part-names/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Part Names Manager')
@section('page-title','Part Names Manager')
@section('page-sub','Add part names once — they appear on Manual Add, Consumables and (if ticked) the harvest checklist')

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>
@endif
{{-- NEW: this was missing entirely — a failed validation (e.g. the
     field-name mismatch that caused "Add Part Name" to silently fail)
     redirected back with $errors set but nothing on this page ever
     displayed them, so the OLD flash message just kept showing
     instead, making a real failure look like success. --}}
@if($errors->any())
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
</div>
@endif

<form method="GET" class="mb-4">
  <input type="text" name="q" value="{{ $q }}" placeholder="Search part names..."
    class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-full sm:w-96 focus:outline-none focus:border-gold">
</form>

<div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl p-4 text-sm font-body mb-5">
  Tick 2 or more names below that mean the same thing, type the ONE canonical name you want them all to become, then click Merge. Every part currently tagged with the old names is instantly retagged — nothing is deleted, just renamed. Rename and Merge also update the standardized name list used by Add Parts Manually/Harvest, so one change here applies everywhere.
  @if(!$isAdmin) <strong>Merge and Rename are admin-only; you can add names and switch them on or off for the harvest checklist.</strong> @endif
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
                @foreach($categoryOptions as $cat)
                <option value="{{ $cat }}" {{ $cat === 'General' ? 'selected' : '' }}>{{ $cat }}</option>
                @endforeach
            </select>
        </div>
        <label class="flex items-center gap-2 text-xs text-gray-600 pb-3 whitespace-nowrap" title="Adds this name as a row on the harvest checklist. Leave unticked for consumables, electronics and other non-vehicle items.">
            <input type="hidden" name="harvest_checklist" value="0">
            <input type="checkbox" name="harvest_checklist" value="1" checked class="accent-gold">
            Show on harvest checklist
        </label>
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
        <th class="px-4 py-3">@if($isAdmin)<span class="sr-only">Select</span>@endif</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Part Name</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider"># Parts</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Total Stock</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">In Dropdown?</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">On Harvest Checklist?</th>
        <th class="px-4 py-3"></th>
      </tr>
    </thead>
    <tbody>
      @forelse($names as $n)
      <tr class="border-b border-gray-50 hover:bg-gray-50">
        <td class="px-4 py-3">@if($isAdmin)<input type="checkbox" name="from_names[]" value="{{ $n->part_name }}" class="accent-gold">@endif</td>
        <td class="px-4 py-3 font-700 text-navy">{{ $n->part_name }}</td>
        <td class="px-4 py-3 text-gray-500">{{ $n->part_count }}</td>
        <td class="px-4 py-3 text-gray-500">{{ $n->total_stock }}</td>
        <td class="px-4 py-3">
            @if($n->in_taxonomy)
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-green-100 text-green-700 font-700">✓ YES</span>
            @else
            <button type="button" onclick="rowAction('{{ route('admin.part-names.add-to-taxonomy') }}', {{ \Illuminate\Support\Js::from($n->part_name) }})"
                    class="text-[10px] px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 font-700 hover:bg-gold hover:text-navy transition-colors" title="Click to add this to the dropdown">
                — NO (click to add)
            </button>
            @endif
        </td>
        <td class="px-4 py-3">
            @if($n->harvest_state === 'built-in')
            <span class="text-[10px] px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 font-700" title="Always a row on the harvest checklist">BUILT-IN</span>
            @elseif($n->harvest_state === 'on' || $n->harvest_state === 'off')
            <button type="button" onclick="rowAction('{{ route('admin.part-names.toggle-harvest') }}', {{ \Illuminate\Support\Js::from($n->part_name) }})"
                    class="text-[10px] px-2 py-0.5 rounded-full font-700 {{ $n->harvest_state === 'on' ? 'bg-green-100 text-green-700 hover:bg-red-100 hover:text-red-700' : 'bg-gray-100 text-gray-500 hover:bg-gold hover:text-navy' }}" title="Click to switch">
                {{ $n->harvest_state === 'on' ? '✓ ON (click to turn off)' : '— OFF (click to turn on)' }}
            </button>
            @else
            <span class="text-[10px] text-gray-400">add to dropdown first</span>
            @endif
        </td>
        <td class="px-4 py-3 text-right">
          @if($isAdmin)<button type="button" onclick="quickRename({{ \Illuminate\Support\Js::from($n->part_name) }})" class="text-xs font-body text-gold hover:text-yellow-600">Rename</button>@endif
        </td>
      </tr>
      @empty
      <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">No part names found.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

@if($isAdmin)
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
@endif
</form>

{{-- Shared one-click row actions (add to dropdown / toggle harvest). Lives OUTSIDE
     the merge form on purpose — HTML does not allow a form inside a form, which
     is why the earlier "click to add" buttons could not work. --}}
<form method="POST" id="rowActionForm" class="hidden">
  @csrf
  <input type="hidden" name="part_name" id="rowActionName">
</form>

{{-- Quick single rename (hidden form, triggered by the Rename link per row) --}}
<form method="POST" action="{{ route('admin.part-names.rename-one') }}" id="quickRenameForm" class="hidden">
  @csrf
  <input type="hidden" name="old_name" id="quickRenameOldName">
</form>

<script>
function rowAction(url, name) {
    const f = document.getElementById('rowActionForm');
    f.action = url;
    document.getElementById('rowActionName').value = name;
    f.submit();
}
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
