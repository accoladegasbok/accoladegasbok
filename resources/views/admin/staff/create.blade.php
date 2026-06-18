{{-- FILE: resources/views/admin/staff/create.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Add Staff')
@section('page-title','Add Staff Member')
@section('page-sub','Create a new admin panel account')

@section('content')
<div class="max-w-xl">
  <form method="POST" action="{{ route('admin.staff.store') }}">
    @csrf

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 text-sm text-red-700 font-body">
      @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Staff Details</h2>
      <div class="space-y-4">

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Full Name *</label>
          <input type="text" name="name" value="{{ old('name') }}" required
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="e.g. Akolade Adedoyin">
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Email Address *</label>
          <input type="email" name="email" value="{{ old('email') }}" required
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="staff@autozenithparts.com">
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Phone</label>
          <input type="text" name="phone" value="{{ old('phone') }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="+234 806 742 2777">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Role *</label>
            <select name="role" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
              @foreach($roles as $r)
                <option value="{{ $r }}" {{ old('role')===$r?'selected':'' }}>{{ ucfirst($r) }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Location *</label>
            <select name="location" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
              @foreach($locations as $l)
                <option value="{{ $l }}" {{ old('location')===$l?'selected':'' }}>{{ $l }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Password *</label>
          <input type="password" name="password" required minlength="8"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
            placeholder="Minimum 8 characters">
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Confirm Password *</label>
          <input type="password" name="password_confirmation" required minlength="8"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>

      </div>
    </div>

    <div class="flex gap-3">
      <button type="submit" class="flex-1 bg-gold text-navy font-display font-700 text-sm py-3.5 rounded-xl tracking-wide hover:bg-yellow-500 transition-colors">
        Create Staff Account
      </button>
      <a href="{{ route('admin.staff.index') }}" class="border border-gray-200 text-gray-500 font-body font-500 text-sm px-5 py-3.5 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
    </div>
  </form>
</div>
@endsection
