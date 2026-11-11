{{-- FILE: resources/views/admin/eotm/rate.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Rate Staff')
@section('page-title', 'Rate Staff - ' . \Carbon\Carbon::parse($ym.'-01')->format('F Y'))
@section('page-sub', 'Four things the system cannot measure. 1 = poor, 3 = good, 5 = outstanding. Leave blank if you cannot judge')

@section('header-actions')
  <a href="{{ route('admin.eotm.index', ['ym' => $ym]) }}" class="bg-white border border-gray-200 text-navy font-display font-700 text-xs px-3 py-2 rounded-xl">Back</a>
@endsection

@section('content')
@if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm font-body">{{ session('error') }}</div>@endif
<form method="POST" action="{{ route('admin.eotm.rate.save') }}" class="text-sm font-body">
  @csrf <input type="hidden" name="ym" value="{{ $ym }}">
  <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-x-auto">
    <table class="w-full">
      <thead class="bg-gray-50 text-[11px] uppercase text-gray-500"><tr><th class="text-left px-4 py-2">Staff</th><th>Customer satisfaction</th><th>Teamwork</th><th>Professional communication</th><th>Problem-solving</th><th class="text-left">Comment</th></tr></thead>
      <tbody>
      @foreach($staff as $s)
        @php $m = $mine->get($s->id); @endphp
        <tr class="border-t border-gray-100 text-center">
          <td class="px-4 py-2 text-left"><b class="text-navy">{{ $s->name }}</b><div class="text-[11px] text-gray-400">{{ $s->location }} · {{ $s->role }}</div></td>
          @foreach(['customer_satisfaction','teamwork','communication','problem_solving'] as $k)
          <td><select name="r[{{ $s->id }}][{{ $k }}]" class="border border-gray-200 rounded px-1 py-1 bg-white">
            <option value="">–</option>@for($i=1;$i<=5;$i++)<option value="{{ $i }}" {{ ($m->$k ?? null) == $i ? 'selected' : '' }}>{{ $i }}</option>@endfor</select></td>
          @endforeach
          <td class="px-2"><input name="r[{{ $s->id }}][comment]" value="{{ $m->comment ?? '' }}" maxlength="255" class="w-48 border border-gray-200 rounded px-2 py-1"></td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <button class="mt-4 bg-gold text-navy font-display font-700 px-6 py-2.5 rounded-xl">Save ratings</button>
  <p class="text-[11px] text-gray-400 mt-2">Your ratings are private. If several supervisors rate the same person, the average is used. Ratings close when the top 3 are chosen.</p>
</form>
@endsection
