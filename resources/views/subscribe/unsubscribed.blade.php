{{-- FILE: resources/views/subscribe/unsubscribed.blade.php --}}
@extends('layouts.app')
@section('title', 'Unsubscribed — Auto Zenith Parts')
@section('content')
<div class="max-w-md mx-auto px-4 py-20 text-center">
  <h1 class="font-display font-800 text-navy text-2xl mb-2">You're unsubscribed</h1>
  <p class="text-sm text-gray-500 mb-6"><strong>{{ $email }}</strong> will no longer receive our marketing emails.</p>
  <a href="{{ route('parts.search') }}" class="inline-block bg-navy text-white font-display font-700 text-sm px-6 py-3 rounded-xl">Search Parts</a>
</div>
@endsection
