{{-- FILE: resources/views/partials/_contacts.blade.php --}}
{{-- Contact cards for the home page. Every number and email comes from App\Support\Contacts — change them there. --}}
@php
    $C = \App\Support\Contacts::class;
    $cards = [
        ['usa',    '🇺🇸 United States'],
        ['lagos',  '🇳🇬 Lagos — main West Africa hub'],
        ['ibadan', '🇳🇬 Ibadan'],
        ['ife',    '🇳🇬 Ile-Ife — regional hub'],
        ['kaduna', '🇳🇬 Kaduna / Jos / Makurdi'],
        ['accra',  '🇬🇭 Accra, Ghana'],
    ];
@endphp
<section class="locations" id="contact" style="background:#fff; border-top:1px solid var(--line);">
  <div class="wrap">
    <div class="section-head">
      <div class="section-eyebrow">Contact Us</div>
      <h2 class="display">Talk to your<br>nearest hub.</h2>
      <p>Message us on WhatsApp or call. Email the team for your region: USA <a href="mailto:{{ $C::email('usa') }}">{{ $C::email('usa') }}</a>, Nigeria <a href="mailto:{{ $C::email('lagos') }}">{{ $C::email('lagos') }}</a>, Ghana <a href="mailto:{{ $C::email('accra') }}">{{ $C::email('accra') }}</a>, or anything else <a href="mailto:{{ $C::GENERAL_EMAIL }}">{{ $C::GENERAL_EMAIL }}</a>.</p>
    </div>
    <div class="loc-grid">
      @foreach($cards as [$key, $title])
      <div class="loc-card">
        <div class="loc-flag">{{ $title }}</div>
        <h3>{{ $C::line($key)['name'] }}</h3>
        <ul>
          <li><a href="https://wa.me/{{ $C::wa($key) }}" target="_blank" rel="noopener">WhatsApp {{ $C::prettyKey($key) }}</a></li>
          <li><a href="tel:{{ $C::line($key)['number'] }}">Call {{ $C::prettyKey($key) }}</a></li>
          <li><a href="mailto:{{ $C::email($key) }}">{{ $C::email($key) }}</a></li>
          @if(!empty($C::line($key)['address']))<li>{{ $C::line($key)['address'] }}</li>@endif
        </ul>
      </div>
      @endforeach
      <div class="loc-card">
        <div class="loc-flag">⚠ Complaints &amp; general enquiries</div>
        <h3>WhatsApp only</h3>
        <ul>
          <li><a href="https://wa.me/{{ $C::wa('complaints') }}" target="_blank" rel="noopener">WhatsApp {{ $C::prettyKey('complaints') }}</a></li>
          <li><a href="mailto:{{ $C::GENERAL_EMAIL }}">{{ $C::GENERAL_EMAIL }}</a></li>
        </ul>
      </div>
    </div>
  </div>
</section>
