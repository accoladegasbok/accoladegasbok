{{-- FILE: resources/views/admin/staff/edit.blade.php --}}
@extends('admin.layouts.admin')
@section('title','Edit Staff')
@section('page-title','Edit Staff Member')
@section('page-sub', $member->name)

@section('content')
<div class="max-w-2xl">
  <form method="POST" action="{{ route('admin.staff.update', $member->id) }}">
    @csrf @method('PUT')

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-3 mb-4 text-sm text-red-700 font-body">
      @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
    @endif

    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Account Details</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Full Name *</label>
          <input type="text" name="name" value="{{ old('name', $member->name) }}" required
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Email *</label>
          <input type="email" name="email" value="{{ old('email', $member->email) }}" required
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Phone</label>
          <input type="text" name="phone" value="{{ old('phone', $member->phone) }}"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div></div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">New Password</label>
          <input type="password" name="password" minlength="8" placeholder="Leave blank to keep current"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Confirm New Password</label>
          <input type="password" name="password_confirmation" minlength="8"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
      </div>
    </div>

    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-4">Role & Location</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Role *</label>
          <select name="role" id="roleSelect" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            @foreach($roles as $r)
              <option value="{{ $r }}" {{ old('role', $member->role)===$r?'selected':'' }}>{{ $roleLabels[$r] ?? ucwords(str_replace('_',' ',$r)) }}</option>
            @endforeach
          </select>
          <p class="text-xs text-gray-400 font-body mt-1.5" id="roleHint"></p>
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Location *</label>
          <select name="location" required class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body bg-white focus:outline-none">
            @foreach($locations as $loc)
              <option value="{{ $loc }}" {{ old('location', $member->location)===$loc?'selected':'' }}>{{ $loc }}</option>
            @endforeach
          </select>
        </div>
      </div>
    </div>

    {{-- Admin-only: discount caps + commission terms ───────────────── --}}
    @if(session('staff_role') === 'admin')
    <div class="stat-card mb-5">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">Discount Allowance</h2>
      <p class="text-xs text-gray-400 font-body mb-4">Maximum discount this staff member can apply on an invoice before requiring an override reason. Leave blank for no cap.</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Fixed Cap (local currency)</label>
          <input type="number" name="discount_cap_fixed" value="{{ old('discount_cap_fixed', $member->discount_cap_fixed) }}" step="0.01" min="0"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Percent Cap (%)</label>
          <input type="number" name="discount_cap_percent" value="{{ old('discount_cap_percent', $member->discount_cap_percent) }}" step="0.1" min="0" max="100"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
      </div>
    </div>

    <div class="stat-card mb-5" id="commissionCard">
      <h2 class="font-display font-700 text-navy text-sm tracking-wide uppercase mb-1">Commission (Sales Rep)</h2>
      <p class="text-xs text-gray-400 font-body mb-4">Base % applies to every sale. Optional volume tiers override it once the rep crosses a monthly sales threshold (their own currency).</p>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
        <div>
          <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Base Commission %</label>
          <input type="number" name="commission_base_percent" value="{{ old('commission_base_percent', $member->commission_base_percent) }}" step="0.1" min="0" max="100" placeholder="e.g. 2.0"
            class="w-full border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm font-body focus:outline-none focus:border-yellow-400">
        </div>
      </div>
      <div>
        <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">Volume Tiers (optional)</label>
        <div id="tiersContainer" class="space-y-2 mb-2"></div>
        <button type="button" onclick="addTierRow()" class="text-xs font-body font-500 text-gold hover:text-gold-dark underline">+ Add a tier</button>
        <input type="hidden" name="commission_tiers" id="commissionTiersInput">
      </div>
    </div>
    @endif

    <div class="flex gap-3">
      <button type="submit" class="flex-1 bg-gold text-navy font-display font-700 text-sm py-3.5 rounded-xl tracking-wide hover:bg-yellow-500 transition-colors">
        Save Changes
      </button>
      <a href="{{ route('admin.staff.index') }}" class="border border-gray-200 text-gray-500 font-body font-500 text-sm px-5 py-3.5 rounded-xl hover:bg-gray-50 transition-colors">Cancel</a>
    </div>
  </form>
</div>

@push('scripts')
<script>
const ROLE_HINTS = {
    admin:          'Full access to everything.',
    manager:        'Full access except editing other managers/admins.',
    supervisor:     'Manager-level access EXCEPT staff management, discount caps, and financial reports.',
    staff:          'Standard access — harvest, inventory, invoices, customers.',
    stocking_clerk: 'Restricted to New Harvest, Add Part Manually, and Add Consumable ONLY. No editing, pricing, invoices, or reports.',
    sales_rep:      'Can create invoices and earn commission on sales. Set commission % below.',
    viewer:         'Read-only access.',
};

function updateRoleHint() {
    const role = document.getElementById('roleSelect').value;
    document.getElementById('roleHint').textContent = ROLE_HINTS[role] || '';
    const commissionCard = document.getElementById('commissionCard');
    if (commissionCard) commissionCard.style.display = role === 'sales_rep' ? '' : 'none';
}
document.getElementById('roleSelect').addEventListener('change', updateRoleHint);
document.addEventListener('DOMContentLoaded', updateRoleHint);

// ── Commission tier builder — pre-fill from existing JSON if present ──────
let tierCount = 0;
function addTierRow(min = '', pct = '') {
    const i = tierCount++;
    const row = document.createElement('div');
    row.className = 'flex gap-2 items-center';
    row.innerHTML = `
        <input type="number" placeholder="Min volume (e.g. 500000)" step="0.01" min="0" value="${min}"
            class="tier-min flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-yellow-400">
        <input type="number" placeholder="Percent (e.g. 3)" step="0.1" min="0" max="100" value="${pct}"
            class="tier-pct w-28 border border-gray-200 rounded-lg px-3 py-2 text-sm font-body focus:outline-none focus:border-yellow-400">
        <button type="button" onclick="this.closest('div').remove(); syncTiers()" class="text-red-400 hover:text-red-600 text-lg leading-none">×</button>
    `;
    document.getElementById('tiersContainer').appendChild(row);
    row.querySelectorAll('input').forEach(inp => inp.addEventListener('input', syncTiers));
}

function syncTiers() {
    const rows = document.querySelectorAll('#tiersContainer > div');
    const tiers = [];
    rows.forEach(row => {
        const min = parseFloat(row.querySelector('.tier-min').value);
        const pct = parseFloat(row.querySelector('.tier-pct').value);
        if (!isNaN(min) && !isNaN(pct)) tiers.push({ min_volume: min, percent: pct });
    });
    document.getElementById('commissionTiersInput').value = tiers.length ? JSON.stringify(tiers) : '';
}

@if(session('staff_role') === 'admin' && $member->commission_tiers)
    @php $existingTiers = json_decode($member->commission_tiers, true) ?? []; @endphp
    @foreach($existingTiers as $t)
        addTierRow({{ $t['min_volume'] ?? 0 }}, {{ $t['percent'] ?? 0 }});
    @endforeach
    syncTiers();
@endif

document.querySelector('form').addEventListener('submit', syncTiers);
</script>
@endpush
@endsection
