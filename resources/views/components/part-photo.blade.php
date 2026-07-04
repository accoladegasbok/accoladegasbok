{{--
    FILE LOCATION: resources/views/components/part-photo.blade.php

    Reusable component — shows a part's first uploaded photo, or the
    AutoZenith "photo coming soon" default if none exist yet.

    USAGE (drop this into any existing card/list view, e.g. your
    customer catalog card or admin inventory row):

        <x-part-photo :photos="$part->photos" :name="$part->part_name" />

    Optional class override:

        <x-part-photo :photos="$part->photos" :name="$part->part_name" class="w-32 h-32" />
--}}

@props([
    'photos' => null,
    'name'   => 'Part photo',
    'class'  => 'part-photo',
])

@php
    $decoded = [];
    if (!empty($photos)) {
        $decoded = is_array($photos) ? $photos : (json_decode($photos, true) ?: []);
        $decoded = array_values(array_filter($decoded));
    }

    $hasRealPhoto = count($decoded) > 0;

    $src = $hasRealPhoto
        ? \Storage::disk('public')->url($decoded[0])
        : asset('images/parts-photo-coming-soon.jpg');
@endphp

<img
    src="{{ $src }}"
    alt="{{ $name }}"
    class="{{ $class }} {{ $hasRealPhoto ? '' : 'part-photo--placeholder' }}"
    loading="lazy"
>
