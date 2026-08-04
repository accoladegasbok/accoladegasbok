@extends('layouts.app')
@section('title', 'Link Telegram — Auto Zenith Parts')
@section('content')
<div class="max-w-sm mx-auto px-4 py-16 text-center">
  <h1 class="font-display font-800 text-navy text-2xl mb-1">Link Telegram</h1>
  <p class="text-sm text-gray-500 mb-6">Tap below to open Telegram and message our bot — this links your account so we can send you verification codes there too.</p>

  <a href="{{ $deepLink }}" target="_blank" class="block w-full bg-[#229ED9] text-white font-display font-700 text-sm py-3.5 rounded-xl tracking-wide hover:opacity-90 transition-opacity">
    Open Telegram →
  </a>

  <p class="text-xs text-gray-400 mt-4">After tapping "Start" in Telegram, come back and refresh your account page — it links automatically.</p>

  <a href="{{ route('customer.account') }}" class="block text-sm text-navy font-600 hover:underline mt-6">← Back to Account</a>
</div>
@endsection
