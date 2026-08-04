@extends('layouts.app')
@section('title', 'Log In — Auto Zenith Parts')
@section('content')
<div class="max-w-md mx-auto px-4 py-16">
  <h1 class="font-display font-800 text-navy text-2xl mb-1">Welcome Back</h1>
  <p class="text-sm text-gray-500 mb-6">Log in to view your orders and account details.</p>

  @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('error') }}</div>@endif

  <form method="POST" action="{{ route('customer.login.attempt') }}" class="space-y-4">
    @csrf
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Email or Phone</label>
      <input type="text" name="identifier" value="{{ old('identifier') }}" required class="w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Password</label>
      <input type="password" name="password" required class="w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <button type="submit" class="w-full bg-gold text-navy font-display font-700 text-sm py-3 rounded-xl tracking-wide hover:bg-yellow-500 transition-colors">
      Log In
    </button>
  </form>

  <p class="text-center text-sm text-gray-500 mt-5">
    New here? <a href="{{ route('customer.register') }}" class="text-navy font-600 hover:underline">Create an account</a>
  </p>
</div>
@endsection
