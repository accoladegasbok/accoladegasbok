{{-- FILE: resources/views/admin/override/set-pin.blade.php --}}
@extends('admin.layouts.admin')
@section('title', 'Set Supervisor PIN')
@section('page-title', 'Set Your Supervisor PIN')
@section('page-sub', 'Set or update your 4-digit override PIN — required to approve supervisor actions')

@section('content')
<div class="max-w-md">

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 mb-5 text-sm text-green-700">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 mb-5 text-sm text-red-700">
        @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
    </div>
    @endif

    <div class="stat-card">
        <div class="flex items-center gap-3 mb-5">
            <div class="w-12 h-12 rounded-xl bg-navy flex items-center justify-center text-2xl">🔐</div>
            <div>
                <div class="font-display font-700 text-navy text-base">Supervisor Override PIN</div>
                <div class="text-xs text-gray-400 mt-0.5">Only admin, manager and supervisor roles can set a PIN</div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.override.set-pin') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-xs font-body font-500 text-gray-500 uppercase tracking-wider mb-1.5">
                    New 4-Digit PIN
                </label>
                <input type="password" name="pin" maxlength="4" inputmode="numeric"
                       pattern="[0-9]{4}" required autocomplete="new-password"
                       class="w-full border border-gray-200 rounded-xl px-4 py-3 text-center text-3xl font-mono tracking-widest focus:outline-none focus:border-yellow-400"
                       placeholder="••••">
                <p class="text-[10px] text-gray-400 mt-1.5">
                    Must be exactly 4 digits. Never share your PIN.
                    Your PIN is hashed — even admins cannot view it, only reset it.
                </p>
            </div>

            <button type="submit"
                    class="w-full bg-gold text-navy font-display font-700 text-sm py-3.5 rounded-xl hover:bg-yellow-500 transition-colors">
                Set PIN
            </button>
        </form>

        <div class="mt-5 pt-5 border-t border-gray-100">
            <p class="text-xs text-gray-400">
                Your PIN is used to approve supervisor actions — removing cart items mid-sale,
                applying discounts above your cap, deleting invoices, and editing locked prices.
                Every use is logged in the override audit trail.
            </p>
        </div>
    </div>

</div>
@endsection
