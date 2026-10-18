<?php

namespace App\Support;

use App\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stores uploads in public/media (no storage symlink on the host).
 * Photos are scaled to 1600px at most and saved as WebP, so a 3 MB camera JPEG
 * becomes a ~200 KB catalog image; PDFs are kept as uploaded.
 */
class MediaStore
{
    private const MAX_SIDE = 1600;

    public static function image(UploadedFile $file, string $dir, ?string $alt = null): Media
    {
        $src = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $src) {
            return self::raw($file, $dir, 'image', $alt);
        }
        [$w, $h] = [imagesx($src), imagesy($src)];
        $scale = min(1, self::MAX_SIDE / max($w, $h));
        [$nw, $nh] = [max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale))];
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagewebp($dst, null, 82);
        $bytes = (string) ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);

        $path = trim($dir, '/').'/'.self::name($file).'.webp';
        Storage::disk('public')->put($path, $bytes);

        return Media::create([
            'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime' => 'image/webp',
            'bytes' => strlen($bytes), 'width' => $nw, 'height' => $nh, 'sha256' => hash('sha256', $bytes),
            'kind' => 'image', 'alt' => $alt, 'uploaded_by' => auth()->id(),
        ]);
    }

    public static function raw(UploadedFile $file, string $dir, string $kind, ?string $alt = null): Media
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $path = $file->storeAs(trim($dir, '/'), self::name($file).'.'.$ext, 'public');

        return Media::create([
            'path' => $path, 'original_name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType() ?: 'application/octet-stream',
            'bytes' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath()),
            'kind' => $kind, 'alt' => $alt, 'uploaded_by' => auth()->id(),
        ]);
    }

    /** Readable, collision-free file name: «hyp-e7-front-k3f9». */
    private static function name(UploadedFile $file): string
    {
        $base = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';

        return Str::limit($base, 60, '').'-'.Str::lower(Str::random(4));
    }
}
