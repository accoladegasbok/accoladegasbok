{{-- FILE: resources/views/admin/inventory/create.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Add Part')
@section('page-title','Add Part Manually')
@section('page-sub','Enter a part directly without using the harvest checklist')

@section('content')
<div class="max-w-2xl">
  <form method="POST" action="{{ route('admin.inventory.store') }}">
    @csrf

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 text-sm text-red-700 font-body">
      @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Vehicle</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Brand *</label>
          <select name="brand" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            <option value="">Select brand</option>
            @foreach($brands as $b)<option value="{{ $b }}" {{ old('brand')===$b?'selected':'' }}>{{ $b }}</option>@endforeach
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Model *</label>
          <input type="text" name="model" value="{{ old('model') }}" required placeholder="Camry, Accord, Altima..."
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Year From *</label>
          <input type="number" name="year_from" value="{{ old('year_from') }}" required min="1990" max="{{ date('Y')+1 }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Year To *</label>
          <input type="number" name="year_to" value="{{ old('year_to') }}" required min="1990" max="{{ date('Y')+1 }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
      </div>
    </div>

    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Part Details</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Part Name *</label>
          <input type="text" name="part_name" value="{{ old('part_name') }}" required placeholder="Tail Lamp Assembly, Engine, Hood..."
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Category *</label>
          <select name="part_category" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            <option value="">Select category</option>
            @foreach($categories as $c)<option value="{{ $c }}" {{ old('part_category')===$c?'selected':'' }}>{{ $c }}</option>@endforeach
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Side</label>
          <select name="side" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            <option value="N/A">N/A</option>
            <option value="D/S">D/S (Driver Side)</option>
            <option value="P/S">P/S (Passenger Side)</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Price (USD) *</label>
          <input type="number" name="price_usd" value="{{ old('price_usd') }}" step="0.01" min="0" required
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Grade *</label>
          <select name="condition_grade" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            @foreach(['A'=>'A — Like New','B'=>'B — Good','C'=>'C — Fair','New'=>'New OEM'] as $v=>$l)
              <option value="{{ $v }}" {{ old('condition_grade')===$v?'selected':'' }}>{{ $l }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Location *</label>
          <select name="location" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            @foreach($locations as $l)<option value="{{ $l }}" {{ old('location')===$l?'selected':'' }}>{{ $l }}</option>@endforeach
          </select>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">OEM Part Number</label>
          <input type="text" name="oem_part_number" value="{{ old('oem_part_number') }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body font-mono focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Mileage</label>
          <input type="number" name="mileage" value="{{ old('mileage') }}" min="0"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Colour</label>
          <input type="text" name="colour" value="{{ old('colour') }}" placeholder="White, Silver..."
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Description</label>
          <textarea name="description" rows="2"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 resize-none">{{ old('description') }}</textarea>
        </div>
      </div>
    </div>

    <div class="flex gap-3">
      <button type="submit" class="flex-1 bg-gold text-navy font-display font-700 text-sm py-3.5 rounded-xl tracking-wide hover:bg-yellow-500 transition-colors">
        Add to Inventory
      </button>
      <a href="{{ route('admin.inventory.index') }}" class="border border-gray-200 text-gray-500 font-body font-500 text-sm px-5 py-3.5 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
    </div>
  </form>
</div>
@endsection
