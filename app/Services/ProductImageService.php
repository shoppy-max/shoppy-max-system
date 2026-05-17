<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProductImageService
{
    private const IMAGE_MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
    ];

    public function upload(UploadedFile $file, string $directory): string
    {
        $path = $file->store($this->cleanDirectory($directory), [
            'disk' => $this->disk(),
            'visibility' => 'private',
        ]);

        if (! $path) {
            throw new \RuntimeException('Image upload failed: the file could not be stored.');
        }

        return $path;
    }

    public function uploadFromUrl(?string $url, string $directory): ?string
    {
        $url = trim((string) $url);
        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        try {
            $response = Http::timeout((int) config('product-images.remote_upload_timeout_seconds', 15))
                ->withHeaders(['Accept' => 'image/*'])
                ->get($url);
        } catch (Throwable $e) {
            Log::warning('Product image URL download failed.', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $contentType = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        if (! isset(self::IMAGE_MIME_EXTENSIONS[$contentType])) {
            return null;
        }

        $body = $response->body();
        $maxBytes = max((int) config('product-images.max_upload_kilobytes', 2048), 1) * 1024;
        if (strlen($body) > $maxBytes) {
            return null;
        }

        $path = $this->cleanDirectory($directory).'/'.(string) Str::uuid().'.'.self::IMAGE_MIME_EXTENSIONS[$contentType];

        $stored = Storage::disk($this->disk())->put($path, $body, [
            'visibility' => 'private',
            'ContentType' => $contentType,
        ]);

        return $stored ? $path : null;
    }

    public function url(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return null;
        }

        if ($this->isAlreadyResolvableUrl($path)) {
            return $path;
        }

        try {
            return Storage::disk($this->disk())->temporaryUrl(
                $path,
                now()->addMinutes(max((int) config('product-images.temporary_url_minutes', 120), 1))
            );
        } catch (Throwable $e) {
            Log::warning('Product image signed URL generation failed.', [
                'disk' => $this->disk(),
                'path' => $path,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function disk(): string
    {
        return (string) config('product-images.disk', 'b2');
    }

    private function cleanDirectory(string $directory): string
    {
        $directory = trim($directory, '/');

        return $directory !== '' ? $directory : 'products';
    }

    private function isAlreadyResolvableUrl(string $path): bool
    {
        return Str::startsWith($path, ['http://', 'https://', '/', 'data:']);
    }
}
