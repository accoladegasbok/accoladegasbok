{{-- FILE: resources/views/admin/assets/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Assets & Equipment')
@section('page-title','Assets & Equipment Register')
@section('page-sub','Office equipment, machinery, tools — internal use only, never for sale')

@section('header-actions')
<a href="{{ route('admin.assets.create') }}" class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition-colors">
  + Add Asset
</a>
@endsection

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-3 gap-4 mb-5">
  <div class="stat-card"><div class="text-2xl font-display font-800 text-navy">{{ $counts['total'] }}</div><div class="text-xs text-gray-400 uppercase tracking-wider mt-1">Total Assets</div></div>
  <div class="stat-card"><div class="text-2xl font-display font-800 text-amber-600">{{ $counts['needs_repair'] }}</div><div class="text-xs text-gray-400 uppercase tracking-wider mt-1">Needs Repair</div></div>
  <div class="stat-card"><div class="text-2xl font-display font-800 text-red-600">{{ $counts['out_of_service'] }}</div><div class="text-xs text-gray-400 uppercase tracking-wider mt-1">Out of Service</div></div>
</div>

<form method="GET" class="grid grid-cols-2 sm:grid-cols-5 gap-2 mb-4">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, tag, serial..."
    class="sm:col-span-2 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-gold">
  <select name="category" onchange="this.form.submit()" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
    <option value="">All Categories</option>
    @foreach($categories as $c)<option value="{{ $c }}" {{ request('category')===$c?'selected':'' }}>{{ $c }}</option>@endforeach
  </select>
  <select name="status" onchange="this.form.submit()" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
    <option value="">All Statuses</option>
    @foreach($statuses as $s)<option value="{{ $s }}" {{ request('status')===$s?'selected':'' }}>{{ $s }}</option>@endforeach
  </select>
  <select name="location" onchange="this.form.submit()" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white">
    <option value="">All Locations</option>
    @foreach($locations as $l)<option value="{{ $l }}" {{ request('location')===$l?'selected':'' }}>{{ $l }}</option>@endforeach
  </select>
</form>

<div class="stat-card overflow-hidden p-0">
  <table class="w-full text-sm font-body">
    <thead>
      <tr class="border-b border-gray-100 bg-gray-50">
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Tag</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Name</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Category</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Location</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
        <th class="px-4 py-3"></th>
      </tr>
    </thead>
    <tbody>
      @forelse($assets as $a)
      <tr class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $a->asset_tag }}</td>
        <td class="px-4 py-3 font-700 text-navy">{{ $a->name }}</td>
        <td class="px-4 py-3 text-gray-500">{{ $a->category }}</td>
        <td class="px-4 py-3 text-xs text-gray-400">{{ $a->location }}</td>
        <td class="px-4 py-3">
          <span class="badge
            @if($a->status==='In Service') badge-green
            @elseif($a->status==='Serviceable') badge-blue
            @elseif($a->status==='Needs Repair') badge-amber
            @else badge-red @endif">
            {{ $a->status }}
          </span>
        </td>
        <td class="px-4 py-3 text-right">
          <a href="{{ route('admin.assets.show', $a->id) }}" class="text-xs font-body border border-gray-200 text-gray-500 hover:border-navy hover:text-navy px-3 py-1.5 rounded-lg transition-colors">View</a>
        </td>
      </tr>
      @empty
      <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">No assets registered yet.</td></tr>
      @endforelse
    </tbody>
  </table>
  @if($assets->hasPages())
  <div class="px-4 py-4 border-t border-gray-100">{{ $assets->links() }}</div>
  @endif
</div>

@endsection
