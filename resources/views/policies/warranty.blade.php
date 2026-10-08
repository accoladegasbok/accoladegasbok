{{-- FILE: resources/views/policies/warranty.blade.php --}}
@extends('layouts.app')
@section('title', 'Mission & Limited Warranty — Auto Zenith Parts')
@section('meta_desc', 'Auto Zenith Parts mission and limited warranty: coverage periods for engines, transmissions, mechanical and electrical parts in the USA and West Africa.')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-10">
  <h1 class="font-display font-800 text-navy text-3xl tracking-wide mb-1">Mission &amp; Limited Warranty</h1>
  <p class="text-xs text-gray-400 font-body mb-6">Last updated October 2026</p>

  <div class="space-y-8 font-body text-gray-700 text-sm leading-relaxed">

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">Our mission</h2>
      <p>To keep vehicles on the road by supplying quality-tested, genuine OEM used parts across the USA and West Africa. Every part is accurately identified, honestly priced and backed by a warranty. We inspect before we sell, describe parts truthfully, and make it right when something goes wrong.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">What the warranty covers</h2>
      <p>Defects that prevent a part from working normally, on parts sold with an Auto Zenith stock number. The warranty starts on the date of your invoice or receipt, belongs to the original purchaser, and cannot be transferred.</p>

      <div class="overflow-x-auto mt-4 rounded-xl border border-gray-200 bg-white">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 text-xs uppercase tracking-wider text-gray-500">
            <tr><th class="text-left px-4 py-3">Part</th><th class="text-left px-4 py-3">USA</th><th class="text-left px-4 py-3">West Africa</th></tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr><td class="px-4 py-3 font-500 text-navy">Complete engines (including Complete Engine, Bare / Borale)</td><td class="px-4 py-3">90 days</td><td class="px-4 py-3">30 days</td></tr>
            <tr><td class="px-4 py-3 font-500 text-navy">Transmissions</td><td class="px-4 py-3">90 days</td><td class="px-4 py-3">30 days, only when installed by an Auto Zenith recommended technician using our recommended transmission oil</td></tr>
            <tr><td class="px-4 py-3 font-500 text-navy">Other mechanical parts (alternators, starters, A/C compressors, power steering pumps, axles, suspension, brakes, differentials)</td><td class="px-4 py-3">30 days</td><td class="px-4 py-3">30 days</td></tr>
            <tr><td class="px-4 py-3 font-500 text-navy">Electrical and electronic modules (ECU, TCM, ABS, BCM, sensors, screens, clusters)</td><td class="px-4 py-3">14 days — exchange or store credit only</td><td class="px-4 py-3">14 days — exchange or store credit only</td></tr>
            <tr><td class="px-4 py-3 font-500 text-navy">Body panels, glass, lights, interior, trim</td><td class="px-4 py-3" colspan="2">Inspect when you receive it. Report damage within 48 hours. No defect warranty after that.</td></tr>
            <tr><td class="px-4 py-3 font-500 text-navy">Consumables and new items</td><td class="px-4 py-3" colspan="2">Manufacturer's warranty only</td></tr>
          </tbody>
        </table>
      </div>

      <p class="mt-3"><strong>Transmissions bought and taken away</strong> (not installed by our recommended technician) carry <strong>no warranty</strong>. Please inspect them properly before you buy. For a transmission claim in West Africa we need the technician's job card or installation record.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">What we do if a part is covered</h2>
      <p>At our option we replace the part, give store credit, or refund the price in the currency of the original sale. A replacement carries only the warranty time that remained. What we pay never exceeds the price you paid.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">What is not covered</h2>
      <p>Labor, towing, fluids, gaskets and seals, rental, lost time or income, shipping, ECU programming or coding, and incidental or consequential damage.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">What voids the warranty</h2>
      <ul class="list-disc pl-5 space-y-1">
        <li>The Auto Zenith tag, paint mark or seal has been removed.</li>
        <li>The part was disassembled before we inspected it.</li>
        <li>Overheating, wrong or low fluids, accident, flood, racing, misuse or modification.</li>
        <li>It was installed on a different vehicle, or you chose the wrong part.</li>
        <li><strong>Engines</strong> installed without new oil and filter, fresh coolant, a cooling-system and timing belt or chain inspection, and new gaskets and seals.</li>
        <li><strong>Transmissions</strong> installed without flushing the cooler lines and using the correct fluid (and, in West Africa, without our recommended technician and oil).</li>
      </ul>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">How to make a claim</h2>
      <ol class="list-decimal pl-5 space-y-1">
        <li>Contact the Auto Zenith location you bought from, within the warranty period, with your <strong>invoice or receipt number</strong> and the part's <strong>stock number</strong>.</li>
        <li>We may ask for a mechanic's diagnostic report.</li>
        <li>Return the part <strong>uninstalled and complete</strong> to where you bought it.</li>
        <li>We tell you our decision within <strong>5 business days</strong>.</li>
      </ol>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">Fitment guarantee and returns</h2>
      <p>If we confirmed fitment for your VIN or year, make and model and the part does not fit, we exchange or refund it when it is returned uninstalled within 7 days. Parts that are not defective can be returned unused and uninstalled, with the tag intact, within 7 days, for store credit or a refund less a 20% restocking fee. Electrical parts and special orders cannot be returned unless defective. Full details are in our <a href="{{ route('policy.refund') }}" class="underline">Refund Policy</a>.</p>
    </section>

    <section>
      <h2 class="font-display font-700 text-navy text-lg mb-2">Questions</h2>
      <p>Email <a class="underline" href="mailto:info@autozenithparts.com">info@autozenithparts.com</a>, or message us on WhatsApp: USA <a class="underline" href="https://wa.me/16822563201" target="_blank" rel="noopener">+1 (682) 256-3201</a>, Nigeria / Ghana <a class="underline" href="https://wa.me/2349155688804" target="_blank" rel="noopener">+234 915 568 8804</a>.</p>
    </section>
  </div>
</div>
@endsection
