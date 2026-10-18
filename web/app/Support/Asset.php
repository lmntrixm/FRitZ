<?php

namespace App\Support;

/** Public asset URL with a cache-busting version (no build step on the host). */
class Asset
{
    public static function url(string $path): string
    {
        $file = public_path($path);

        return asset($path).(is_file($file) ? '?v='.filemtime($file) : '');
    }
}
