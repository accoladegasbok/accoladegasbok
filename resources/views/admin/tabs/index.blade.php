{{-- FILE: resources/views/admin/tabs/index.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Open Tabs')
@section('page-title','Open Tabs')
@section('page-sub','Running tabs for customers visiting multiple times in one day (e.g. workshop repairs)')

@section('header-actions')
<a href="{{ route('admin.tabs.create') }}" class="bg-gold text-navy font-display font-700 text-xs px-4 py-2 rounded-xl hover:bg-yellow-400 transition-colors">
  + Open New Tab
</a>
@endsection

@section('content')

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>
@endif

<div class="flex gap-2 mb-4">
  @foreach(['open' => 'Open', 'closed' => 'Closed', 'cancelled' => 'Cancelled', '' => 'All'] as $val => $lbl)
  <a href="{{ route('admin.tabs.index', ['status' => $val]) }}"
     class="text-xs font-body font-500 px-3 py-1.5 rounded-full border {{ $status===$val ? 'bg-navy text-white border-navy' : 'bg-white text-gray-600 border-gray-200' }}">
    {{ $lbl }}
  </a>
  @endforeach
</div>

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
  <table class="w-full text-sm font-body">
    <thead>
      <tr class="bg-gray-50 border-b border-gray-200">
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Tab #</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Customer</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Location</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Status</th>
        <th class="text-left px-4 py-3 text-xs font-500 text-gray-400 uppercase tracking-wider">Opened</th>
        <th class="px-4 py-3"></th>
      </tr>
    </thead>
    <tbody>
      @forelse($tabs as $t)
      <tr class="border-b border-gray-50 hover:bg-gray-50">
        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $t->tab_no }}</td>
        <td class="px-4 py-3">
          <div class="font-700 text-navy">{{ $t->customer_name }}</div>
          <div class="text-xs text-gray-400">{{ $t->customer_phone }}</div>
        </td>
        <td class="px-4 py-3 text-gray-500 text-xs">{{ $t->location }}</td>
        <td class="px-4 py-3">
          <span class="badge {{ $t->status==='open' ? 'badge-amber' : ($t->status==='closed' ? 'badge-green' : 'badge-gray') }}">{{ ucfirst($t->status) }}</span>
        </td>
        <td class="px-4 py-3 text-xs text-gray-400">{{ \Carbon\Carbon::parse($t->created_at)->format('d M, H:i') }}</td>
        <td class="px-4 py-3 text-right">
          <a href="{{ route('admin.tabs.show', $t->id) }}" class="text-xs font-body text-gold hover:text-yellow-600">View →</a>
        </td>
      </tr>
      @empty
      <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400 text-sm">No tabs.</td></tr>
      @endforelse
    </tbody>
  </table>
  @if($tabs->hasPages())<div class="px-4 py-4 border-t border-gray-100">{{ $tabs->links() }}</div>@endif
</div>

@endsection
