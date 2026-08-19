<?php

namespace App\Services\Mail;

use App\Models\Central\PlanRequest;
use App\Models\Central\RecruitmentApplication;
use App\Models\Central\SuperAdmin;
use App\Models\Central\Tenant;
use App\Models\Central\TenantUserDirectory;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PlatformMailService
{
    public function sendPlanSignupOtp(string $email, string $greeting, string $otp): void
    {
        $html = view('emails.otp.plan-signup', [
            'greeting' => $greeting,
            'otp' => $otp,
        ])->render();

        $this->sendHtml(
            to: [$email],
            subject: 'رمز التحقق لطلب باقة DressnMore',
            html: $html,
        );
    }

    public function notifyAdminsPlanRequest(PlanRequest $planRequest): void
    {
        $planRequest->loadMissing(['plan', 'paymentGateway']);

        $recipients = SuperAdmin::query()
            ->where('status', 'active')
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $fallback = strtolower(trim((string) env('PLATFORM_ADMIN_EMAIL', '')));
        if ($fallback !== '' && ! in_array($fallback, $recipients, true)) {
            $recipients[] = $fallback;
        }

        if ($recipients === []) {
            return;
        }

        $html = view('emails.admin.plan-request-review', [
            'requestId' => (int) $planRequest->id,
            'name' => (string) $planRequest->name,
            'email' => (string) $planRequest->email,
            'phone' => (string) $planRequest->phone,
            'planTitle' => (string) ($planRequest->plan?->name ?? '—'),
            'gatewayName' => (string) ($planRequest->paymentGateway?->name ?? '—'),
            'reference' => (string) ($planRequest->payment_reference ?? '—'),
        ])->render();

        $this->sendHtml(
            to: $recipients,
            subject: 'طلب باقة جديد #'.$planRequest->id.' — DressnMore',
            html: $html,
        );
    }

    public function sendPlatformBroadcast(string $email, string $title, string $message, ?string $tenantName = null): void
    {
        $html = view('emails.broadcast.platform-message', [
            'title' => $title,
            'message' => $message,
            'tenantName' => $tenantName,
        ])->render();

        $this->sendHtml(
            to: [$email],
            subject: $title,
            html: $html,
        );
    }

    public function sendPlanRequestApprovedCredentials(
        string $email,
        string $name,
        string $password,
        string $hostnameLabel,
        string $planName,
    ): void {
        $loginUrl = 'https://'.preg_replace('#^https?://#i', '', $hostnameLabel);
        $loginUrl = rtrim($loginUrl, '/').'/login';

        $html = view('emails.plan-request-approved', [
            'name' => $name !== '' ? $name : 'عميلنا العزيز',
            'email' => $email,
            'password' => $password,
            'loginUrl' => $loginUrl,
            'planName' => $planName !== '' ? $planName : 'باقتك',
        ])->render();

        $this->sendHtml(
            to: [$email],
            subject: 'تم تفعيل حسابك على DressnMore',
            html: $html,
        );
    }

    public function sendRecruitmentApplicantConfirmation(
        string $email,
        string $name,
        string $applicationNumber,
        ?string $jobTitle,
    ): void {
        $html = view('emails.careers.application-received', [
            'name' => $name !== '' ? $name : 'المتقدم',
            'applicationNumber' => $applicationNumber,
            'jobTitle' => $jobTitle,
        ])->render();

        $this->sendHtml(
            to: [$email],
            subject: 'وصلنا طلبك — DressnMore',
            html: $html,
        );
    }

    public function notifyAdminsRecruitmentApplication(RecruitmentApplication $application, ?string $overrideEmail = null): void
    {
        $application->loadMissing('job');

        $recipients = SuperAdmin::query()
            ->where('status', 'active')
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ([$overrideEmail, env('PLATFORM_ADMIN_EMAIL', '')] as $extra) {
            $normalized = strtolower(trim((string) $extra));
            if ($normalized !== '' && filter_var($normalized, FILTER_VALIDATE_EMAIL) && ! in_array($normalized, $recipients, true)) {
                $recipients[] = $normalized;
            }
        }

        if ($recipients === []) {
            return;
        }

        $html = view('emails.admin.recruitment-application', [
            'applicationNumber' => $application->application_number,
            'name' => $application->full_name,
            'email' => $application->email,
            'jobTitle' => (string) ($application->job?->title ?? 'طلب عام'),
        ])->render();

        $this->sendHtml(
            to: $recipients,
            subject: 'طلب توظيف جديد '.$application->application_number.' — DressnMore',
            html: $html,
        );
    }

    public function sendDemoExpired(
        string $email,
        string $tenantName,
        string $title,
        string $body,
    ): void {
        $html = view('emails.demo-expired', [
            'tenantName' => $tenantName !== '' ? $tenantName : 'عزيزي العميل',
            'title' => $title,
            'body' => $body,
        ])->render();

        $this->sendHtml(
            to: [$email],
            subject: $title,
            html: $html,
        );
    }

    /**
     * @return list<string>
     */
    public function resolveTenantEmails(Tenant $tenant): array
    {
        $emails = [];

        $metadata = is_array($tenant->metadata) ? $tenant->metadata : [];
        $adminEmail = strtolower(trim((string) ($metadata['admin_email'] ?? '')));
        if ($adminEmail !== '' && filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $adminEmail;
        }

        $directoryEmails = TenantUserDirectory::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('email')
            ->pluck('email')
            ->all();

        foreach ($directoryEmails as $email) {
            $normalized = strtolower(trim((string) $email));
            if ($normalized !== '' && filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $normalized;
            }
        }

        return array_values(array_unique($emails));
    }

    /**
     * @param  list<string>  $to
     */
    private function sendHtml(array $to, string $subject, string $html): void
    {
        $fromAddress = (string) config('mail.from.address', 'info@dressnmore.it.com');
        $fromName = (string) config('mail.from.name', 'DressnMore');

        Mail::html($html, function ($message) use ($to, $subject, $fromAddress, $fromName): void {
            $message->to($to)
                ->from($fromAddress, $fromName)
                ->subject($subject);
        });
    }

    public function trySend(callable $callback): ?string
    {
        try {
            $callback();

            return null;
        } catch (Throwable $exception) {
            report($exception);

            return $exception->getMessage();
        }
    }
}
