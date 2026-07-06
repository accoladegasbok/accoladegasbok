<?php
// FILE: app/Support/PartMedia.php
//
// Single source of truth for "which photo do we show for this part?"
// Used on both the admin/staff screens and the customer-facing catalog.
//
// Usage:
//   PartMedia::photoUrl($part->photos)          // first real photo, or default
//   PartMedia::allPhotoUrls($part->photos)      // full gallery, or [default]
//   PartMedia::hasRealPhoto($part->photos)      // true/false

namespace App\Support;

class PartMedia
{
    // Path relative to /public — put the AutoZenith "photo coming soon"
    // image here: public/images/parts-photo-coming-soon.jpg
    const DEFAULT_PHOTO_PATH = 'images/parts-photo-coming-soon.jpg';

    /**
     * Decode the `photos` column (JSON array of storage paths, or '[]'/null)
     * into a plain PHP array of storage-relative paths.
     */
    private static function decode($photosJson): array
    {
        if (empty($photosJson)) return [];
        $decoded = is_array($photosJson) ? $photosJson : json_decode($photosJson, true);
        return is_array($decoded) ? array_values(array_filter($decoded)) : [];
    }

    /**
     * True if this part has at least one real uploaded photo.
     */
    public static function hasRealPhoto($photosJson): bool
    {
        return count(self::decode($photosJson)) > 0;
    }

    /**
     * The single photo to show on a card/thumbnail — first uploaded
     * photo if one exists, otherwise the AutoZenith default image.
     */
    public static function photoUrl($photosJson): string
    {
        $photos = self::decode($photosJson);
        if (!empty($photos)) {
            return \Storage::disk('public')->url($photos[0]);
        }
        return asset(self::DEFAULT_PHOTO_PATH);
    }

    /**
     * Full gallery for a detail page — all uploaded photos, or a
     * single-item array with just the default image if none exist.
     */
    public static function allPhotoUrls($photosJson): array
    {
        $photos = self::decode($photosJson);
        if (!empty($photos)) {
            return array_map(fn($p) => \Storage::disk('public')->url($p), $photos);
        }
        return [asset(self::DEFAULT_PHOTO_PATH)];
    }
}
