{{-- FILE: resources/views/admin/staff/edit.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Edit Staff')
@section('page-title','Edit Staff Member')
@section('page-sub', $member->name . ' — ' . ucfirst($member->role))

@section('content')
<div class="max-w-xl">
  <form method="POST" action="{{ route('admin.staff.update', $member->id) }}">
    @csrf @method('PUT')

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
          <input type="text" name="name" value="{{ old('name', $member->name) }}" required
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Email Address *</label>
          <input type="email" name="email" value="{{ old('email', $member->email) }}" required
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>

        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Phone</label>
          <input type="text" name="phone" value="{{ old('phone', $member->phone) }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Role *</label>
            <select name="role" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
              @foreach($roles as $r)
                <option value="{{ $r }}" {{ old('role',$member->role)===$r?'selected':'' }}>{{ ucfirst($r) }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Location *</label>
            <select name="location" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
              @foreach($locations as $l)
                <option value="{{ $l }}" {{ old('location',$member->location)===$l?'selected':'' }}>{{ $l }}</option>
              @endforeach
            </select>
          </div>
        </div>

        {{-- Password change (optional) --}}
        <div class="border-t border-gray-100 pt-4">
          <div class="text-xs font-body text-gray-400 mb-3">Leave password fields blank to keep the current password.</div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">New Password</label>
              <input type="password" name="password" minlength="8"
                class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400"
                placeholder="Leave blank to keep">
            </div>
            <div>
              <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Confirm New Password</label>
              <input type="password" name="password_confirmation" minlength="8"
                class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
            </div>
          </div>
        </div>

      </div>
    </div>

    @if(\Illuminate\Support\Facades\Session::get('staff_role') === 'admin')
    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">Discount Allowance Caps</h2>
      <p class="text-xs text-gray-400 mb-4">Admin-only setting. Limits how much discount this staff member can apply on an invoice before a warning is shown.</p>
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Fixed Cap (USD)</label>
          <input type="number" step="0.01" min="0" name="discount_cap_fixed" value="{{ old('discount_cap_fixed', $member->discount_cap_fixed) }}"
            placeholder="e.g. 50.00"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Percentage Cap (%)</label>
          <input type="number" step="0.01" min="0" max="100" name="discount_cap_percent" value="{{ old('discount_cap_percent', $member->discount_cap_percent) }}"
            placeholder="e.g. 10"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
      </div>
      <p class="text-xs text-gray-400 mt-3">Leave blank for no cap on that type. Both caps apply simultaneously — whichever is reached first triggers a warning.</p>
    </div>
    @endif

    {{-- Account info --}}
    <div class="stat-card mb-5 bg-gray-50 text-xs font-body space-y-2">
      <div class="flex justify-between"><span class="text-gray-400">Account created</span><span>{{ \Carbon\Carbon::parse($member->created_at)->format('d M Y') }}</span></div>
      <div class="flex justify-between"><span class="text-gray-400">Last login</span><span>{{ $member->last_login_at ? \Carbon\Carbon::parse($member->last_login_at)->format('d M Y H:i') : 'Never' }}</span></div>
      <div class="flex justify-between"><span class="text-gray-400">Status</span>
        <span class="badge {{ $member->is_active ? 'badge-green' : 'badge-gray' }}">{{ $member->is_active ? 'Active' : 'Inactive' }}</span>
      </div>
    </div>

    <div class="flex gap-3">
      <button type="submit" class="flex-1 bg-gold text-navy font-display font-700 text-sm py-3.5 rounded-xl tracking-wide hover:bg-yellow-500 transition-colors">
        Save Changes
      </button>
      <a href="{{ route('admin.staff.index') }}" class="border border-gray-200 text-gray-500 font-body font-500 text-sm px-5 py-3.5 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
    </div>
  </form>
</div>
@endsection
