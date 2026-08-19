<?php

namespace App\Services\Tenant;

use App\Models\Central\Tenant;
use App\Models\Tenant\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class TenantUserAvatarService
{
    private const DISK = 'public';

    private const MAX_BYTES = 2_500_000;

    /**
     * Encode the uploaded image as a data URL for tenant-DB persistence.
     */
    public function storeAsDataUrl(UploadedFile $file): string
    {
        $mime = strtolower((string) ($file->getMimeType() ?: 'image/jpeg'));
        if (! str_starts_with($mime, 'image/')) {
            throw new RuntimeException('Invalid image mime type.');
        }

        $path = $file->getRealPath();
        if ($path === false || $path === '') {
            throw new RuntimeException('Unable to read uploaded image.');
        }

        $size = filesize($path);
        if ($size !== false && $size > self::MAX_BYTES) {
            throw new RuntimeException('Image exceeds maximum allowed size.');
        }

        $binary = file_get_contents($path);
        if ($binary === false || $binary === '') {
            throw new RuntimeException('Unable to read uploaded image.');
        }

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    /**
     * @deprecated Prefer storeAsDataUrl() + avatar_data column.
     */
    public function store(Tenant $tenant, User $user, UploadedFile $file): string
    {
        return $this->storeAsDataUrl($file);
    }

    public function deleteIfOwned(Tenant $tenant, ?string $path): void
    {
        if ($path === null || trim($path) === '' || str_starts_with($path, 'data:')) {
            return;
        }

        if (! $this->pathBelongsToTenant($tenant, $path)) {
            return;
        }

        if (Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function urlForUser(User $user, ?Tenant $tenant): ?string
    {
        $data = $user->avatar_data ?? null;
        if (is_string($data) && trim($data) !== '') {
            return $data;
        }

        $path = $user->avatar_path ?? null;
        if (is_string($path) && str_starts_with($path, 'data:')) {
            return $path;
        }

        return $this->url($path, $tenant);
    }

    public function url(?string $path, ?Tenant $tenant): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        if (str_starts_with($path, 'data:')) {
            return $path;
        }

        if ($tenant !== null && ! $this->pathBelongsToTenant($tenant, $path) && ! $this->isLegacyPath($path)) {
            return null;
        }

        if (! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->url($path);
    }

    public function pathBelongsToTenant(Tenant $tenant, string $path): bool
    {
        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        return str_starts_with($normalized, 'tenants/'.$tenant->id.'/');
    }

    private function isLegacyPath(string $path): bool
    {
        $normalized = ltrim(str_replace('\\', '/', $path), '/');

        return ! str_starts_with($normalized, 'tenants/');
    }

    public function assertTenantContext(Tenant $tenant): void
    {
        if ($tenant->id <= 0) {
            throw new RuntimeException('Tenant context is required for avatar storage.');
        }
    }
}
