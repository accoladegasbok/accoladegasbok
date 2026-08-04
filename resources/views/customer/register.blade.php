@extends('layouts.app')
@section('title', 'Create Account — Auto Zenith Parts')
@section('content')
<div class="max-w-md mx-auto px-4 py-16">
  <h1 class="font-display font-800 text-navy text-2xl mb-1">Create Your Account</h1>
  <p class="text-sm text-gray-500 mb-6">Track orders, save your details, and check out faster next time.</p>

  @if(session('error'))<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm">{{ session('error') }}</div>@endif
  @if($errors->any())
  <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-4 text-sm">
    @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
  </div>
  @endif

  <form method="POST" action="{{ route('customer.register.store') }}" class="space-y-4">
    @csrf
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Full Name</label>
      <input type="text" name="name" value="{{ old('name') }}" required class="w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Email</label>
      <input type="email" name="email" value="{{ old('email') }}" required class="w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Phone</label>
      <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+234..." required class="w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Password</label>
      <input type="password" name="password" required minlength="8" class="w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <div>
      <label class="block text-xs text-gray-500 uppercase tracking-wider mb-1">Confirm Password</label>
      <input type="password" name="password_confirmation" required minlength="8" class="w-full border border-gray-200 rounded-lg px-3.5 py-2.5 text-sm focus:outline-none focus:border-gold">
    </div>
    <button type="submit" class="w-full bg-gold text-navy font-display font-700 text-sm py-3 rounded-xl tracking-wide hover:bg-yellow-500 transition-colors">
      Create Account
    </button>
  </form>

  <p class="text-center text-sm text-gray-500 mt-5">
    Already have an account? <a href="{{ route('customer.login') }}" class="text-navy font-600 hover:underline">Log in</a>
  </p>
</div>
@endsection
