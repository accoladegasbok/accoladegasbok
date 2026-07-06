{{-- FILE: resources/views/admin/inventory/edit.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Edit Part')
@section('page-title','Edit Part')
@section('page-sub', $part->part_code . ' — ' . $part->part_name)

@section('content')
<div class="max-w-3xl">

  {{-- ── Photos — separate mini-form, uploads immediately on submit ── --}}
  <div class="stat-card mb-4">
    <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">Photos</h2>
    <p class="text-xs text-gray-400 font-body mb-3">Customers see these on the parts search page. First photo shown is the main display photo.</p>

    @php $photos = json_decode($part->photos ?? '[]', true) ?: []; @endphp

    @if(count($photos))
    <div class="grid grid-cols-3 sm:grid-cols-5 gap-2 mb-4">
      @foreach($photos as $i => $photo)
      <div class="relative group">
        <img src="{{ asset(config('media.prefix') . '/' . $photo) }}" class="w-full h-24 object-cover rounded-lg border border-gray-200">
        @if($i === 0)<span class="absolute top-1 left-1 bg-gold text-navy text-[10px] font-700 px-1.5 py-0.5 rounded">Main</span>@endif
        <form method="POST" action="{{ route('admin.inventory.photos.delete', $part->id) }}" class="absolute top-1 right-1" onsubmit="return confirm('Remove this photo?')">
          @csrf
          <input type="hidden" name="path" value="{{ $photo }}">
          <button type="submit" class="bg-red-500 text-white rounded-full w-5 h-5 text-xs leading-none opacity-0 group-hover:opacity-100 transition-opacity">✕</button>
        </form>
      </div>
      @endforeach
    </div>
    @else
    <p class="text-xs text-gray-400 font-body mb-3">No photos yet.</p>
    @endif

    <form method="POST" action="{{ route('admin.inventory.photos.add', $part->id) }}" enctype="multipart/form-data" class="flex gap-2">
      @csrf
      <input type="file" name="photos[]" multiple accept="image/*" required
        class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      <button type="submit" class="bg-gold text-navy font-display font-700 text-sm px-4 py-2 rounded-lg hover:bg-yellow-400 transition-colors whitespace-nowrap">
        + Add Photos
      </button>
    </form>
  </div>

  {{-- ── Video — optional, one per part ── --}}
  <div class="stat-card mb-4">
    <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">Video</h2>
    <p class="text-xs text-gray-400 font-body mb-3">One short video per part — MP4, MOV, AVI or WEBM, max 50MB.</p>

    @if(!empty($part->video_path))
    <div class="mb-3">
      <video controls class="w-full max-w-sm rounded-lg border border-gray-200">
        <source src="{{ asset(config('media.prefix') . '/' . $part->video_path) }}">
      </video>
      <form method="POST" action="{{ route('admin.inventory.video.delete', $part->id) }}" onsubmit="return confirm('Remove this video?')" class="mt-2">
        @csrf
        <button type="submit" class="text-xs font-body text-red-500 hover:text-red-700">✕ Remove Video</button>
      </form>
    </div>
    @else
    <p class="text-xs text-gray-400 font-body mb-3">No video yet.</p>
    @endif

    <form method="POST" action="{{ route('admin.inventory.video.add', $part->id) }}" enctype="multipart/form-data" class="flex gap-2">
      @csrf
      <input type="file" name="video" accept="video/*" required
        class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-gold">
      <button type="submit" class="bg-gold text-navy font-display font-700 text-sm px-4 py-2 rounded-lg hover:bg-yellow-400 transition-colors whitespace-nowrap">
        {{ !empty($part->video_path) ? 'Replace Video' : '+ Add Video' }}
      </button>
    </form>
  </div>

  <form method="POST" action="{{ route('admin.inventory.update', $part->id) }}">
    @csrf @method('PUT')

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 text-sm text-red-700 font-body">
      @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    {{-- ── Basic Details ──────────────────────────────────────────── --}}
    <div class="stat-card mb-4">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Part Details</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Part Name *</label>
          <input type="text" name="part_name" value="{{ old('part_name', $part->part_name) }}" required
            list="partNameDatalist" placeholder="Select a standard name, or type a new one"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
          <datalist id="partNameDatalist">
            @foreach(\App\Data\PartNames::flat() as $pn)
              <option value="{{ $pn }}"></option>
            @endforeach
          </datalist>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            Price ({{ $part->currency_code ?? 'USD' }}) *
            <span class="text-gray-400 font-normal normal-case">— this part's real, fixed price. Never auto-converted.</span>
          </label>
          <input type="number" name="price_usd" value="{{ old('price_usd', $part->price_local ?? $part->price_usd) }}" step="0.01" min="0" required
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Condition Grade *</label>
          <select name="condition_grade" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            @foreach(['A','B','C','New'] as $g)
              <option value="{{ $g }}" {{ old('condition_grade',$part->condition_grade)===$g?'selected':'' }}>Grade {{ $g }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Status *</label>
          {{-- Now includes all 7 real statuses. Previously only had
               Available/Reserved/Sold — Missing/Hold/Core/Scrapped
               existed conceptually elsewhere in the code (e.g. the
               bin-exclusivity check already treats 'Hold' as active)
               but were never selectable here. --}}
          <select name="status" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            @foreach(['Available','Reserved','Sold','Missing','Hold','Core','Scrapped'] as $s)
              <option value="{{ $s }}" {{ old('status',$part->status)===$s?'selected':'' }}>{{ $s }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Location *</label>
          <select name="location" id="locationSelect" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            @foreach($locations as $l)
              <option value="{{ $l }}" {{ old('location',$part->location)===$l?'selected':'' }}>{{ $l }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Store Room</label>
          <select id="storeRoomSelect" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            <option value="">Loading...</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Bin</label>
          <select id="binSelect" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            <option value="">Select Store Room first</option>
          </select>
          <input type="hidden" name="storage_shelf_id" id="storageShelfIdInput" value="{{ old('storage_shelf_id', $part->storage_shelf_id) }}">
          <input type="hidden" name="confirm_shared_bin" id="confirmSharedBinInput" value="">
          <input type="hidden" name="bin_location" id="binLocationInput" value="{{ old('bin_location', $part->bin_location) }}">
          <p class="text-xs text-gray-400 font-body mt-1">Current: <span class="font-mono">{{ $part->bin_location ?: '—' }}</span></p>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">OEM Part Number</label>
          <input type="text" name="oem_part_number" value="{{ old('oem_part_number', $part->oem_part_number) }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body font-mono focus:outline-none focus:border-yellow-400">
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Mileage (miles)</label>
          <input type="number" name="mileage" value="{{ old('mileage', $part->mileage) }}" min="0"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Colour</label>
          <input type="text" name="colour" value="{{ old('colour', $part->colour) }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>

      </div>
    </div>

    {{-- ── OEM Engine / Transmission Codes (items 6 & 7) ──────────── --}}
    @if(in_array($part->part_category, ['Engine','Transmission']))
    <div class="stat-card mb-4">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">
        Engine / Transmission / Gear Details
      </h2>
      <p class="text-xs text-gray-400 font-body mb-4">OEM codes make parts searchable by engine/transmission code in the Nigerian market.</p>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            OEM Engine Code
            <span class="text-gray-400 normal-case tracking-normal font-400">e.g. 2ZRFE, 2ARFE, 2GRFE</span>
          </label>
          <input type="text" name="engine_code_oem"
            value="{{ old('engine_code_oem', $part->engine_code_oem ?? '') }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body font-mono focus:outline-none focus:border-yellow-400"
            placeholder="e.g. 2ZRFE">
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            OEM Transmission / Gear Code
            <span class="text-gray-400 normal-case tracking-normal font-400">e.g. U341E, U760E, A750E</span>
          </label>
          <input type="text" name="transmission_code_oem"
            value="{{ old('transmission_code_oem', $part->transmission_code_oem ?? '') }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body font-mono focus:outline-none focus:border-yellow-400"
            placeholder="e.g. U341E">
        </div>

        {{-- Pin count — Nigerian market item 10 --}}
        @if($part->part_category === 'Transmission')
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            Pin Count (Nigerian market)
            <span class="text-gray-400 normal-case tracking-normal font-400">e.g. 13, 22</span>
          </label>
          <input type="number" name="pin_count"
            value="{{ old('pin_count', $part->pin_count ?? '') }}"
            min="1" max="99"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body font-mono focus:outline-none focus:border-yellow-400"
            placeholder="e.g. 13">
          <p class="text-xs text-gray-400 font-body mt-1">
            Physical pin count on transmission connector.<br>
            2AZ-FE 4cyl = 13 pins · 2GR-FE V6 = 22 pins
          </p>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            Gear Alias (Nigerian market label)
          </label>
          <input type="text" name="gear_alias"
            value="{{ old('gear_alias', $part->gear_alias ?? '') }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="e.g. 13-pin gear, 22-pin gear">
          <p class="text-xs text-gray-400 font-body mt-1">How this gear is known in the Nigerian market.</p>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            Drive Type
          </label>
          <select name="drive_type" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            <option value="">Not specified</option>
            @foreach(['2WD','4WD','AWD','RWD','FWD','4x2','4x4'] as $dt)
              <option value="{{ $dt }}" {{ old('drive_type', $part->drive_type ?? '') === $dt ? 'selected' : '' }}>{{ $dt }}</option>
            @endforeach
          </select>
          <p class="text-xs text-gray-400 font-body mt-1">Drive configuration — shown to customers alongside pin count.</p>
        </div>
        @endif

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Origin Market</label>
          <select name="origin_market" class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            @foreach(['N/A','JDM','USDM','EDM','Nigerian Used'] as $om)
              <option value="{{ $om }}" {{ old('origin_market', $part->origin_market ?? 'N/A')===$om?'selected':'' }}>{{ $om }}</option>
            @endforeach
          </select>
        </div>

      </div>
    </div>
    @endif

    {{-- ── Vehicle Fitment (items 4, 5, 9, 11) ──────────────────────── --}}
    <div class="stat-card mb-4">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">
        Vehicle Fitment &amp; Compatibility
      </h2>
      <p class="text-xs text-gray-400 font-body mb-4">
        Set the year range this part is compatible with — wider than the donor year alone.
        E.g. a 2009 Corolla door fits 2009–2013.
      </p>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            Compatible From Year
          </label>
          <select name="compat_year_from"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            @php $selFrom = old('compat_year_from', $part->compat_year_from ?? $part->year_from); @endphp
            @foreach(\App\Data\VehicleDatabase::years() as $yr)
              <option value="{{ $yr }}" {{ (string)$selFrom === (string)$yr ? 'selected' : '' }}>{{ $yr }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            Compatible To Year
          </label>
          <select name="compat_year_to"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none focus:border-yellow-400">
            @php $selTo = old('compat_year_to', $part->compat_year_to ?? $part->year_to); @endphp
            @foreach(\App\Data\VehicleDatabase::years() as $yr)
              <option value="{{ $yr }}" {{ (string)$selTo === (string)$yr ? 'selected' : '' }}>{{ $yr }}</option>
            @endforeach
          </select>
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            Compatible Trims (comma-separated)
          </label>
          <input type="text" name="compatible_trims"
            value="{{ old('compatible_trims', $part->compatible_trims ?? '') }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="e.g. L, LE, SE, XLE">
          <p class="text-xs text-gray-400 font-body mt-1">Leave blank to indicate all trims are compatible.</p>
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            Not Compatible With
          </label>
          <input type="text" name="not_compatible_note"
            value="{{ old('not_compatible_note', $part->not_compatible_note ?? '') }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="e.g. Not compatible with 3.5L V6 or Hybrid (eCVT) models">
        </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
            Full Fitment Notes (shown on product page)
          </label>
          <textarea name="fitment_notes" rows="4"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 resize-none"
            placeholder="e.g. This transmission is a direct replacement for the XV50 Generation Toyota Camry equipped with the 2.5L engine. Years: 2013–2017. Trims: L, LE, SE, XLE (Gasoline Models). Not compatible with 3.5L V6 or Hybrid models.">{{ old('fitment_notes', $part->fitment_notes ?? '') }}</textarea>
            @include('partials.product-info', ['part' => $part])
          </div>

        <div class="sm:col-span-2">
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Description / Notes</label>
          <textarea name="description" rows="2"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400 resize-none">{{ old('description', $part->description) }}</textarea>
        </div>

      </div>
    </div>

    {{-- Read-only vehicle info --}}
    <div class="stat-card mb-5 bg-gray-50">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-3">Donor Vehicle (read-only)</h2>
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs font-body">
        <div><div class="text-gray-400 mb-0.5">Brand</div><div class="font-500 text-navy">{{ $part->brand }}</div></div>
        <div><div class="text-gray-400 mb-0.5">Model</div><div class="font-500 text-navy">{{ $part->model }}</div></div>
        <div><div class="text-gray-400 mb-0.5">Donor Year</div><div class="font-500 text-navy">{{ $part->year_from }}</div></div>
        <div><div class="text-gray-400 mb-0.5">Part Code</div><div class="font-500 font-mono text-navy">{{ $part->part_code }}</div></div>
        @if($part->donor_vin)
        <div class="sm:col-span-4"><div class="text-gray-400 mb-0.5">Donor VIN</div><div class="font-mono text-navy">{{ $part->donor_vin }}</div></div>
        @endif
      </div>
    </div>

    <div class="flex gap-3">
      <button type="submit" class="flex-1 bg-gold text-navy font-display font-700 text-sm py-3.5 rounded-xl tracking-wide hover:bg-yellow-500 transition-colors">
        Save Changes
      </button>
      <a href="{{ route('admin.inventory.index') }}" class="border border-gray-200 text-gray-500 font-body font-500 text-sm px-5 py-3.5 rounded-xl hover:bg-gray-50 transition-colors">
        Cancel
      </a>
    </div>
  </form>

  {{-- ── Interchange / Compatibility Aggregation (Phase B3) ───────────
       Separate forms (own POST actions) — not part of the main Save form. --}}
  <div class="stat-card mb-5">
    <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">Interchangeable Parts</h2>
    <p class="text-xs text-gray-400 font-body mb-4">
      Parts in the same interchange group share their stock count — e.g. a 2009 and a 2010 Corolla headlight-right
      both count toward the same total "in stock" number, since they fit the same range of vehicles.
    </p>

    {{-- AI suggestion button — available regardless of current state --}}
    <div class="mb-4">
      <button type="button" onclick="getAiSuggestions()" id="aiSuggestBtn"
        class="inline-flex items-center gap-2 bg-navy text-white font-display font-700 text-xs px-4 py-2.5 rounded-lg hover:bg-navy-light transition-colors">
        🤖 Get AI Interchange Suggestions
      </button>
      <p class="text-xs text-gray-400 font-body mt-1.5">Asks AI which other makes/models/years likely share this exact part, based on its engine/transmission code. You review and confirm — nothing is added automatically.</p>
      <div id="aiSuggestResults" class="mt-3 space-y-2"></div>
    </div>

    @if($interchangeGroup)
      {{-- ── Already in a group ─────────────────────────────────────── --}}
      <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-4">
        <div class="flex items-center justify-between mb-2">
          <div>
            <span class="font-mono font-700 text-navy text-sm">{{ $interchangeGroup->group_code }}</span>
            <span class="text-xs text-gray-400 ml-2">{{ $interchangeGroup->source === 'manual' ? 'Confirmed' : 'Auto-suggested' }}</span>
          </div>
          <form method="POST" action="{{ route('admin.interchange.parts.remove', $part->id) }}" onsubmit="return confirm('Remove this part from its interchange group? The group itself will remain.')">
            @csrf
            <button type="submit" class="text-xs font-body text-red-500 hover:text-red-700 underline">Remove from group</button>
          </form>
        </div>
        @if($aggregatedStock)
        <div class="font-display font-700 text-navy text-2xl mb-2">{{ $aggregatedStock['total'] }} <span class="text-sm font-body font-400 text-gray-500">total in stock across all compatible years</span></div>
        <div class="space-y-1">
          @foreach($aggregatedStock['lines'] as $line)
            <div class="flex justify-between text-xs font-body text-gray-600 {{ $line->id === $part->id ? 'font-700 text-navy' : '' }}">
              <span>{{ $line->part_code }} — {{ $line->brand }} {{ $line->model }} {{ $line->year_from }}@if($line->year_to != $line->year_from)–{{ $line->year_to }}@endif {{ $line->side !== 'N/A' ? '· '.$line->side : '' }}</span>
              <span>{{ $line->stock_qty }} unit{{ $line->stock_qty != 1 ? 's' : '' }}</span>
            </div>
          @endforeach
        </div>
        @endif
      </div>

      {{-- Add another compatible vehicle to this group --}}
      <details class="border border-gray-200 rounded-xl p-3">
        <summary class="text-sm font-body font-500 text-navy cursor-pointer">+ Add a compatible vehicle (research-based)</summary>
        <form method="POST" action="{{ route('admin.interchange.groups.add-vehicle', $interchangeGroup->id) }}" class="grid grid-cols-2 sm:grid-cols-5 gap-2 mt-3">
          @csrf
          <input type="hidden" name="part_id" value="{{ $part->id }}">
          <input type="text" name="make" required placeholder="Make" class="border border-gray-200 rounded-lg px-2.5 py-2 text-sm font-body focus:outline-none focus:border-yellow-400">
          <input type="text" name="model" required placeholder="Model" class="border border-gray-200 rounded-lg px-2.5 py-2 text-sm font-body focus:outline-none focus:border-yellow-400">
          <input type="number" name="year_from" required placeholder="From" min="1986" max="2027" class="border border-gray-200 rounded-lg px-2.5 py-2 text-sm font-body focus:outline-none focus:border-yellow-400">
          <input type="number" name="year_to" required placeholder="To" min="1986" max="2027" class="border border-gray-200 rounded-lg px-2.5 py-2 text-sm font-body focus:outline-none focus:border-yellow-400">
          <button type="submit" class="bg-navy text-white font-display font-700 text-xs rounded-lg hover:bg-navy-light transition-colors">+ Add</button>
        </form>
      </details>

    @elseif($heuristicSuggestion && count($heuristicSuggestion) > 0)
      {{-- ── No confirmed group yet, but a heuristic match exists ────── --}}
      <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
        <div class="text-xs font-body font-500 text-amber-700 uppercase tracking-wider mb-2">⚠ Suggested — not yet confirmed</div>
        <p class="text-xs text-gray-600 font-body mb-3">
          Other parts in inventory share this OEM code. Confirm this as a real interchange group to start aggregating stock counts.
        </p>
        <div class="space-y-1 mb-3">
          @foreach($heuristicSuggestion as $v)
            <div class="text-xs font-body text-gray-600">{{ $v->make }} {{ $v->model }} {{ $v->year_from }}@if($v->year_to != $v->year_from)–{{ $v->year_to }}@endif</div>
          @endforeach
        </div>
        <form method="POST" action="{{ route('admin.interchange.promote-heuristic') }}" class="flex gap-2">
          @csrf
          <input type="hidden" name="part_id" value="{{ $part->id }}">
          <input type="text" name="group_code" required placeholder="Group code, e.g. COROLLA-E170-HEADLIGHT-R"
            class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono uppercase focus:outline-none focus:border-yellow-400">
          <button type="submit" class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-lg hover:bg-yellow-500 transition-colors whitespace-nowrap">
            Confirm & Save
          </button>
        </form>
      </div>

    @else
      {{-- ── No group, no suggestion — manual create ──────────────────── --}}
      <p class="text-xs text-gray-400 font-body mb-3">This part isn't in an interchange group yet. Create one if you know it's compatible with other vehicles.</p>
      <form method="POST" action="{{ route('admin.interchange.groups.create') }}" class="flex gap-2">
        @csrf
        <input type="hidden" name="part_id" value="{{ $part->id }}">
        <input type="text" name="group_code" required placeholder="Group code, e.g. COROLLA-E170-HEADLIGHT-R"
          class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono uppercase focus:outline-none focus:border-yellow-400">
        <button type="submit" class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-lg hover:bg-yellow-500 transition-colors whitespace-nowrap">
          + Create Group
        </button>
      </form>
    @endif
  </div>
</div>
@push('scripts')
<script>
const CURRENT_ROOM_ID  = {{ $currentRoomId ?? 'null' }};
const CURRENT_SHELF_ID = {{ $part->storage_shelf_id ?? 'null' }};
const PART_ID = {{ $part->id }};
const HAS_GROUP = {{ $interchangeGroup ? 'true' : 'false' }};
const GROUP_ID = {{ $interchangeGroup->id ?? 'null' }};

async function getAiSuggestions() {
    const btn = document.getElementById('aiSuggestBtn');
    const box = document.getElementById('aiSuggestResults');
    btn.disabled = true;
    btn.textContent = '🤖 Thinking...';
    box.innerHTML = '';

    try {
        const res = await fetch(`{{ route('admin.interchange.ai-suggest') }}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: JSON.stringify({ part_id: PART_ID }),
        });
        const data = await res.json();

        if (data.error) {
            box.innerHTML = `<div class="text-xs text-red-600 font-body">${data.error}</div>`;
        } else if (!data.suggestions || data.suggestions.length === 0) {
            box.innerHTML = `<div class="text-xs text-gray-400 font-body">No confident suggestions found for this part.</div>`;
        } else {
            box.innerHTML = data.suggestions.map(s => `
                <div class="border border-gray-200 rounded-lg p-3 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-sm font-700 text-navy">${s.brand} ${s.model} ${s.year_from}${s.year_to !== s.year_from ? '–' + s.year_to : ''}
                            <span class="text-xs font-body font-500 ml-1 px-2 py-0.5 rounded-full ${s.confidence==='high'?'bg-green-100 text-green-700':s.confidence==='medium'?'bg-amber-100 text-amber-700':'bg-gray-100 text-gray-500'}">${s.confidence}</span>
                        </div>
                        <div class="text-xs text-gray-400 font-body mt-0.5">${s.reason}</div>
                    </div>
                    <button type="button" onclick='confirmAiSuggestion(${JSON.stringify(s).replace(/'/g,"&#39;")})'
                        class="text-xs font-body font-700 bg-gold text-navy px-3 py-2 rounded-lg hover:bg-yellow-500 transition-colors whitespace-nowrap">
                        + Confirm
                    </button>
                </div>`).join('');
        }
    } catch (e) {
        box.innerHTML = `<div class="text-xs text-red-600 font-body">Request failed — try again.</div>`;
    }

    btn.disabled = false;
    btn.textContent = '🤖 Get AI Interchange Suggestions';
}

async function confirmAiSuggestion(s) {
    if (HAS_GROUP) {
        // Add directly to the existing confirmed group
        const form = new FormData();
        form.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        form.append('part_id', PART_ID);
        form.append('make', s.brand);
        form.append('model', s.model);
        form.append('year_from', s.year_from);
        form.append('year_to', s.year_to);
        await fetch(`/admin/interchange/groups/${GROUP_ID}/add-vehicle`, { method: 'POST', body: form });
        location.reload();
    } else {
        const groupCode = prompt(`No confirmed interchange group exists yet for this part. Enter a group code to create one (e.g. COROLLA-E170-HEADLIGHT-R):`);
        if (!groupCode) return;
        const form = new FormData();
        form.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        form.append('part_id', PART_ID);
        form.append('group_code', groupCode);
        const res = await fetch(`{{ route('admin.interchange.groups.create') }}`, { method: 'POST', body: form });
        if (res.redirected || res.ok) {
            alert('Group created — now adding the suggested vehicle. Reloading...');
            location.reload();
        }
    }
}

document.getElementById('locationSelect').addEventListener('change', () => loadStoreRooms());
document.addEventListener('DOMContentLoaded', () => loadStoreRooms(true));

async function loadStoreRooms(isInitialLoad = false) {
    const loc = document.getElementById('locationSelect').value;
    const roomSelect = document.getElementById('storeRoomSelect');
    const binSelect   = document.getElementById('binSelect');

    if (!isInitialLoad) {
        binSelect.innerHTML = '<option value="">Select Store Room first</option>';
        document.getElementById('storageShelfIdInput').value = '';
        document.getElementById('binLocationInput').value = '';
    }

    if (!loc) {
        roomSelect.innerHTML = '<option value="">Select Location first</option>';
        return;
    }

    roomSelect.innerHTML = '<option value="">Loading...</option>';
    try {
        const res  = await fetch(`/admin/storage/rooms-for-location?location=${encodeURIComponent(loc)}`);
        const data = await res.json();
        if (!data.rooms || data.rooms.length === 0) {
            roomSelect.innerHTML = '<option value="">No store rooms set up for this location yet</option>';
            return;
        }
        roomSelect.innerHTML = '<option value="">Select Store Room</option>' +
            data.rooms.map(r => `<option value="${r.id}">${r.name} (${r.code})</option>`).join('');

        if (isInitialLoad && CURRENT_ROOM_ID) {
            roomSelect.value = CURRENT_ROOM_ID;
            await loadBinsForRoom(true);
        }
    } catch (e) {
        roomSelect.innerHTML = '<option value="">Could not load rooms</option>';
    }
}

document.getElementById('storeRoomSelect').addEventListener('change', () => loadBinsForRoom());

async function loadBinsForRoom(isInitialLoad = false) {
    const roomId = document.getElementById('storeRoomSelect').value;
    const binSelect = document.getElementById('binSelect');

    if (!isInitialLoad) {
        document.getElementById('storageShelfIdInput').value = '';
        document.getElementById('binLocationInput').value = '';
    }

    if (!roomId) {
        binSelect.innerHTML = '<option value="">Select Store Room first</option>';
        return;
    }

    binSelect.innerHTML = '<option value="">Loading...</option>';
    try {
        const res  = await fetch(`/admin/storage/shelves-for-room?room_id=${encodeURIComponent(roomId)}&keep_part_id={{ $part->id }}`);
        const data = await res.json();
        if (!data.shelves || data.shelves.length === 0) {
            binSelect.innerHTML = '<option value="">No bins set up for this room yet</option>';
            return;
        }
        binSelect.innerHTML = '<option value="">Select Bin (optional)</option>' +
            data.shelves.map(s => `<option value="${s.id}" data-code="${s.full_bin_code}" data-occupied="${s.occupied_by ? '1' : ''}">${s.full_bin_code}${s.occupied_by ? ' — occupied: ' + s.occupied_by : ''}</option>`).join('');

        if (isInitialLoad && CURRENT_SHELF_ID) {
            binSelect.value = CURRENT_SHELF_ID;
            binSelectPrevValue = CURRENT_SHELF_ID;
        }
    } catch (e) {
        binSelect.innerHTML = '<option value="">Could not load bins</option>';
    }
}

let binSelectPrevValue = '';
document.getElementById('binSelect').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const isOccupied = selected && selected.dataset.occupied === '1';

    if (isOccupied) {
        const ok = confirm(`This bin already has "${selected.textContent.split(' — occupied: ')[1]}" in it.\n\nAre you sure you want to put this item in the same bin? Only do this for genuinely grouped/related items.`);
        if (!ok) {
            this.value = binSelectPrevValue;
            return;
        }
        document.getElementById('confirmSharedBinInput').value = '1';
    } else {
        document.getElementById('confirmSharedBinInput').value = '';
    }

    binSelectPrevValue = this.value;
    const code = selected ? selected.dataset.code : '';
    document.getElementById('storageShelfIdInput').value = this.value;
    document.getElementById('binLocationInput').value = code || '';
});
</script>
@endpush
@endsection
