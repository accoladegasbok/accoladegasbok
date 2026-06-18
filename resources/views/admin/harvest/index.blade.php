{{-- FILE: resources/views/admin/harvest/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Harvest History')
@section('page-title','Harvest History')
@section('page-sub','All donor vehicles that have been stripped and listed')

@section('header-actions')
<a href="{{ route('admin.harvest.create') }}"
   class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl tracking-wide hover:bg-yellow-500 transition-colors flex items-center gap-1.5">
  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
  New Harvest
</a>
@endsection

@section('content')

{{-- Summary strip --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
  @php
    $all        = $sessions->total();
    $completed  = $sessions->getCollection()->where('status','completed')->count();
    $inProgress = $sessions->getCollection()->where('status','in_progress')->count();
    $totalParts = $sessions->getCollection()->sum('parts_listed');
  @endphp
  <div class="stat-card text-center">
    <div class="font-display font-700 text-navy text-2xl">{{ $all }}</div>
    <div class="text-xs text-gray-400 font-body mt-1">Total Sessions</div>
  </div>
  <div class="stat-card text-center bg-green-50">
    <div class="font-display font-700 text-green-700 text-2xl">{{ $completed }}</div>
    <div class="text-xs text-gray-400 font-body mt-1">Completed</div>
  </div>
  <div class="stat-card text-center bg-amber-50">
    <div class="font-display font-700 text-amber-700 text-2xl">{{ $inProgress }}</div>
    <div class="text-xs text-gray-400 font-body mt-1">In Progress</div>
  </div>
  <div class="stat-card text-center bg-blue-50">
    <div class="font-display font-700 text-blue-700 text-2xl">{{ $totalParts }}</div>
    <div class="text-xs text-gray-400 font-body mt-1">Parts Listed (this page)</div>
  </div>
</div>

{{-- Sessions table --}}
<div class="stat-card overflow-hidden p-0">
  <div class="overflow-x-auto">
    <table class="w-full text-sm font-body">
      <thead>
        <tr class="border-b border-gray-100 bg-gray-50">
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Vehicle</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">VIN</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Location</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Staff</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Parts</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
          <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Date</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody>
        @forelse($sessions as $s)
        <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">

          {{-- Vehicle --}}
          <td class="px-4 py-3">
            <div class="font-500 text-navy">{{ $s->year }} {{ $s->make }} {{ $s->model }}</div>
            @if($s->dv_id)
              <div class="text-xs text-gray-400 mt-0.5">Donor ID #{{ $s->dv_id }}</div>
            @endif
          </td>

          {{-- VIN --}}
          <td class="px-4 py-3">
            <span class="font-mono text-xs text-gray-600">{{ $s->vin }}</span>
          </td>

          {{-- Location --}}
          <td class="px-4 py-3 text-xs text-gray-600">{{ $s->location }}</td>

          {{-- Staff --}}
          <td class="px-4 py-3 text-xs text-gray-600">{{ $s->staff_name }}</td>

          {{-- Parts --}}
          <td class="px-4 py-3">
            <div class="font-display font-700 text-navy text-base">{{ $s->parts_listed }}</div>
            <div class="text-xs text-gray-400">of {{ $s->parts_harvested }} ticked</div>
          </td>

          {{-- Status --}}
          <td class="px-4 py-3">
            <span class="badge
              @if($s->status==='completed') badge-green
              @elseif($s->status==='in_progress') badge-amber
              @else badge-gray @endif">
              {{ str_replace('_',' ', $s->status) }}
            </span>
          </td>

          {{-- Date --}}
          <td class="px-4 py-3 text-xs text-gray-400">
            <div>{{ \Carbon\Carbon::parse($s->created_at)->format('d M Y') }}</div>
            <div>{{ \Carbon\Carbon::parse($s->created_at)->format('H:i') }}</div>
          </td>

          {{-- Actions --}}
          <td class="px-4 py-3">
            <div class="flex gap-2">
              @if($s->status === 'in_progress')
                <a href="{{ route('admin.harvest.checklist', $s->id) }}"
                   class="text-xs font-body bg-gold text-navy px-3 py-1.5 rounded-lg hover:bg-yellow-500 transition-colors font-500">
                  Continue
                </a>
              @else
                <a href="{{ route('admin.harvest.complete', $s->id) }}"
                   class="text-xs font-body border border-gray-200 text-gray-500 hover:border-navy hover:text-navy px-3 py-1.5 rounded-lg transition-colors">
                  View
                </a>
              @endif
            </div>
          </td>

        </tr>
        @empty
        <tr>
          <td colspan="8" class="px-4 py-16 text-center">
            <div class="text-gray-300 text-5xl mb-3">🚗</div>
            <div class="text-gray-500 font-body text-sm mb-4">No harvest sessions yet.</div>
            <a href="{{ route('admin.harvest.create') }}"
               class="inline-block bg-navy text-white font-display font-700 text-xs px-5 py-2.5 rounded-xl tracking-wide hover:bg-navy-light transition-colors">
              Register Your First Donor Vehicle
            </a>
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($sessions->hasPages())
  <div class="px-4 py-4 border-t border-gray-100">
    {{ $sessions->links() }}
  </div>
  @endif
</div>

@endsection
