<?php
// FILE: config/media.php
//
// The URL prefix used to serve uploaded photos/videos/proof files.
//
// LOCAL (Laragon): leave MEDIA_URL_PREFIX unset in .env — defaults to
// "storage", which is the standard Laravel storage:link symlink path.
//
// LIVE SERVER: .env has MEDIA_URL_PREFIX=media, because the real
// storage/ folder collides with the web-facing /storage path after
// the public_html flatten earlier this project — so live uses a
// renamed symlink at public_html/media instead.

return [
    'prefix' => env('MEDIA_URL_PREFIX', 'storage'),
];
