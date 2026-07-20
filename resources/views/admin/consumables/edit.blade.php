{{-- FILE: resources/views/admin/consumables/edit.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Edit Consumable')
@section('page-title', 'Edit Consumable')
@section('page-sub', $item->part_name . ' · ' . $item->part_code)

@section('content')
<div class="max-w-2xl">

  <form method="POST" action="{{ route('admin.inventory.consumable.update', $item->id) }}" enctype="multipart/form-data">
    @csrf @method('PUT')

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-5 text-sm text-red-700">
      <ul class="list-disc list-inside space-y-1">
        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
      </ul>
    </div>
    @endif

    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-base tracking-wide mb-4 uppercase">Part Details</h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Part Name *</label>
          <input type="text" name="part_name" value="{{ old('part_name', $item->part_name) }}" required
                 class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Brand</label>
          <input type="text" name="brand" value="{{ old('brand', $item->brand) }}"
                 class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Stock Quantity *</label>
          <input type="number" name="stock_qty" value="{{ old('stock_qty', $item->stock_qty) }}" min="0" required
                 class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            Retail Price ({{ $currency['code'] }}) *
          </label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">{{ $currency['symbol'] }}</span>
            <input type="number" name="price_local" value="{{ old('price_local', $item->price_local) }}"
                   step="{{ $currency['code'] === 'NGN' ? '500' : '0.01' }}" min="0" required
                   class="w-full border border-gray-200 rounded-xl pl-7 pr-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
          </div>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            Trade / Wholesale Price <span class="font-normal text-gray-300">(optional)</span>
          </label>
          <div class="relative">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">{{ $currency['symbol'] }}</span>
            <input type="number" name="price_wholesale" value="{{ old('price_wholesale', $item->price_wholesale) }}"
                   step="{{ $currency['code'] === 'NGN' ? '500' : '0.01' }}" min="0"
                   class="w-full border border-gray-200 rounded-xl pl-7 pr-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
          </div>
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Notes</label>
          <textarea name="description" rows="2"
                    class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 resize-none">{{ old('description', $item->description) }}</textarea>
        </div>

        {{-- FIXED: photo upload/display was completely missing from
             this form — editing a consumable could never add or manage
             a photo, regardless of what staff tried. --}}
        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Current Photos</label>
          @php $existingPhotos = json_decode($item->photos ?? '[]', true) ?: []; @endphp
          @if(count($existingPhotos) > 0)
          <div class="grid grid-cols-3 sm:grid-cols-4 gap-3 mb-3">
            @foreach($existingPhotos as $idx => $photoPath)
            <div class="relative group">
              <img src="{{ asset('storage/' . $photoPath) }}" class="w-full h-20 object-cover rounded-lg border border-gray-200">
              <label class="absolute top-1 right-1 bg-red-600 text-white text-[10px] w-5 h-5 rounded-full flex items-center justify-center cursor-pointer hover:bg-red-700">
                <input type="checkbox" name="remove_photos[]" value="{{ $idx }}" class="sr-only">
                ✕
              </label>
            </div>
            @endforeach
          </div>
          <p class="text-[10px] text-gray-400 mb-3">Click the ✕ on a photo to remove it when you save.</p>
          @else
          <p class="text-xs text-gray-400 mb-3">No photos yet.</p>
          @endif

          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            Add Photos <span class="font-normal text-gray-300">(optional, up to 6 total)</span>
          </label>
          <input type="file" name="photos[]" multiple accept="image/*"
                 class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 bg-white">
          <p class="text-[10px] text-gray-400 mt-1">JPG/PNG, max 5MB each.</p>
        </div>

      </div>
    </div>

    <div class="flex gap-3">
      <button type="submit"
              class="flex-1 bg-gold text-navy font-display font-700 text-sm py-3.5 rounded-xl hover:bg-yellow-500 transition-colors">
        Save Changes
      </button>
      <a href="{{ route('admin.inventory.consumable.index') }}"
         class="border border-gray-200 text-gray-500 font-body font-500 text-sm px-5 py-3.5 rounded-xl hover:bg-gray-50 transition-colors">
        Cancel
      </a>
    </div>

  </form>
</div>
@endsection
