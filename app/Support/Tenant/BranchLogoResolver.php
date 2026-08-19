<?php

namespace App\Support\Tenant;

use App\Models\Tenant\Branch;

final class BranchLogoResolver
{
    public static function url(?Branch $branch): string
    {
        if ($branch === null) {
            return '';
        }

        $logo = trim((string) ($branch->logo ?? ''));
        if ($logo !== '') {
            return $logo;
        }

        $image = trim((string) ($branch->image ?? ''));
        if ($image !== '') {
            return $image;
        }

        return trim((string) ($branch->cover ?? ''));
    }

    /**
     * @return array{id:int,name:string,phone:string,address:string,logo_url:string}|null
     */
    public static function present(?Branch $branch): ?array
    {
        if ($branch === null) {
            return null;
        }

        return [
            'id' => (int) $branch->id,
            'name' => (string) $branch->name,
            'phone' => (string) ($branch->phone ?? ''),
            'address' => (string) ($branch->address ?? ''),
            'logo_url' => self::url($branch),
        ];
    }
}
