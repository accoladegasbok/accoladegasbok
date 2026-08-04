<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Auto Zenith Parts — Quality Used Auto Parts')</title>
    <meta name="description" content="@yield('meta_desc', 'Search quality used auto parts by VIN or vehicle. Toyota, Lexus, Honda, Nissan, Kia, Hyundai and more. Locations in Texas, Wisconsin, Nigeria and Ghana.')">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">

    {{-- Tailwind via CDN (replace with compiled in production) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy:  { DEFAULT: '#0A1F5C', light: '#132474', dark: '#060F2E' },
                        gold:  { DEFAULT: '#C8960C', light: '#F0B429', dark: '#9A7309' },
                        az:    { blue: '#185FA5', teal: '#0F6E56', red: '#A32D2D' },
                    },
                    fontFamily: {
                        display: ['"Barlow Condensed"', 'sans-serif'],
                        body:    ['"DM Sans"', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DM Sans', sans-serif; }

        /* Grade badges */
        .grade-a { background:#EAF3DE; color:#27500A; border:1px solid #C0DD97; }
        .grade-b { background:#E6F1FB; color:#0C447C; border:1px solid #B5D4F4; }
        .grade-c { background:#FAEEDA; color:#633806; border:1px solid #FAC775; }
        .grade-new{ background:#EEEDFE; color:#3C3489; border:1px solid #AFA9EC; }

        /* Location badges */
        .loc-us { background:#E6F1FB; color:#185FA5; }
        .loc-ng { background:#EAF3DE; color:#3B6D11; }
        .loc-gh { background:#FAEEDA; color:#854F0B; }

        /* Part card hover */
        .part-card { transition: box-shadow .18s ease, transform .18s ease; }
        .part-card:hover { box-shadow: 0 8px 28px rgba(10,31,92,.12); transform: translateY(-2px); }

        /* VIN input glow */
        .vin-input:focus { box-shadow: 0 0 0 3px rgba(200,150,12,.3); border-color: #C8960C; }

        /* Sidebar filter transition */
        .filter-section { transition: max-height .25s ease; overflow: hidden; }

        /* Loading skeleton */
        @keyframes shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }
        .skeleton { background: linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);
                    background-size:200% 100%; animation: shimmer 1.4s infinite; border-radius:6px; }

        /* Nav active */
        .nav-link.active { color:#C8960C; border-bottom:2px solid #C8960C; }

        /* Scrollbar thin */
        ::-webkit-scrollbar { width:5px; } ::-webkit-scrollbar-track { background:#f1f1f1; }
        ::-webkit-scrollbar-thumb { background:#C8960C; border-radius:3px; }

        html { scroll-behavior: smooth; }
    </style>

    @stack('head')
</head>
<body class="bg-gray-50 text-gray-900 antialiased">

{{-- ── Navigation ─────────────────────────────────────────────────────────── --}}
<nav class="bg-navy sticky top-0 z-50 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-16">

            {{-- Logo — links to the SEO homepage, not the parts search
                 page itself (search is reached via the "Search Parts"
                 nav link below instead). --}}
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <div class="w-9 h-9 bg-gold rounded flex items-center justify-center">
                    <span class="font-display font-800 text-navy text-lg leading-none">AZ</span>
                </div>
                <div>
                    <div class="font-display font-700 text-white text-xl leading-none tracking-wide">AUTO ZENITH</div>
                    <div class="text-gold text-xs font-body font-500 tracking-widest uppercase leading-none">Parts</div>
                </div>
            </a>

            {{-- Nav links --}}
            <div class="hidden md:flex items-center gap-6">
                <a href="{{ route('parts.search') }}" class="nav-link text-gray-300 hover:text-white text-sm font-body font-500 pb-1 transition-colors {{ request()->routeIs('parts.search') ? 'active' : '' }}">Search Parts</a>
                <a href="{{ route('parts.compatibility') }}" class="nav-link text-gray-300 hover:text-white text-sm font-body font-500 pb-1 transition-colors {{ request()->routeIs('parts.compatibility') ? 'active' : '' }}">Compatibility Checker</a>
                <a href="https://accounts.autozenithparts.com" target="_blank"
                   class="bg-gold hover:bg-yellow-500 text-navy font-display font-700 text-sm px-4 py-2 rounded-lg transition-colors tracking-wide">
                  ACCOUNTIX
                </a>
                <a href="#how-it-works" class="nav-link text-gray-300 hover:text-white text-sm font-body pb-1 transition-colors">How It Works</a>
                <a href="#footer-locations" class="nav-link text-gray-300 hover:text-white text-sm font-body pb-1 transition-colors">Locations</a>
                <a href="#footer-contact" class="nav-link text-gray-300 hover:text-white text-sm font-body pb-1 transition-colors">Contact</a>
            </div>

            {{-- WhatsApp CTA — asks location first so enquiries reach the right regional number --}}
            <button onclick="openWaPicker()"
               class="hidden sm:flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white text-sm font-body font-500 px-4 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.097.543 4.068 1.496 5.779L0 24l6.394-1.677A11.948 11.948 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.6a9.574 9.574 0 01-4.888-1.343l-.35-.208-3.627.952.968-3.537-.228-.363A9.564 9.564 0 012.4 12C2.4 6.698 6.698 2.4 12 2.4S21.6 6.698 21.6 12 17.302 21.6 12 21.6z"/></svg>
                WhatsApp Us
            </button>

            {{-- Mobile menu toggle --}}
            <button id="mobileMenuBtn" class="md:hidden text-gray-300 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>

        {{-- Mobile menu --}}
        <div id="mobileMenu" class="md:hidden hidden pb-4">
            <div class="flex flex-col gap-3 pt-2 border-t border-navy-light">
                <a href="{{ route('parts.search') }}" class="text-gray-300 hover:text-white text-sm font-body py-1">Search Parts</a>
                <a href="{{ route('parts.compatibility') }}" class="text-gray-300 hover:text-white text-sm font-body py-1">Compatibility Checker</a>
                <a href="https://accounts.autozenithparts.com" target="_blank" class="inline-block w-fit bg-gold text-navy font-display font-700 text-sm px-4 py-2 rounded-lg tracking-wide">ACCOUNTIX</a>
                <a href="#how-it-works" class="text-gray-300 hover:text-white text-sm font-body py-1">How It Works</a>
                <a href="#footer-locations" class="text-gray-300 hover:text-white text-sm font-body py-1">Locations</a>
                <a href="#footer-contact" class="text-gray-300 hover:text-white text-sm font-body py-1">Contact</a>
                <button onclick="openWaPicker()" class="text-left text-green-400 hover:text-green-300 text-sm font-body py-1 font-500">WhatsApp Us</button>
            </div>
        </div>
    </div>
</nav>

{{-- ── WhatsApp location picker modal ──────────────────────────────────────── --}}
<div id="waPickerModal" class="hidden fixed inset-0 z-[60] bg-black bg-opacity-50 items-center justify-center px-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 relative">
        <button onclick="closeWaPicker()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <h3 class="font-display font-700 text-navy text-lg tracking-wide mb-1">Where are you contacting us from?</h3>
        <p class="text-sm text-gray-500 font-body mb-5">We'll connect you to the right team for your region.</p>
        <div class="space-y-2">
            <button onclick="goToWa('16822563201')" class="w-full flex items-center gap-3 border border-gray-200 hover:border-green-400 hover:bg-green-50 rounded-xl px-4 py-3 transition-colors text-left">
                <span class="text-xl">🇺🇸</span>
                <span class="font-body font-500 text-navy text-sm">USA</span>
            </button>
            <button onclick="goToWa('2349155688804')" class="w-full flex items-center gap-3 border border-gray-200 hover:border-green-400 hover:bg-green-50 rounded-xl px-4 py-3 transition-colors text-left">
                <span class="text-xl">🇳🇬🇬🇭</span>
                <span class="font-body font-500 text-navy text-sm">Nigeria or Ghana</span>
            </button>
        </div>
    </div>
</div>

{{-- ── Page Content ────────────────────────────────────────────────────────── --}}
@yield('content')

{{-- ── How It Works (lives here so the nav link works on every page) ──────── --}}
<section id="how-it-works" class="bg-white border-t border-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12">
        <h2 class="font-display font-700 text-navy text-2xl tracking-wide text-center mb-8">HOW IT WORKS</h2>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-12 h-12 bg-gold rounded-full flex items-center justify-center mx-auto mb-3">
                    <span class="font-display font-800 text-navy text-lg">1</span>
                </div>
                <div class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">Search or Send Your VIN</div>
                <p class="text-sm text-gray-500 font-body">Find your part by vehicle, or enter your VIN for an exact-fit match.</p>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 bg-gold rounded-full flex items-center justify-center mx-auto mb-3">
                    <span class="font-display font-800 text-navy text-lg">2</span>
                </div>
                <div class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">Confirm Fitment & Price</div>
                <p class="text-sm text-gray-500 font-body">Check compatibility, grade, and price — each part lists its real fixed price, no surprises.</p>
            </div>
            <div class="text-center">
                <div class="w-12 h-12 bg-gold rounded-full flex items-center justify-center mx-auto mb-3">
                    <span class="font-display font-800 text-navy text-lg">3</span>
                </div>
                <div class="font-display font-700 text-navy text-sm uppercase tracking-wide mb-1">Order, Pickup, or Delivery</div>
                <p class="text-sm text-gray-500 font-body">Order online or via WhatsApp, then pick up in-store or arrange delivery.</p>
            </div>
        </div>
    </div>
</section>

{{-- ── Footer ──────────────────────────────────────────────────────────────── --}}
<footer class="bg-navy text-gray-400 mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="md:col-span-2">
                <div class="font-display font-700 text-white text-2xl mb-1 tracking-wide">AUTO ZENITH PARTS</div>
                <div class="text-gold text-xs font-500 tracking-widest uppercase mb-4">A Division of Gasbok Engineering Nig. Limited · RC: 1135830</div>
                <p class="text-sm text-gray-400 leading-relaxed max-w-sm">Quality used and new spare parts for Toyota, Lexus, Honda, Nissan, Kia, Hyundai, Mercedes-Benz, Infiniti, Ford, GM, Chevrolet, Acura and VW — across the USA, Nigeria and Ghana.</p>
            </div>
            <div id="footer-locations">
                <div class="text-white font-500 text-sm uppercase tracking-wider mb-4">Locations</div>
                <ul class="space-y-2 text-sm">
                    <li>📍 3230 S Hwy 77, Suite 303, Waxahachie TX <span class="text-gold text-xs">(HQ)</span></li>
                    <li>📍 613 E Geneva St #23, Elkhorn WI</li>
                    <li>📍 Ile-Ife <span class="text-gold text-xs">(Regional HQ)</span> · Ibadan · Lagos · Abuja · Akure, Nigeria</li>
                    <li>📍 Accra, Ghana</li>
                </ul>
            </div>
            <div id="footer-contact">
                <div class="text-white font-500 text-sm uppercase tracking-wider mb-4">Contact</div>
                <ul class="space-y-2 text-sm">
                    <li>🇺🇸 <a href="https://wa.me/16822563201" target="_blank" class="hover:text-white transition-colors">+1 (682) 256-3201</a> <span class="text-xs text-gray-500">— USA</span></li>
                    <li>🌍 <a href="https://wa.me/2349155688804" target="_blank" class="hover:text-white transition-colors">+234 915 568 8804</a> <span class="text-xs text-gray-500">— Nigeria & Ghana</span></li>
                    <li class="pt-1 border-t border-navy-light mt-1">
                        <a href="https://wa.me/2348067422777" target="_blank" class="hover:text-white transition-colors">+234 806 742 2777</a>
                        <span class="text-xs text-amber-400">— Complaints (WhatsApp only)</span>
                    </li>
                    <li>🌐 autozenithparts.com</li>
                    <li>🔐 <a href="https://accounts.autozenithparts.com" target="_blank" class="hover:text-white transition-colors">accounts.autozenithparts.com</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-navy-light mt-8 pt-6 text-xs text-gray-500 flex flex-col sm:flex-row justify-between gap-2">
            <span>© {{ date('Y') }} Auto Zenith LLC. All rights reserved.</span>
            <span>Quality Cars · Trusted Deals · Smooth Delivery</span>
        </div>
    </div>
</footer>

<script>
    // Mobile menu toggle
    document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
        document.getElementById('mobileMenu').classList.toggle('hidden');
    });

    // WhatsApp location picker — ensures enquiries reach the correct
    // regional number rather than always defaulting to one country.
    // Optionally pass a pre-filled message (e.g. from a "source this
    // part" request) which carries through to whichever number the
    // visitor picks.
    let waPickerMessage = '';
    function openWaPicker(message = '') {
        waPickerMessage = message;
        const modal = document.getElementById('waPickerModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
    function closeWaPicker() {
        const modal = document.getElementById('waPickerModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    function goToWa(number) {
        const url = waPickerMessage
            ? `https://wa.me/${number}?text=${encodeURIComponent(waPickerMessage)}`
            : `https://wa.me/${number}`;
        window.open(url, '_blank');
        closeWaPicker();
    }
    document.getElementById('waPickerModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeWaPicker();
    });
</script>

@stack('scripts')
</body>
</html>
