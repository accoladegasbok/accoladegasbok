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
    </style>

    @stack('head')
</head>
<body class="bg-gray-50 text-gray-900 antialiased">

{{-- ── Navigation ─────────────────────────────────────────────────────────── --}}
<nav class="bg-navy sticky top-0 z-50 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-16">

            {{-- Logo --}}
            <a href="{{ route('parts.search') }}" class="flex items-center gap-3">
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
                <a href="#" class="nav-link text-gray-300 hover:text-white text-sm font-body pb-1 transition-colors">How It Works</a>
                <a href="#" class="nav-link text-gray-300 hover:text-white text-sm font-body pb-1 transition-colors">Locations</a>
                <a href="#" class="nav-link text-gray-300 hover:text-white text-sm font-body pb-1 transition-colors">Contact</a>
            </div>

            {{-- WhatsApp CTA --}}
            <a href="https://wa.me/15125873425" target="_blank"
               class="hidden sm:flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white text-sm font-body font-500 px-4 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.097.543 4.068 1.496 5.779L0 24l6.394-1.677A11.948 11.948 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.6a9.574 9.574 0 01-4.888-1.343l-.35-.208-3.627.952.968-3.537-.228-.363A9.564 9.564 0 012.4 12C2.4 6.698 6.698 2.4 12 2.4S21.6 6.698 21.6 12 17.302 21.6 12 21.6z"/></svg>
                WhatsApp Us
            </a>

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
                <a href="#" class="text-gray-300 hover:text-white text-sm font-body py-1">How It Works</a>
                <a href="#" class="text-gray-300 hover:text-white text-sm font-body py-1">Locations</a>
                <a href="#" class="text-gray-300 hover:text-white text-sm font-body py-1">Contact</a>
            </div>
        </div>
    </div>
</nav>

{{-- ── Page Content ────────────────────────────────────────────────────────── --}}
@yield('content')

{{-- ── Footer ──────────────────────────────────────────────────────────────── --}}
<footer class="bg-navy text-gray-400 mt-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <div class="md:col-span-2">
                <div class="font-display font-700 text-white text-2xl mb-1 tracking-wide">AUTO ZENITH PARTS</div>
                <div class="text-gold text-xs font-500 tracking-widest uppercase mb-4">A Division of Gasbok Engineering Nig. Limited · RC: 1135830</div>
                <p class="text-sm text-gray-400 leading-relaxed max-w-sm">Quality used and new spare parts for Toyota, Lexus, Honda, Nissan, Kia, Hyundai, Mercedes-Benz, Infiniti, Ford, GM, Chevrolet, Acura and VW — across the USA, Nigeria and Ghana.</p>
            </div>
            <div>
                <div class="text-white font-500 text-sm uppercase tracking-wider mb-4">Locations</div>
                <ul class="space-y-2 text-sm">
                    <li>📍 3230 S Hwy 77, Suite 303, Waxahachie TX <span class="text-gold text-xs">(HQ)</span></li>
                    <li>📍 613 E Geneva St #23, Elkhorn WI</li>
                    <li>📍 Ile-Ife, Osun State · Ibadan · Oshodi Lagos</li>
                    <li>📍 Accra, Ghana</li>
                </ul>
            </div>
            <div>
                <div class="text-white font-500 text-sm uppercase tracking-wider mb-4">Contact</div>
                <ul class="space-y-2 text-sm">
                    <li>🇺🇸 +1 (512) 587-3425</li>
                    <li>🇳🇬 +234 706 441 3764</li>
                    <li>🇳🇬 +234 915 568 8804</li>
                    <li>🌐 autozenithparts.com</li>
                </ul>
            </div>
        </div>
        <div class="border-t border-navy-light mt-8 pt-6 text-xs text-gray-500 flex flex-col sm:flex-row justify-between gap-2">
            <span>© {{ date('Y') }} Auto Zenith Parts LLC. All rights reserved.</span>
            <span>Quality Cars · Trusted Deals · Smooth Delivery</span>
        </div>
    </div>
</footer>

<script>
    // Mobile menu toggle
    document.getElementById('mobileMenuBtn')?.addEventListener('click', () => {
        document.getElementById('mobileMenu').classList.toggle('hidden');
    });
</script>

@stack('scripts')
</body>
</html>
