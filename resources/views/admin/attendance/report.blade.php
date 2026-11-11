{{-- FILE: resources/views/admin/attendance/report.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Attendance')
@section('page-title', 'Attendance')
@section('page-sub', 'Who is in, who is late and who is absent - recorded from the phone, not a WhatsApp photo')

@section('header-actions')
  @if($isMgr)<a href="{{ route('admin.attendance.settings') }}" class="bg-white border border-gray-200 text-navy font-display font-700 text-xs px-3 py-2 rounded-xl hover:border-navy">Sites &amp; Phones</a>@endif
@endsection

@section('content')
@if($errors->any())<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ $errors->first() }}</div>@endif
@php
  $badge = ['present'=>['Present','bg-green-100 text-green-800'],'late'=>['Late','bg-amber-100 text-amber-800'],'absent'=>['Absent','bg-red-100 text-red-700'],
            'blocked'=>['Absent - training overdue','bg-red-600 text-white'],'excused'=>['Excused','bg-blue-100 text-blue-800'],'rest'=>['Rest day','bg-gray-100 text-gray-500'],
            'pending'=>['Not in yet','bg-gray-100 text-gray-600'],'future'=>['—','bg-gray-50 text-gray-400']];
  $tz = 'Africa/Lagos';
  $cnt = $rows->groupBy('state')->map->count();
@endphp

<form method="GET" class="flex flex-wrap items-end gap-3 mb-4">
  <div><label class="block text-[10px] text-gray-400 uppercase mb-1">Day</label><input type="date" name="date" value="{{ $date }}" class="border border-gray-200 rounded-lg px-3 py-2 text-sm"></div>
  @if($isMgr)
  <div><label class="block text-[10px] text-gray-400 uppercase mb-1">Location</label>
    <select name="location" class="border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white"><option value="">All</option>@foreach($locations as $l)<option value="{{ $l }}" {{ $loc===$l?'selected':'' }}>{{ $l }}</option>@endforeach</select></div>
  @endif
  <div><label class="block text-[10px] text-gray-400 uppercase mb-1">Month summary</label><input type="month" name="ym" value="{{ $ym }}" class="border border-gray-200 rounded-lg px-3 py-2 text-sm"></div>
  <button class="bg-navy text-white font-display font-700 text-sm px-4 py-2 rounded-lg">Show</button>
</form>

<div class="flex flex-wrap gap-2 mb-4 text-xs font-body">
  @foreach(['present','late','absent','blocked','excused'] as $k)
    <span class="px-3 py-1 rounded-full {{ $badge[$k][1] }}">{{ $badge[$k][0] }}: <b>{{ $cnt[$k] ?? 0 }}</b></span>
  @endforeach
</div>

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-x-auto mb-8">
  <table class="w-full text-sm font-body">
    <thead class="bg-gray-50 text-[11px] uppercase text-gray-500"><tr>
      <th class="text-left px-4 py-2">Staff</th><th class="text-left px-2">Status</th><th class="text-left px-2">In</th><th class="text-left px-2">Out</th>
      <th class="text-left px-2">Away from site</th><th class="text-left px-2">Verified</th><th class="text-left px-2">Training</th><th class="px-2">Photo</th><th class="px-2"></th></tr></thead>
    <tbody>
    @forelse($rows as $x)
      @php $rec = $x->rec; $b = $badge[$x->state]; @endphp
      <tr class="border-t border-gray-100">
        <td class="px-4 py-2"><div class="font-700 text-navy">{{ $x->staff->name }}</div><div class="text-[11px] text-gray-400">{{ $x->staff->location }} · {{ $x->staff->role }}</div></td>
        <td class="px-2"><span class="px-2 py-0.5 rounded-full text-[11px] font-700 {{ $b[1] }}">{{ $b[0] }}</span>@if($x->state==='late')<div class="text-[11px] text-amber-700">{{ $rec->late_minutes }} min</div>@endif</td>
        <td class="px-2">{{ $rec && $rec->clock_in_at ? \Carbon\Carbon::parse($rec->clock_in_at)->setTimezone($tz)->format('g:i A') : '—' }}</td>
        <td class="px-2">{{ $rec && $rec->clock_out_at ? \Carbon\Carbon::parse($rec->clock_out_at)->setTimezone($tz)->format('g:i A') : '—' }}</td>
        <td class="px-2">{{ $rec && $rec->in_distance_m !== null ? $rec->in_distance_m . ' m' : '—' }}</td>
        <td class="px-2 text-xs">{{ $rec && $rec->clock_in_at ? ($rec->verified_by==='passkey' ? 'Phone fingerprint' : 'Selfie only') : '—' }}</td>
        <td class="px-2"><span class="inline-block w-3 h-3 rounded-full {{ ['red'=>'bg-red-600','amber'=>'bg-amber-400','green'=>'bg-green-500'][$x->training] }}"></span></td>
        <td class="px-2 text-center">@if($rec && $rec->in_photo)<a target="_blank" href="{{ route('admin.attendance.photo', [$rec->id, 'in']) }}" class="text-blue-700 underline text-xs">In</a>@endif @if($rec && $rec->out_photo)<a target="_blank" href="{{ route('admin.attendance.photo', [$rec->id, 'out']) }}" class="text-blue-700 underline text-xs ml-1">Out</a>@endif</td>
        <td class="px-2 py-1">
          @if(in_array($x->state, ['absent','blocked']))
          <form method="POST" action="{{ route('admin.attendance.excuse') }}" class="flex gap-1">@csrf
            <input type="hidden" name="staff_id" value="{{ $x->staff->id }}"><input type="hidden" name="date" value="{{ $date }}">
            <input name="note" placeholder="Reason" class="border border-gray-200 rounded px-2 py-1 text-xs w-24">
            <button class="text-xs bg-navy text-white px-2 rounded">Excuse</button></form>
          @elseif($rec && $rec->note)<span class="text-[11px] text-gray-400">{{ $rec->note }}</span>@endif
        </td>
      </tr>
    @empty
      <tr><td colspan="9" class="px-4 py-6 text-center text-gray-400">No staff found.</td></tr>
    @endforelse
    </tbody>
  </table>
</div>

<h2 class="font-display font-700 text-navy text-lg mb-2">Month summary - {{ \Carbon\Carbon::parse($ym.'-01')->format('F Y') }}</h2>
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-x-auto">
  <table class="w-full text-sm font-body">
    <thead class="bg-gray-50 text-[11px] uppercase text-gray-500"><tr><th class="text-left px-4 py-2">Staff</th><th>Working days</th><th>Present</th><th>Late</th><th>Absent</th><th>Excused</th><th>Attendance</th><th>Punctuality</th></tr></thead>
    <tbody>
    @foreach($summary as $s)
      <tr class="border-t border-gray-100 text-center">
        <td class="px-4 py-2 text-left font-700 text-navy">{{ $s->staff->name }}</td><td>{{ $s->m['working_days'] }}</td><td>{{ $s->m['present'] }}</td>
        <td class="{{ $s->m['late'] ? 'text-amber-700 font-700' : '' }}">{{ $s->m['late'] }}</td><td class="{{ $s->m['absent'] ? 'text-red-600 font-700' : '' }}">{{ $s->m['absent'] }}</td>
        <td>{{ $s->m['excused'] }}</td><td>{{ $s->m['attendance'] }}%</td><td>{{ $s->m['punctuality'] }}%</td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endsection
