<?php
// FILE: config/media.php
//
// Media/photo URL prefix used across the app when building public URLs
// for uploaded photos (parts, invoice payment proofs, etc).
//
// IMPORTANT — permanent fix for LiteSpeed symlink blocking (discovered
// July 2026): some LiteSpeed configurations silently 403 requests that
// traverse a symlink (php_html_admin_root protection), even when the
// symlink itself is valid and correctly created by `storage:link`.
// This breaks the standard Laravel `storage/xxx` public URL pattern
// on such hosts.
//
// Fix: serve media directly from the real path (storage/app/public)
// instead of through the symlink, bypassing the block entirely. This
// is safe because Laravel's local disk driver stores files at
// storage/app/public/... regardless of the prefix used to serve them.
//
// This setting survives every redeploy since it's version-controlled —
// no more manual "fix the symlink" step needed after each deploy.

return [
    'prefix' => 'storage/app/public',
];
