<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * WatermarkService
 *
 * Applies a text watermark to uploaded images using PHP's built-in GD library.
 * No additional Composer packages required — GD ships with every standard PHP build.
 *
 * Usage:
 *   $service = app(WatermarkService::class);
 *   $storedPath = $service->processAndStore($uploadedFile, 'ads/images');
 */
class WatermarkService
{
    /**
     * The watermark text drawn across every image.
     * Change this in .env:  WATERMARK_TEXT="© YourBrand"
     */
    protected string $watermarkText;

    /**
     * Opacity of the watermark (0 = transparent, 100 = opaque).
     * Controlled via env: WATERMARK_OPACITY=40
     */
    protected int $opacity;

    public function __construct()
    {
        $this->watermarkText = config('watermark.text', '© SBRAI Solutions');
        $this->opacity       = (int) config('watermark.opacity', 40);
    }

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * Accepts an uploaded file (UploadedFile or path string), applies the
     * watermark, saves to $disk under $directory, and returns the stored path.
     *
     * @param  \Illuminate\Http\UploadedFile|string  $file
     * @param  string  $directory  e.g. 'ads/images'
     * @param  string  $disk       Laravel filesystem disk
     * @return string  stored path (relative to disk root)
     */
    public function processAndStore($file, string $directory = 'ads', string $disk = 'public'): string
    {
        // 1. Load source image
        $sourcePath = is_string($file) ? $file : $file->getRealPath();
        $mimeType   = is_string($file) ? mime_content_type($file) : $file->getMimeType();

        $src = $this->loadImage($sourcePath, $mimeType);

        // 2. Apply watermark
        $watermarked = $this->applyWatermark($src);

        // 3. Encode to JPEG bytes
        ob_start();
        imagejpeg($watermarked, null, 90);
        $imageData = ob_get_clean();

        // Free memory
        imagedestroy($src);
        imagedestroy($watermarked);

        // 4. Store
        $filename = Str::uuid() . '.jpg';
        $path     = $directory . '/' . $filename;
        Storage::disk($disk)->put($path, $imageData);

        return $path;
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Load a GD resource from any common image mime type.
     */
    private function loadImage(string $path, ?string $mimeType)
    {
        return match (true) {
            str_contains((string) $mimeType, 'png')  => imagecreatefrompng($path),
            str_contains((string) $mimeType, 'gif')  => imagecreatefromgif($path),
            str_contains((string) $mimeType, 'webp') => imagecreatefromwebp($path),
            default                                   => imagecreatefromjpeg($path),
        };
    }

    /**
     * Draw a semi-transparent tiled text watermark over the image.
     * The watermark is tiled diagonally so it covers the full canvas.
     */
    private function applyWatermark($src)
    {
        $width  = imagesx($src);
        $height = imagesy($src);

        // Create a true-colour canvas with alpha support
        $dst = imagecreatetruecolor($width, $height);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);

        // Copy source onto destination
        imagecopy($dst, $src, 0, 0, 0, 0, $width, $height);
        imagealphablending($dst, true);

        // ── Font setup ──────────────────────────────────────────────────────
        // We use a TTF font if available; fall back to GD built-in font 5.
        $fontPath = $this->resolveFontPath();
        $fontSize  = max(14, (int) ($width * 0.035)); // scales with image width

        // Compute colour with alpha channel (GD alpha: 0=opaque, 127=transparent)
        $gdAlpha = (int) round(127 - ($this->opacity / 100 * 127));
        $colour  = imagecolorallocatealpha($dst, 255, 255, 255, $gdAlpha);

        // ── Tiling ──────────────────────────────────────────────────────────
        $angle   = -30;   // degrees
        $stepX   = (int) ($width  * 0.4);
        $stepY   = (int) ($height * 0.25);

        for ($y = -$stepY; $y < $height + $stepY; $y += $stepY) {
            for ($x = -$stepX; $x < $width + $stepX; $x += $stepX) {
                if ($fontPath) {
                    imagettftext($dst, $fontSize, $angle, $x, $y, $colour, $fontPath, $this->watermarkText);
                } else {
                    // Fallback: built-in font, no rotation
                    imagestring($dst, 5, $x, $y, $this->watermarkText, $colour);
                }
            }
        }

        return $dst;
    }

    /**
     * Return path to a bundled TTF font, or null to fall back to GD built-in.
     */
    private function resolveFontPath(): ?string
    {
        $custom = storage_path('app/fonts/watermark.ttf');
        if (file_exists($custom)) {
            return $custom;
        }

        // Try common system fonts (Linux servers)
        $systemFonts = [
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
        ];

        foreach ($systemFonts as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null; // Use GD built-in
    }
}
