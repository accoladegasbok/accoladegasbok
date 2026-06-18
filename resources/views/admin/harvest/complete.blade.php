{{-- FILE: resources/views/admin/harvest/complete.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Harvest Complete')
@section('page-title', 'Harvest Complete')

@section('content')
<div class="max-w-3xl">

  {{-- Success header --}}
  <div class="stat-card mb-6 text-center py-8">
    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 border-2 border-green-200">
      <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
      </svg>
    </div>
    <h2 class="font-display font-700 text-navy text-2xl tracking-wide mb-1">Harvest Complete!</h2>
    <p class="text-gray-500 font-body text-sm">
      <strong>{{ $session->parts_listed }}</strong> parts from the
      <strong>{{ $session->year }} {{ $session->make }} {{ $session->model }}</strong>
      are now live in the customer inventory.
    </p>

    <div class="flex justify-center gap-4 mt-4 text-xs font-body text-gray-400">
      <span>VIN: <span class="font-mono text-gray-600">{{ $session->vin }}</span></span>
      <span>By: {{ $session->staff_name }}</span>
      <span>{{ $session->location }}</span>
    </div>

    <div class="grid grid-cols-3 gap-3 mt-6 max-w-sm mx-auto">
      <div class="bg-gray-50 rounded-xl p-3">
        <div class="font-display font-700 text-navy text-2xl">{{ $session->parts_harvested }}</div>
        <div class="text-xs text-gray-400 font-body">Ticked</div>
      </div>
      <div class="bg-green-50 rounded-xl p-3">
        <div class="font-display font-700 text-green-700 text-2xl">{{ $session->parts_listed }}</div>
        <div class="text-xs text-gray-400 font-body">Listed</div>
      </div>
      <div class="bg-gray-50 rounded-xl p-3">
        <div class="font-display font-700 text-navy text-2xl">₦{{ number_format($newParts->sum('price_usd') * 1600) }}</div>
        <div class="text-xs text-gray-400 font-body">Est. value</div>
      </div>
    </div>
  </div>

  {{-- New parts list --}}
  <div class="stat-card mb-6">
    <h3 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Parts Now in Inventory</h3>
    <div class="space-y-2">
      @foreach($newParts->groupBy('part_category') as $cat => $catParts)
      <div class="text-xs font-body font-500 text-gray-400 uppercase tracking-wider pt-2 first:pt-0">{{ $cat }}</div>
      @foreach($catParts as $part)
      <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
        <div class="flex items-center gap-3">
          <span class="font-mono text-xs text-gray-400 w-20 flex-shrink-0">{{ $part->part_code }}</span>
          <span class="font-body text-sm text-navy font-500">{{ $part->part_name }}</span>
          @if($part->side && $part->side !== 'N/A')
            <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">{{ $part->side }}</span>
          @endif
        </div>
        <div class="flex items-center gap-3">
          <span class="badge {{ $part->condition_grade === 'A' ? 'badge-green' : ($part->condition_grade === 'B' ? 'badge-blue' : 'badge-amber') }}">
            Grade {{ $part->condition_grade }}
          </span>
          <span class="font-display font-700 text-navy text-sm">${{ number_format($part->price_usd, 2) }}</span>
        </div>
      </div>
      @endforeach
      @endforeach
    </div>
  </div>

  {{-- Actions --}}
  <div class="flex gap-3">
    <a href="{{ route('admin.harvest.create') }}"
      class="flex-1 bg-gold text-navy font-display font-700 text-sm py-3.5 rounded-xl text-center tracking-wide hover:bg-yellow-500 transition-colors">
      Harvest Another Vehicle
    </a>
    <a href="{{ route('admin.dashboard') }}"
      class="border border-gray-200 text-gray-600 font-body font-500 text-sm px-5 py-3.5 rounded-xl hover:bg-gray-50 transition-colors">
      Dashboard
    </a>
    <a href="{{ route('admin.inventory.index') }}"
      class="border border-navy text-navy font-body font-500 text-sm px-5 py-3.5 rounded-xl hover:bg-navy hover:text-white transition-colors">
      View Inventory
    </a>
  </div>
</div>
@endsection
