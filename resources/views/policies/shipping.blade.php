{{-- FILE: resources/views/policies/shipping.blade.php --}}
@extends('layouts.app')
@section('title', 'Shipping Policy — Auto Zenith Parts')
@section('meta_desc', 'Auto Zenith Parts shipping and pickup policy: free pickup, delivery arranged with our team, and how our Lagos hub moves parts between yards.')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
  <h1 class="font-display font-800 text-navy text-3xl tracking-wide mb-1">Shipping Policy</h1>
  <p class="text-xs text-gray-400 font-body mb-6">Last updated October 2026</p>

  <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5 mb-8 text-sm font-body text-blue-900 leading-relaxed">
    <strong>In short:</strong> pickup at any of our locations is free. If you want delivery, our team arranges it and tells you the cost <strong>before you pay</strong>.
    In West Africa every part is available through our Lagos hub, and we bring it to Lagos from any of our yards at our own cost.
  </div>

  <div class="space-y-7 font-body text-gray-700 text-sm leading-relaxed">

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">1. Pickup</h2>
      <p>Pick up your order at any of our locations, free of charge, during business hours. Show your order reference to our staff. Our locations are listed in the footer of every page.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">2. Delivery</h2>
      <p>Tell us you want delivery when you order, or contact our team afterwards. We arrange it with you and confirm the delivery cost <strong>before you pay</strong>. Any shipping charge appears as its own <strong>Shipping</strong> line on your invoice or receipt. Shipping is not included in the part price unless your invoice says so.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">3. The Lagos hub (Nigeria and Ghana)</h2>
      <p>Our West Africa stock is shown to you as <strong>Lagos / Oshodi, Lagos / Ibadan, Lagos / Ife, Lagos / Akure, Lagos / Abuja</strong> and <strong>Lagos / Accra</strong>. You can search and order any of it through Lagos, our main hub.</p>
      <p class="mt-2">If the part you buy is in another yard, <strong>we move it to Lagos at our own expense</strong>. You do not pay for transfers between our yards.</p>
      <p class="mt-2">Prices are always in the currency of the location the part is listed in. We never convert between currencies, so a cart can only hold parts priced in one currency.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">4. Reserving and paying</h2>
      <ul class="list-disc pl-5 space-y-1">
        <li>When you place an order online we reserve your parts for <strong>24 hours</strong>.</li>
        <li>Pay by bank transfer, or by POS at any of our offices.</li>
        <li>For a bank transfer, send us your transfer reference. We confirm payment within about 1–2 hours.</li>
        <li>We release your parts to you, or send them out, once payment is confirmed.</li>
      </ul>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">5. USA orders</h2>
      <p>Parts ship from the yard that holds them. We quote the shipping cost to you before you pay, and it is shown on your invoice.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">6. When your part arrives</h2>
      <ul class="list-disc pl-5 space-y-1">
        <li>Check the part against your invoice or receipt straight away.</li>
        <li>Body panels, glass, lights, interior and trim: report any damage within <strong>48 hours</strong>.</li>
        <li>Keep our Auto Zenith tag or mark on the part. Removing it voids the warranty.</li>
      </ul>
      <p class="mt-2">For defects and returns, see our <a href="{{ route('policy.warranty') }}" class="underline">Warranty</a> and <a href="{{ route('policy.refund') }}" class="underline">Refund Policy</a>. Shipping costs are not refunded.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">Questions</h2>
      <p>Email <a class="underline" href="mailto:info@autozenithparts.com">info@autozenithparts.com</a>, or message us on WhatsApp: USA <a class="underline" href="https://wa.me/16822563201" target="_blank" rel="noopener">+1 (682) 256-3201</a>, Nigeria / Ghana <a class="underline" href="https://wa.me/2349155688804" target="_blank" rel="noopener">+234 915 568 8804</a>.</p>
    </section>
  </div>
</div>
@endsection
