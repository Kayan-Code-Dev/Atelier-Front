<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Central\RecruitmentSetting;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecruitmentSettingsController extends Controller
{
    public function show(): JsonResponse
    {
        return ApiResponse::success($this->payload(RecruitmentSetting::current()));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'notify_email' => ['nullable', 'email', 'max:255'],
            'accepting_applications' => ['sometimes', 'boolean'],
            'honeypot_enabled' => ['sometimes', 'boolean'],
            'cv_max_kilobytes' => ['sometimes', 'integer', 'min:512', 'max:20480'],
        ]);

        $settings = RecruitmentSetting::current();
        $settings->fill($data);
        $settings->save();

        return ApiResponse::success($this->payload($settings->fresh()), 'تم حفظ إعدادات التوظيف');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(RecruitmentSetting $settings): array
    {
        return [
            'notify_email' => $settings->notify_email,
            'accepting_applications' => $settings->accepting_applications,
            'honeypot_enabled' => $settings->honeypot_enabled,
            'cv_max_kilobytes' => $settings->cv_max_kilobytes,
        ];
    }
}
