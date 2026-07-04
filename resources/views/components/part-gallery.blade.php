{{--
    FILE LOCATION: resources/views/components/part-gallery.blade.php

    Reusable component — shows ALL of a part's uploaded photos, or a
    single AutoZenith "photo coming soon" default if none exist yet.
    Use this on the part detail/show page (customer-facing).

    USAGE:

        <x-part-gallery :photos="$part->photos" :name="$part->part_name" />
--}}

@props([
    'photos' => null,
    'name'   => 'Part photo',
])

@php
    $decoded = [];
    if (!empty($photos)) {
        $decoded = is_array($photos) ? $photos : (json_decode($photos, true) ?: []);
        $decoded = array_values(array_filter($decoded));
    }

    $hasRealPhotos = count($decoded) > 0;

    $urls = $hasRealPhotos
        ? array_map(fn($p) => \Storage::disk('public')->url($p), $decoded)
        : [asset('images/parts-photo-coming-soon.jpg')];
@endphp

<div class="part-gallery">
    @foreach ($urls as $url)
        <img
            src="{{ $url }}"
            alt="{{ $name }}"
            class="part-gallery__img {{ $hasRealPhotos ? '' : 'part-gallery__img--placeholder' }}"
            loading="lazy"
        >
    @endforeach

    @unless ($hasRealPhotos)
        <p class="part-gallery__note">Photo coming soon — contact us for a live photo if urgent.</p>
    @endunless
</div>
