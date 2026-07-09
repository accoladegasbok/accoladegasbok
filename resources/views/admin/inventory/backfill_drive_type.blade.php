{{-- FILE: resources/views/admin/inventory/backfill-drive-type.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Backfill Drive Type')
@section('page-title', 'Backfill Drive Type — One-Time Cleanup')
@section('page-sub', 'Transmission / combined engine-gear parts saved before drive_type existed as a field. Fill in what you know; leave blank to do later.')

@section('content')

<div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-5 text-xs font-body text-blue-700">
  This tool only shows parts still missing Drive Type. Fill in as many as you know now, click "Save All Filled Rows," then reload — anything left blank stays here for next time. Nothing is skipped or lost.
</div>

@if($parts->isEmpty())
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8 text-center text-gray-400 font-body text-sm">
  ✓ Nothing left — every Transmission / combined engine-gear part has Drive Type set.
</div>
@else

<form method="POST" action="{{ route('admin.inventory.backfill-drive-type.save') }}">
@csrf

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
  <table class="w-full text-sm">
    <thead class="bg-navy text-white">
      <tr>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Part Code</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Vehicle</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Part Name</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Gearbox Code / Pins</th>
        <th class="px-4 py-3 text-left text-xs font-display uppercase tracking-wide">Drive Type</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-gray-100">
      @foreach($parts as $p)
      <tr class="hover:bg-gray-50">
        <td class="px-4 py-3 font-mono text-xs text-navy">{{ $p->part_code }}</td>
        <td class="px-4 py-3 text-xs text-gray-600">{{ $p->brand }} {{ $p->model }} {{ $p->year_from }}@if($p->year_to != $p->year_from)–{{ $p->year_to }}@endif</td>
        <td class="px-4 py-3 text-xs text-gray-800">{{ $p->part_name }}</td>
        <td class="px-4 py-3 text-xs font-mono text-gray-500">
          {{ $p->transmission_code_oem ?: '—' }}{{ $p->pin_count ? ' ('.$p->pin_count.'-pin)' : '' }}
          @if($p->gear_alias)<div class="text-gray-400">{{ $p->gear_alias }}</div>@endif
        </td>
        <td class="px-4 py-3">
          <select name="drive_type[{{ $p->id }}]" class="border border-gray-200 rounded-lg px-2 py-1.5 text-xs font-body bg-white focus:outline-none focus:border-gold">
            <option value="">— Skip for now —</option>
            <option value="2WD">2WD</option>
            <option value="4WD">4WD</option>
            <option value="AWD">AWD</option>
            <option value="RWD">RWD</option>
            <option value="FWD">FWD</option>
            <option value="4x2">4x2</option>
            <option value="4x4">4x4</option>
          </select>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>

<div class="flex justify-between items-center mt-5 pb-8">
  <span class="text-xs text-gray-400 font-body">{{ $parts->count() }} part(s) remaining</span>
  <button type="submit" class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-8 py-3 rounded-xl transition-colors shadow-lg">
    Save All Filled Rows
  </button>
</div>

</form>
@endif

@endsection
