{{-- FILE: resources/views/admin/training/mine.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'My Training')
@section('page-title', 'My Training')
@section('page-sub', 'Finish assigned courses before the due date so you can keep clocking in')

@section('content')
@if($state === 'red')<div class="bg-red-600 text-white rounded-2xl px-5 py-3 mb-4 font-display font-700">Training overdue - you cannot clock in until it is completed.</div>@endif
<div class="space-y-3">
@forelse($rows as $a)
  @php
    $due = \Carbon\Carbon::parse($a->due_date); $days = now()->startOfDay()->diffInDays($due, false);
    if ($a->status === 'completed') { $cls = 'border-green-300 bg-white'; $tag = ['Completed', 'bg-green-100 text-green-800']; }
    elseif ($days < 0) { $cls = 'border-red-500 bg-red-50'; $tag = ['OVERDUE by '.abs($days).' day'.(abs($days)==1?'':'s'), 'bg-red-600 text-white']; }
    elseif ($days <= 7) { $cls = 'border-amber-400 bg-amber-50'; $tag = ['Due in '.$days.' day'.($days==1?'':'s'), 'bg-amber-200 text-amber-900']; }
    else { $cls = 'border-gray-200 bg-white'; $tag = ['Due '.$due->format('d M Y'), 'bg-gray-100 text-gray-700']; }
  @endphp
  <div class="rounded-2xl border-2 {{ $cls }} p-4 flex flex-wrap items-center justify-between gap-3">
    <div>
      <div class="font-display font-700 text-navy">{{ $a->title }} @if($a->is_retraining)<span class="text-[11px] text-purple-700 font-body">(retraining)</span>@endif</div>
      <div class="text-xs text-gray-500 font-body">{{ $a->area }} · {{ ucfirst($a->provider_type) }}{{ $a->provider_name ? ' - '.$a->provider_name : '' }}{{ $a->duration_minutes ? ' · '.$a->duration_minutes.' min' : '' }}@if($a->recurrence_days) · repeats every {{ $a->recurrence_days }} days @endif</div>
    </div>
    <div class="flex items-center gap-3">
      <span class="text-[11px] font-700 px-2.5 py-1 rounded-full {{ $tag[1] }}">{{ $tag[0] }}</span>
      @if($a->status === 'completed')<a href="{{ route('admin.training.certificate', $a->id) }}" class="text-xs underline text-blue-700">Certificate{{ $a->score !== null ? ' ('.$a->score.'%)' : '' }}</a>
      @else<a href="{{ route('admin.training.watch', $a->id) }}" class="bg-navy text-white font-display font-700 text-xs px-4 py-2 rounded-xl">{{ $a->status === 'in_progress' ? 'Continue' : 'Start' }}</a>@endif
    </div>
  </div>
@empty
  <div class="text-sm text-gray-400 font-body">Nothing assigned to you.</div>
@endforelse
</div>
@endsection
