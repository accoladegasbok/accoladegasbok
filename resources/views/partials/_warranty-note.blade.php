{{-- FILE: resources/views/partials/_warranty-note.blade.php
     Short warranty line for the part search page and the part page.
     Usage: @include('partials._warranty-note', ['category' => $part->part_category ?? null])
     The wording comes from App\Support\Warranty, the one source for every page. --}}
<div class="flex items-start gap-2 text-xs font-body text-amber-900 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2.5">
    <span class="mt-0.5">🛡️</span>
    <span>
        <strong>Warranty:</strong> {{ \App\Support\Warranty::partNote($category ?? null) }}
        <a href="{{ \App\Support\Warranty::URL_PATH }}" class="underline font-600 whitespace-nowrap">Full terms</a>
    </span>
</div>
