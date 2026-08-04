@extends('layouts.app')
@section('title', 'Verify Your Account — Auto Zenith Parts')
@section('content')
<div class="max-w-sm mx-auto px-4 py-16 text-center">
  <h1 class="font-display font-800 text-navy text-2xl mb-1">Enter Your Code</h1>
  <p class="text-sm text-gray-500 mb-6">We sent a 6-digit code to <strong class="text-navy">{{ $identifier }}</strong>.</p>

  @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('error') }}</div>@endif

  <form method="POST" action="{{ route('customer.verify.attempt') }}" class="space-y-4">
    @csrf
    <input type="text" name="code" maxlength="6" pattern="[0-9]{6}" required autofocus placeholder="000000"
      class="w-full text-center font-mono text-3xl tracking-[0.4em] border border-gray-200 rounded-xl px-4 py-4 focus:outline-none focus:border-gold">
    <button type="submit" class="w-full bg-gold text-navy font-display font-700 text-sm py-3 rounded-xl tracking-wide hover:bg-yellow-500 transition-colors">
      Verify
    </button>
  </form>

  <form method="POST" action="{{ route('customer.verify.resend') }}" class="mt-4">
    @csrf
    <button type="submit" class="text-sm text-navy font-600 hover:underline">Resend code</button>
  </form>
</div>
@endsection
