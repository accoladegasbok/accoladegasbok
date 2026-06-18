{{--
=============================================================================
AI Smart Search Bar + Chatbot Widget
=============================================================================
Include this partial on the parts search page:
    @include('partials.ai-search')

Requires: Tailwind CSS, CSRF meta tag in <head>, /ai/search and /ai/chat routes
=============================================================================
--}}

{{-- ── AI Smart Search Bar ──────────────────────────────────────────────────── --}}
<div class="max-w-3xl mx-auto px-4 mb-6" id="aiSearchSection">
    <div class="relative">
        {{-- Search input --}}
        <div class="flex gap-2">
            <div class="flex-1 relative">
                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-gold">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <input
                    type="text"
                    id="aiSearchInput"
                    placeholder="Describe the part you need — e.g. &quot;left tail light 2019 Camry&quot; or &quot;Accord gearbox 2018&quot;"
                    class="w-full pl-12 pr-4 py-4 bg-white border-2 border-gold border-opacity-40 rounded-xl text-sm font-body focus:outline-none focus:border-gold shadow-lg placeholder:text-gray-400"
                    autocomplete="off"
                    maxlength="200"
                >
                {{-- Autocomplete dropdown --}}
                <div id="aiSuggestBox"
                    class="absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-xl shadow-xl z-50 hidden mt-1 overflow-hidden">
                </div>
            </div>
            <button id="aiSearchBtn"
                class="bg-gold hover:bg-yellow-600 text-navy font-display font-700 text-sm px-6 py-4 rounded-xl transition-colors flex items-center gap-2 whitespace-nowrap shadow-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                AI SEARCH
            </button>
        </div>

        {{-- Label --}}
        <p class="text-center text-gray-400 text-xs mt-2 font-body">
            <span class="inline-flex items-center gap-1">
                <svg class="w-3 h-3 text-gold" fill="currentColor" viewBox="0 0 24 24"><path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                Powered by Claude AI — understands "bonnet", "gearbox", "back light" and more
            </span>
        </p>
    </div>

    {{-- ── Intent Banner (shown after search) ────────────────────────────── --}}
    <div id="aiIntentBanner" class="hidden mt-3 bg-navy bg-opacity-5 border border-navy border-opacity-10 rounded-xl px-4 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-gold rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-navy" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <div id="aiIntentSummary" class="text-navy font-body font-500 text-sm"></div>
                <div id="aiIntentMeta" class="text-gray-400 text-xs mt-0.5 font-body"></div>
            </div>
        </div>
        <button id="aiClearBtn" class="text-xs text-gray-400 hover:text-red-500 font-body underline flex-shrink-0">Clear</button>
    </div>

    {{-- ── AI Results Grid ──────────────────────────────────────────────── --}}
    <div id="aiResultsSection" class="hidden mt-5">
        <div id="aiResultsHeader" class="flex items-center justify-between mb-4">
            <h3 class="font-display font-700 text-navy text-lg tracking-wide" id="aiResultsCount"></h3>
            <span class="text-xs text-gray-400 font-body" id="aiResultsTime"></span>
        </div>
        <div id="aiResultsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"></div>

        {{-- Also Fits section --}}
        <div id="alsoFitsSection" class="hidden mt-5">
            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4">
                <h4 class="font-body font-500 text-blue-800 text-sm mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    Platform-compatible vehicles — these may have the same part:
                </h4>
                <div id="alsoFitsList" class="flex flex-wrap gap-2"></div>
            </div>
        </div>

        {{-- Special order prompt --}}
        <div id="specialOrderPrompt" class="hidden mt-4 bg-amber-50 border border-amber-200 rounded-xl p-5 text-center">
            <div class="text-2xl mb-2">🔍</div>
            <h4 class="font-display font-700 text-navy text-base mb-1">Not in stock — we can source it</h4>
            <p class="text-gray-500 font-body text-sm mb-4">We didn't find this part in current inventory, but our team can locate and import it for you.</p>
            <a id="specialOrderWhatsapp" href="#" target="_blank"
               class="inline-flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white font-body font-500 text-sm px-5 py-2.5 rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                Request on WhatsApp
            </a>
        </div>
    </div>

    {{-- Loading skeleton --}}
    <div id="aiLoadingSkeleton" class="hidden mt-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @for($i = 0; $i < 6; $i++)
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                <div class="skeleton h-40 w-full"></div>
                <div class="p-4 space-y-2">
                    <div class="skeleton h-3 w-2/3 rounded"></div>
                    <div class="skeleton h-5 w-full rounded"></div>
                    <div class="skeleton h-3 w-1/2 rounded"></div>
                    <div class="skeleton h-8 w-full rounded-lg mt-3"></div>
                </div>
            </div>
        @endfor
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     CHATBOT WIDGET
═══════════════════════════════════════════════════════════════════════════ --}}
<div id="chatWidget" class="fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3">

    {{-- Chat bubble button --}}
    <button id="chatToggleBtn"
        class="w-14 h-14 bg-navy hover:bg-navy-dark rounded-full shadow-2xl flex items-center justify-center transition-all duration-300 group relative"
        title="Chat with Auto Zenith AI">
        {{-- Chat icon --}}
        <svg id="chatIconOpen" class="w-6 h-6 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
        </svg>
        {{-- Close icon (hidden) --}}
        <svg id="chatIconClose" class="w-6 h-6 text-gold hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        {{-- Notification dot --}}
        <span class="absolute top-1 right-1 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></span>
    </button>

    {{-- Chat panel --}}
    <div id="chatPanel"
        class="hidden w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden flex flex-col"
        style="height: 480px; max-height: 80vh;">

        {{-- Header --}}
        <div class="bg-navy px-4 py-3 flex items-center gap-3 flex-shrink-0">
            <div class="w-8 h-8 bg-gold rounded-full flex items-center justify-center">
                <span class="font-display font-700 text-navy text-sm">AZ</span>
            </div>
            <div>
                <div class="font-body font-500 text-white text-sm">Auto Zenith AI</div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2 h-2 bg-green-400 rounded-full"></span>
                    <span class="text-gray-400 text-xs font-body">Online — typically replies instantly</span>
                </div>
            </div>
            <button id="chatCloseBtn" class="ml-auto text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- Messages --}}
        <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-3">
            {{-- Welcome message --}}
            <div class="flex gap-2">
                <div class="w-7 h-7 bg-gold rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                    <span class="font-display font-700 text-navy text-xs">AZ</span>
                </div>
                <div class="bg-gray-100 rounded-2xl rounded-tl-sm px-3 py-2 max-w-[85%]">
                    <p class="text-sm font-body text-gray-700 leading-relaxed">
                        Hi! I'm the Auto Zenith assistant. I can help you find parts, check compatibility, or answer questions about our inventory. What are you looking for?
                    </p>
                </div>
            </div>
        </div>

        {{-- Quick replies --}}
        <div id="quickReplies" class="px-3 pb-2 flex gap-2 overflow-x-auto flex-shrink-0">
            <button class="quick-reply flex-shrink-0 text-xs font-body border border-gray-200 rounded-full px-3 py-1.5 hover:bg-gray-50 text-gray-600 whitespace-nowrap">
                Do you have parts for my car?
            </button>
            <button class="quick-reply flex-shrink-0 text-xs font-body border border-gray-200 rounded-full px-3 py-1.5 hover:bg-gray-50 text-gray-600 whitespace-nowrap">
                Can you source a part?
            </button>
            <button class="quick-reply flex-shrink-0 text-xs font-body border border-gray-200 rounded-full px-3 py-1.5 hover:bg-gray-50 text-gray-600 whitespace-nowrap">
                Nigeria delivery?
            </button>
        </div>

        {{-- Input --}}
        <div class="border-t border-gray-100 px-3 py-3 flex gap-2 flex-shrink-0">
            <input type="text" id="chatInput"
                placeholder="Type your question..."
                class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm font-body focus:outline-none focus:border-gold"
                maxlength="500">
            <button id="chatSendBtn"
                class="bg-navy hover:bg-navy-dark text-white rounded-xl w-9 h-9 flex items-center justify-center flex-shrink-0 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            </button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════════
     JAVASCRIPT
═══════════════════════════════════════════════════════════════════════════ --}}
<style>
    @keyframes shimmer{0%{background-position:-200% 0}100%{background-position:200% 0}}
    .skeleton{background:linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);background-size:200% 100%;animation:shimmer 1.4s infinite}
    .chat-msg-in{animation:fadeSlideIn .2s ease}
    @keyframes fadeSlideIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
    .grade-a{background:#EAF3DE;color:#27500A;border:1px solid #C0DD97}
    .grade-b{background:#E6F1FB;color:#0C447C;border:1px solid #B5D4F4}
    .grade-c{background:#FAEEDA;color:#633806;border:1px solid #FAC775}
    .grade-new{background:#EEEDFE;color:#3C3489;border:1px solid #AFA9EC}
</style>

<script>
(function () {
    const CSRF   = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const WA_US  = '15125873425';
    const WA_NG  = '2347064413764';

    // ── AI SEARCH ─────────────────────────────────────────────────────────────

    const aiInput    = document.getElementById('aiSearchInput');
    const aiBtn      = document.getElementById('aiSearchBtn');
    const aiSkeleton = document.getElementById('aiLoadingSkeleton');
    const aiSection  = document.getElementById('aiResultsSection');
    const aiGrid     = document.getElementById('aiResultsGrid');
    const aiCount    = document.getElementById('aiResultsCount');
    const aiTime     = document.getElementById('aiResultsTime');
    const aiBanner   = document.getElementById('aiIntentBanner');
    const aiSummary  = document.getElementById('aiIntentSummary');
    const aiMeta     = document.getElementById('aiIntentMeta');
    const aiSuggest  = document.getElementById('aiSuggestBox');
    const alsoFits   = document.getElementById('alsoFitsSection');
    const alsoFitsList = document.getElementById('alsoFitsList');
    const specialOrder = document.getElementById('specialOrderPrompt');
    const specialWA    = document.getElementById('specialOrderWhatsapp');

    // Autocomplete
    let suggestTimer;
    aiInput.addEventListener('input', function () {
        clearTimeout(suggestTimer);
        const term = this.value.trim();
        if (term.length < 2) { aiSuggest.classList.add('hidden'); return; }
        suggestTimer = setTimeout(() => fetchSuggestions(term), 300);
    });

    async function fetchSuggestions(term) {
        try {
            const res = await fetch(`/ai/suggest?q=${encodeURIComponent(term)}`);
            const data = await res.json();
            if (!data.suggestions?.length) { aiSuggest.classList.add('hidden'); return; }
            aiSuggest.innerHTML = data.suggestions.map(s =>
                `<button class="suggest-item w-full text-left px-4 py-2.5 text-sm font-body text-gray-700 hover:bg-gold hover:bg-opacity-10 border-b border-gray-50 last:border-0" data-value="${s.value}">${s.label}</button>`
            ).join('');
            aiSuggest.classList.remove('hidden');
            aiSuggest.querySelectorAll('.suggest-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    aiInput.value = btn.dataset.value;
                    aiSuggest.classList.add('hidden');
                    runAiSearch();
                });
            });
        } catch (e) {}
    }

    document.addEventListener('click', e => {
        if (!aiInput.contains(e.target)) aiSuggest.classList.add('hidden');
    });

    // Search trigger
    aiBtn.addEventListener('click', runAiSearch);
    aiInput.addEventListener('keydown', e => { if (e.key === 'Enter') runAiSearch(); });
    document.getElementById('aiClearBtn').addEventListener('click', clearAiSearch);

    async function runAiSearch() {
        const q = aiInput.value.trim();
        if (q.length < 3) { aiInput.focus(); return; }

        const startTime = Date.now();

        // Show loading
        aiSkeleton.classList.remove('hidden');
        aiSection.classList.add('hidden');
        aiBtn.disabled = true;
        aiBtn.innerHTML = `<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Searching...`;

        try {
            const currency = document.querySelector('[name="currency"]')?.value || 'USD';
            const location = document.querySelector('[name="location"]:checked')?.value || '';

            const res = await fetch('/ai/search', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                },
                body: JSON.stringify({ q, currency, location }),
            });

            const data = await res.json();
            const elapsed = ((Date.now() - startTime) / 1000).toFixed(1);

            renderAiResults(data, elapsed);

        } catch (err) {
            aiGrid.innerHTML = `<div class="col-span-3 text-center py-8 text-red-500 font-body text-sm">Search failed. Please try again or use the dropdown filters.</div>`;
            aiSection.classList.remove('hidden');
        } finally {
            aiSkeleton.classList.add('hidden');
            aiBtn.disabled = false;
            aiBtn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg> AI SEARCH`;
        }
    }

    function renderAiResults(data, elapsed) {
        // Intent banner
        if (data.intent?.intent_summary) {
            aiSummary.textContent = data.intent.intent_summary;
            const conf = data.intent.confidence ? `Confidence: ${Math.round(data.intent.confidence * 100)}%` : '';
            aiMeta.textContent = [conf, data.intent.side ? `Side: ${data.intent.side}` : '', data.intent.body_style || ''].filter(Boolean).join(' · ');
            aiBanner.classList.remove('hidden');
        }

        // Result count
        const count = data.result_count || 0;
        aiCount.textContent = count > 0 ? `${count} result${count !== 1 ? 's' : ''} found` : 'No results found';
        aiTime.textContent  = `${elapsed}s`;

        // Results grid
        if (count > 0) {
            aiGrid.innerHTML = data.results.map(part => renderPartCard(part)).join('');
            specialOrder.classList.add('hidden');
        } else {
            aiGrid.innerHTML = '';
            if (data.special_order_prompt) {
                specialOrder.classList.remove('hidden');
                const msg = encodeURIComponent(`Hi, I'm looking for a part that's not listed: ${aiInput.value}. Can you help source it?`);
                specialWA.href = `https://wa.me/${WA_US}?text=${msg}`;
            }
        }

        // Also fits
        if (data.also_fits?.length) {
            alsoFitsList.innerHTML = data.also_fits.map(af =>
                `<button onclick="searchAlsoFits('${af.brand}','${af.model}')"
                    class="text-xs font-body font-500 px-3 py-1.5 bg-white border border-blue-200 rounded-full text-blue-700 hover:bg-blue-50 transition-colors">
                    ${af.brand} ${af.model}${af.year_from ? ' ' + af.year_from + '–' + af.year_to : ''}
                </button>`
            ).join('');
            alsoFits.classList.remove('hidden');
        } else {
            alsoFits.classList.add('hidden');
        }

        aiSection.classList.remove('hidden');

        // Scroll to results
        aiSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function renderPartCard(part) {
        const gradeClass = { A: 'grade-a', B: 'grade-b', C: 'grade-c', New: 'grade-new' }[part.condition_grade] || 'grade-b';
        const gradeLabel = { A: 'Grade A', B: 'Grade B', C: 'Grade C', New: 'New OEM' }[part.condition_grade] || 'Grade B';
        const locFlag    = part.location?.includes('Nigeria') || part.location?.includes('Lagos') ? '🇳🇬' :
                           part.location?.includes('Ghana') ? '🇬🇭' : '🇺🇸';
        const phone      = part.location?.includes('Nigeria') || part.location?.includes('Lagos') ? WA_NG : WA_US;
        const yearRange  = part.year_from === part.year_to ? part.year_from : `${part.year_from}–${part.year_to}`;
        const waMsg      = encodeURIComponent(`Hi, I'm enquiring about: ${part.part_name} for ${part.brand} ${part.model} ${yearRange}. Part ID: ${part.part_code}. Price: ${part.price_display || '$' + part.price_usd}. Is this available?`);
        const thumb      = part.thumb
            ? `<img src="${part.thumb}" alt="${part.part_name}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" loading="lazy">`
            : `<div class="w-full h-full flex items-center justify-center text-gray-300 text-xs font-body">No photo</div>`;

        return `
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden flex flex-col chat-msg-in hover:shadow-md transition-shadow">
            <a href="/parts/${part.id}" class="block relative bg-gray-100 aspect-[4/3] overflow-hidden group">
                ${thumb}
                <div class="absolute top-2 left-2">
                    <span class="text-xs font-body font-500 px-2 py-1 rounded-full ${gradeClass}">${gradeLabel}</span>
                </div>
                ${part.photos_count > 1 ? `<div class="absolute bottom-2 right-2 bg-black bg-opacity-60 text-white text-xs font-body px-2 py-0.5 rounded-full">📷 ${part.photos_count}</div>` : ''}
            </a>
            <div class="p-4 flex flex-col flex-1">
                <div class="text-xs font-body text-gray-400 uppercase tracking-wider mb-1">${part.brand} ${part.model} · ${yearRange}</div>
                <h4 class="font-display font-700 text-navy text-base leading-tight mb-1">
                    <a href="/parts/${part.id}" class="hover:text-blue-700">${part.part_name}${part.side && part.side !== 'N/A' ? ' <span class="text-gray-400 font-600 text-sm">· ' + part.side + '</span>' : ''}</a>
                </h4>
                <div class="flex gap-3 text-xs font-body text-gray-500 mb-2">
                    ${part.mileage ? `<span>🔢 ${parseInt(part.mileage).toLocaleString()} mi</span>` : ''}
                    ${part.oem_part_number ? `<span class="font-mono">${part.oem_part_number}</span>` : ''}
                </div>
                <div class="bg-blue-50 border border-blue-100 rounded-lg px-2.5 py-1.5 text-xs font-body text-blue-700 mb-3">
                    ✓ ${part.compatibility_label}
                </div>
                <div class="mt-auto">
                    <div class="flex items-end justify-between mb-3">
                        <div class="font-display font-800 text-navy text-xl">${part.price_display || '$' + parseFloat(part.price_usd).toFixed(2)}</div>
                        <div class="text-right text-xs text-gray-400 font-body">${locFlag} ${part.location?.split(' ')[0]}</div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <a href="/parts/${part.id}" class="text-center text-xs font-body font-500 text-navy border border-navy rounded-lg px-2 py-2 hover:bg-navy hover:text-white transition-colors">Details</a>
                        <a href="https://wa.me/${phone}?text=${waMsg}" target="_blank"
                           class="flex items-center justify-center gap-1 text-xs font-body font-500 bg-green-500 hover:bg-green-600 text-white rounded-lg px-2 py-2 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                            WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>`;
    }

    function clearAiSearch() {
        aiInput.value = '';
        aiBanner.classList.add('hidden');
        aiSection.classList.add('hidden');
        aiSkeleton.classList.add('hidden');
        alsoFits.classList.add('hidden');
        specialOrder.classList.add('hidden');
        aiGrid.innerHTML = '';
        aiInput.focus();
    }

    // "Also Fits" click — run new search for that vehicle
    window.searchAlsoFits = function(brand, model) {
        aiInput.value = `${brand} ${model} ${document.getElementById('yearSelect')?.value || ''} ${aiInput.value.split(' ').slice(-2).join(' ')}`.trim();
        runAiSearch();
    };

    // ── CHATBOT ───────────────────────────────────────────────────────────────

    const chatPanel    = document.getElementById('chatPanel');
    const chatToggle   = document.getElementById('chatToggleBtn');
    const chatClose    = document.getElementById('chatCloseBtn');
    const chatMessages = document.getElementById('chatMessages');
    const chatInput    = document.getElementById('chatInput');
    const chatSend     = document.getElementById('chatSendBtn');
    const chatOpenIcon = document.getElementById('chatIconOpen');
    const chatCloseIcon= document.getElementById('chatIconClose');

    let chatHistory = [];
    let chatOpen    = false;

    chatToggle.addEventListener('click', () => toggleChat());
    chatClose.addEventListener('click',  () => toggleChat(false));

    function toggleChat(force) {
        chatOpen = force !== undefined ? force : !chatOpen;
        chatPanel.classList.toggle('hidden', !chatOpen);
        chatOpenIcon.classList.toggle('hidden', chatOpen);
        chatCloseIcon.classList.toggle('hidden', !chatOpen);
        if (chatOpen) chatInput.focus();
    }

    chatSend.addEventListener('click', sendChat);
    chatInput.addEventListener('keydown', e => { if (e.key === 'Enter') sendChat(); });

    // Quick replies
    document.querySelectorAll('.quick-reply').forEach(btn => {
        btn.addEventListener('click', () => {
            chatInput.value = btn.textContent.trim();
            sendChat();
        });
    });

    async function sendChat() {
        const msg = chatInput.value.trim();
        if (!msg) return;

        chatInput.value = '';
        addChatMessage('user', msg);
        chatHistory.push({ role: 'user', content: msg });

        // Show typing indicator
        const typingId = addTypingIndicator();

        try {
            const res = await fetch('/ai/chat', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({
                    message:      msg,
                    history:      chatHistory.slice(-8), // last 4 turns
                    page_context: window.AZ_PAGE_CONTEXT || {},
                }),
            });

            removeTypingIndicator(typingId);
            const data = await res.json();

            addChatMessage('assistant', data.reply);
            chatHistory.push({ role: 'assistant', content: data.reply });

            // WhatsApp handoff button
            if (data.whatsapp_prompt) {
                addWhatsAppPrompt(msg);
            }

            // Auto-search trigger
            if (data.action === 'search' && data.search_query) {
                setTimeout(() => {
                    aiInput.value = data.search_query;
                    toggleChat(false);
                    runAiSearch();
                }, 800);
            }

        } catch (e) {
            removeTypingIndicator(typingId);
            addChatMessage('assistant', "I'm having trouble connecting. Please try WhatsApp for instant help.");
            addWhatsAppPrompt(msg);
        }
    }

    function addChatMessage(role, text) {
        const isUser = role === 'user';
        const div = document.createElement('div');
        div.className = `flex gap-2 chat-msg-in ${isUser ? 'flex-row-reverse' : ''}`;
        div.innerHTML = `
            ${!isUser ? `<div class="w-7 h-7 bg-gold rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"><span class="font-display font-700 text-navy text-xs">AZ</span></div>` : ''}
            <div class="${isUser ? 'bg-navy text-white' : 'bg-gray-100 text-gray-700'} rounded-2xl ${isUser ? 'rounded-tr-sm' : 'rounded-tl-sm'} px-3 py-2 max-w-[85%]">
                <p class="text-sm font-body leading-relaxed">${text.replace(/\n/g, '<br>')}</p>
            </div>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return div;
    }

    function addWhatsAppPrompt(context) {
        const div = document.createElement('div');
        div.className = 'flex justify-center chat-msg-in';
        const msg = encodeURIComponent(`Hi Auto Zenith, I need help: ${context}`);
        div.innerHTML = `
            <a href="https://wa.me/${WA_US}?text=${msg}" target="_blank"
               class="flex items-center gap-1.5 text-xs font-body font-500 bg-green-500 hover:bg-green-600 text-white rounded-full px-4 py-2 transition-colors mt-1">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/></svg>
                Continue on WhatsApp
            </a>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    let typingCounter = 0;
    function addTypingIndicator() {
        const id = `typing-${typingCounter++}`;
        const div = document.createElement('div');
        div.id = id;
        div.className = 'flex gap-2 chat-msg-in';
        div.innerHTML = `
            <div class="w-7 h-7 bg-gold rounded-full flex items-center justify-center flex-shrink-0"><span class="font-display font-700 text-navy text-xs">AZ</span></div>
            <div class="bg-gray-100 rounded-2xl rounded-tl-sm px-4 py-3">
                <div class="flex gap-1 items-center h-4">
                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                    <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                </div>
            </div>`;
        chatMessages.appendChild(div);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        return id;
    }

    function removeTypingIndicator(id) {
        document.getElementById(id)?.remove();
    }

})();
</script>
