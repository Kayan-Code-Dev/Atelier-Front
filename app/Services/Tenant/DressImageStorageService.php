<?php

namespace App\Services\Tenant;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DressImageStorageService
{
    private const DISK = 'public';

    private const MAX_BYTES = 5_000_000;

    public function __construct(private readonly TenantContext $tenantContext) {}

    /**
     * Store uploaded image and return a public URL (or data URL fallback).
     */
    public function store(UploadedFile $file): string
    {
        $mime = strtolower((string) ($file->getMimeType() ?: 'image/jpeg'));
        if (! str_starts_with($mime, 'image/')) {
            throw new RuntimeException('Invalid image mime type.');
        }

        $size = $file->getSize();
        if (is_int($size) && $size > self::MAX_BYTES) {
            throw new RuntimeException('Image exceeds maximum allowed size.');
        }

        $tenant = $this->tenantContext->tenant();
        $tenantKey = $tenant !== null && $tenant->id > 0
            ? (string) $tenant->id
            : 'shared';

        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg');
        $filename = Str::uuid()->toString().'.'.$extension;
        $directory = 'tenants/'.$tenantKey.'/dresses';

        Storage::disk(self::DISK)->putFileAs($directory, $file, $filename);

        return $directory.'/'.$filename;
    }

    /**
     * Normalize a client-provided data URL or remote URL for persistence.
     */
    public function normalizePath(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new RuntimeException('Image path cannot be empty.');
        }

        if (str_starts_with($trimmed, 'data:image/')) {
            if (strlen($trimmed) > self::MAX_BYTES * 2) {
                throw new RuntimeException('Image exceeds maximum allowed size.');
            }

            return $trimmed;
        }

        return $trimmed;
    }

    public function url(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (str_starts_with($path, 'data:') || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (str_starts_with($path, '/storage/')) {
            return $path;
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            return Storage::disk(self::DISK)->url($path);
        }

        return $path;
    }
}
