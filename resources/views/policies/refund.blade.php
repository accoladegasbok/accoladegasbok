{{-- FILE: resources/views/policies/refund.blade.php --}}
@extends('layouts.app')
@section('title', 'Refund Policy — Auto Zenith Parts')
@section('meta_desc', 'Auto Zenith Parts refund and return policy: 7-day returns, fitment guarantee, defective parts, and how refunds are paid.')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
  <h1 class="font-display font-800 text-navy text-3xl tracking-wide mb-1">Refund Policy</h1>
  <p class="text-xs text-gray-400 font-body mb-6">Last updated October 2026</p>

  <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-8 text-sm font-body text-amber-900 leading-relaxed">
    <strong>In short:</strong> return a part within <strong>7 days</strong>, unused, uninstalled and with our tag intact, and you get store credit or a refund less a <strong>20% restocking fee</strong>.
    If we confirmed it would fit your vehicle and it doesn't, we exchange or refund it. Parts that turn out defective are handled under our <a href="{{ route('policy.warranty') }}" class="underline">Warranty</a>.
  </div>

  <div class="space-y-7 font-body text-gray-700 text-sm leading-relaxed">

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">1. It doesn't fit (fitment guarantee)</h2>
      <p>If Auto Zenith confirmed fitment for your VIN, or for your year, make and model, and the part does not fit, we will exchange it or refund it, as long as it comes back <strong>uninstalled within 7 days</strong>.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">2. You changed your mind</h2>
      <p>You can return a part that is not defective if it is:</p>
      <ul class="list-disc pl-5 mt-2 space-y-1">
        <li>unused and uninstalled,</li>
        <li>still carrying our Auto Zenith tag or mark, and</li>
        <li>returned within <strong>7 days</strong> of the date on your invoice or receipt.</li>
      </ul>
      <p class="mt-2">We give you store credit or a refund <strong>less a 20% restocking fee</strong>.</p>
      <p class="mt-2"><strong>Not returnable</strong> unless defective: electrical parts (ECUs, modules, sensors, screens, clusters and similar) and special orders.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">3. The part is defective</h2>
      <p>Defective parts are covered by our <a href="{{ route('policy.warranty') }}" class="underline">Warranty</a>. At our option we replace the part, give store credit, or refund the price in the currency of the original sale. A replacement carries only the warranty time remaining, and what we pay never exceeds the price you paid.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">4. What is never refunded</h2>
      <p>Labor, towing, fluids, gaskets and seals, shipping, rental, lost time or income, ECU programming or coding, and any incidental or consequential damage.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">5. How to return a part</h2>
      <ol class="list-decimal pl-5 space-y-1">
        <li>Contact the Auto Zenith location you bought from, within the time allowed, with your <strong>invoice or receipt number</strong> and the part's <strong>stock number</strong>.</li>
        <li>Bring or send the part back <strong>uninstalled and complete</strong> to where you bought it.</li>
        <li>We inspect it and tell you our decision within <strong>5 business days</strong>.</li>
      </ol>
      <p class="mt-2">For a defective part we may ask for a mechanic's diagnostic report.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">6. How refunds are paid</h2>
      <p>Refunds are always in the <strong>currency of the original sale</strong>. We never convert between currencies. We agree the method with you when the return is approved: store credit, bank transfer, or cash at the location you bought from.</p>
      <p class="mt-2">Store credit stays on your account, in its own currency, and can be used on your next purchase.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">7. Orders you have not paid for yet</h2>
      <p>When you place an order online we reserve your parts for <strong>24 hours</strong>. If payment is not completed in that time the parts are released and nothing is charged.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">Questions</h2>
      <p>Email <a class="underline" href="mailto:info@autozenithparts.com">info@autozenithparts.com</a>, or message us on WhatsApp: USA <a class="underline" href="https://wa.me/16822563201" target="_blank" rel="noopener">+1 (682) 256-3201</a>, Nigeria / Ghana <a class="underline" href="https://wa.me/2349155688804" target="_blank" rel="noopener">+234 915 568 8804</a>.</p>
    </section>
  </div>
</div>
@endsection
