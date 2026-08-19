<?php

namespace App\Services\Platform;

use App\Models\Central\RecruitmentApplication;
use App\Models\Central\RecruitmentApplicationEvent;
use App\Models\Central\RecruitmentApplicationNote;
use App\Models\Central\RecruitmentJob;
use App\Models\Central\RecruitmentSetting;
use App\Models\Central\SuperAdmin;
use App\Services\Mail\PlatformMailService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RecruitmentService
{
    public function __construct(
        private readonly PlatformMailService $mail,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function publicJobs(): array
    {
        return RecruitmentJob::query()
            ->published()
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (RecruitmentJob $job) => $this->publicJobCard($job))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function publicJob(string $slug): array
    {
        $job = RecruitmentJob::query()->published()->where('slug', $slug)->first();
        if ($job === null) {
            throw new HttpException(404, 'الوظيفة غير متاحة');
        }

        return $this->publicJobDetail($job);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{application_number: string}
     */
    public function submitApplication(array $data, ?RecruitmentJob $job, ?UploadedFile $cv, ?UploadedFile $portfolio, string $ip, string $userAgent): array
    {
        $settings = RecruitmentSetting::current();
        if (! $settings->accepting_applications) {
            throw ValidationException::withMessages([
                'email' => ['استقبال الطلبات متوقف مؤقتاً.'],
            ]);
        }

        if ($job !== null && ! $job->isOpenForApplications()) {
            throw ValidationException::withMessages([
                'job' => ['هذه الوظيفة لم تعد مفتوحة للتقديم.'],
            ]);
        }

        $cvPayload = $cv ? $this->storePrivateFile($cv, 'cv', (int) $settings->cv_max_kilobytes) : null;
        $portfolioPayload = $portfolio
            ? $this->storePrivateFile($portfolio, 'portfolio', (int) config('recruitment.portfolio_max_kilobytes', 8192))
            : null;

        $application = new RecruitmentApplication;
        $application->fill([
            'application_number' => 'APP-TEMP-'.Str::lower(Str::random(8)),
            'job_id' => $job?->id,
            'full_name' => trim((string) $data['full_name']),
            'email' => strtolower(trim((string) $data['email'])),
            'phone' => $this->nullableString($data['phone'] ?? null),
            'city' => $this->nullableString($data['city'] ?? null),
            'linkedin_url' => $this->nullableString($data['linkedin_url'] ?? null),
            'portfolio_url' => $this->nullableString($data['portfolio_url'] ?? null),
            'years_experience' => isset($data['years_experience']) && $data['years_experience'] !== ''
                ? (int) $data['years_experience']
                : null,
            'specialty' => $this->nullableString($data['specialty'] ?? null),
            'bio' => $this->nullableString($data['bio'] ?? null),
            'consent' => true,
            'status' => 'new',
            'ip_address' => $ip !== '' ? $ip : null,
            'user_agent' => $userAgent !== '' ? mb_substr($userAgent, 0, 500) : null,
        ]);

        if ($cvPayload) {
            $application->cv_disk = $cvPayload['disk'];
            $application->cv_path = $cvPayload['path'];
            $application->cv_original_name = $cvPayload['original_name'];
            $application->cv_mime = $cvPayload['mime'];
            $application->cv_size = $cvPayload['size'];
        }
        if ($portfolioPayload) {
            $application->portfolio_file_path = $portfolioPayload['path'];
            $application->portfolio_file_name = $portfolioPayload['original_name'];
        }

        $application->save();
        $application->application_number = 'APP-'.str_pad((string) $application->id, 4, '0', STR_PAD_LEFT);
        $application->save();

        $this->addEvent($application, 'received', 'تم استلام الطلب', null, 'new');

        $this->mail->trySend(fn () => $this->mail->sendRecruitmentApplicantConfirmation(
            $application->email,
            $application->full_name,
            $application->application_number,
            $job?->title,
        ));

        $this->mail->trySend(fn () => $this->mail->notifyAdminsRecruitmentApplication(
            $application->fresh(['job']),
            $settings->notify_email,
        ));

        return ['application_number' => $application->application_number];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createJob(array $data): RecruitmentJob
    {
        $payload = $this->normalizeJobPayload($data);
        if (($payload['slug'] ?? '') === '') {
            $payload['slug'] = RecruitmentJob::makeUniqueSlug((string) $payload['title']);
        } else {
            $payload['slug'] = RecruitmentJob::makeUniqueSlug((string) $payload['slug']);
        }
        $payload['status'] = $payload['status'] ?? 'draft';
        if ($payload['status'] === 'published' && empty($payload['published_at'])) {
            $payload['published_at'] = now();
        }

        return RecruitmentJob::query()->create($payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateJob(RecruitmentJob $job, array $data): RecruitmentJob
    {
        $payload = $this->normalizeJobPayload($data, $job->id);
        if (isset($payload['status']) && $payload['status'] === 'published' && $job->published_at === null) {
            $payload['published_at'] = now();
        }
        $job->fill($payload);
        $job->save();

        return $job->fresh();
    }

    public function setJobStatus(RecruitmentJob $job, string $status): RecruitmentJob
    {
        $allowed = config('recruitment.job_statuses');
        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => ['حالة غير صالحة.']]);
        }

        $job->status = $status;
        if ($status === 'published' && $job->published_at === null) {
            $job->published_at = now();
        }
        $job->save();

        return $job->fresh();
    }

    public function deleteJob(RecruitmentJob $job): void
    {
        $job->delete();
    }

    public function changeApplicationStatus(RecruitmentApplication $application, string $status, ?int $actorId): RecruitmentApplication
    {
        $allowed = config('recruitment.application_statuses');
        if (! in_array($status, $allowed, true)) {
            throw ValidationException::withMessages(['status' => ['حالة غير صالحة.']]);
        }

        $from = $application->status;
        if ($from === $status) {
            return $application;
        }

        $application->status = $status;
        $application->save();

        $labels = [
            'new' => 'جديد',
            'screening' => 'فرز أولي',
            'shortlisted' => 'قائمة مختصرة',
            'interview' => 'مقابلة',
            'final_review' => 'مراجعة نهائية',
            'accepted' => 'مقبول',
            'rejected' => 'مرفوض',
        ];

        $this->addEvent(
            $application,
            'status_changed',
            'تم نقل الطلب إلى '.$labels[$status],
            $from,
            $status,
            $actorId,
        );

        return $application->fresh(['job', 'notes.author', 'events.actor']);
    }

    public function addNote(RecruitmentApplication $application, string $body, ?int $authorId): RecruitmentApplicationNote
    {
        $note = RecruitmentApplicationNote::query()->create([
            'application_id' => $application->id,
            'author_id' => $authorId,
            'body' => trim($body),
        ]);

        $this->addEvent($application, 'note_added', 'تمت إضافة ملاحظة داخلية', null, null, $authorId);

        return $note->load('author');
    }

    public function downloadCv(RecruitmentApplication $application, bool $inline = false): StreamedResponse
    {
        if (! $application->hasCv() || ! Storage::disk($application->cv_disk ?: 'local')->exists((string) $application->cv_path)) {
            throw new HttpException(404, 'ملف السيرة غير متوفر');
        }

        $downloadName = $this->safeDownloadName(
            (string) $application->cv_original_name,
            (string) $application->cv_path,
        );

        $disposition = $inline ? 'inline' : 'attachment';

        return Storage::disk($application->cv_disk ?: 'local')->response(
            (string) $application->cv_path,
            $downloadName,
            [
                'Content-Type' => $application->cv_mime ?: 'application/octet-stream',
                'Content-Disposition' => $disposition.'; filename="'.$downloadName.'"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ],
        );
    }

    /**
     * @return array<string, int>
     */
    public function applicationSummary(): array
    {
        $counts = RecruitmentApplication::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $out = ['total' => (int) RecruitmentApplication::query()->count()];
        foreach (config('recruitment.application_statuses') as $status) {
            $out[$status] = (int) ($counts[$status] ?? 0);
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    public function publicJobCard(RecruitmentJob $job): array
    {
        return [
            'slug' => $job->slug,
            'title' => $job->title,
            'department' => $job->department,
            'employment_type' => $job->employment_type,
            'employment_type_label' => $this->employmentLabel($job->employment_type),
            'location' => $job->location,
            'skills' => array_values(array_filter($job->skills ?? [])),
            'published_at' => optional($job->published_at)?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function publicJobDetail(RecruitmentJob $job): array
    {
        return [
            ...$this->publicJobCard($job),
            'description' => $job->description,
            'responsibilities' => array_values(array_filter($job->responsibilities ?? [])),
            'requirements' => array_values(array_filter($job->requirements ?? [])),
            'nice_to_have' => array_values(array_filter($job->nice_to_have ?? [])),
            'benefits' => array_values(array_filter($job->benefits ?? [])),
            'json_ld' => $this->jobPostingJsonLd($job),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function adminJobPayload(RecruitmentJob $job): array
    {
        return [
            'id' => $job->id,
            'title' => $job->title,
            'slug' => $job->slug,
            'department' => $job->department,
            'employment_type' => $job->employment_type,
            'location' => $job->location,
            'description' => $job->description,
            'responsibilities' => $job->responsibilities ?? [],
            'requirements' => $job->requirements ?? [],
            'nice_to_have' => $job->nice_to_have ?? [],
            'benefits' => $job->benefits ?? [],
            'skills' => $job->skills ?? [],
            'status' => $job->status,
            'published_at' => optional($job->published_at)?->toIso8601String(),
            'applications_count' => $job->applications()->count(),
            'created_at' => optional($job->created_at)?->toIso8601String(),
            'updated_at' => optional($job->updated_at)?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function adminApplicationListItem(RecruitmentApplication $application): array
    {
        return [
            'id' => $application->id,
            'application_number' => $application->application_number,
            'full_name' => $application->full_name,
            'email' => $application->email,
            'phone' => $application->phone,
            'city' => $application->city,
            'years_experience' => $application->years_experience,
            'specialty' => $application->specialty,
            'status' => $application->status,
            'job' => $application->job ? [
                'id' => $application->job->id,
                'title' => $application->job->title,
                'slug' => $application->job->slug,
                'department' => $application->job->department,
            ] : null,
            'has_cv' => $application->hasCv(),
            'created_at' => optional($application->created_at)?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function adminApplicationDetail(RecruitmentApplication $application): array
    {
        $application->loadMissing(['job', 'notes.author', 'events.actor']);

        return [
            ...$this->adminApplicationListItem($application),
            'linkedin_url' => $application->linkedin_url,
            'portfolio_url' => $application->portfolio_url,
            'bio' => $application->bio,
            'cv_original_name' => $application->cv_original_name,
            'cv_mime' => $application->cv_mime,
            'cv_size' => $application->cv_size,
            'portfolio_file_name' => $application->portfolio_file_name,
            'consent' => $application->consent,
            'notes' => $application->notes->map(fn (RecruitmentApplicationNote $note) => [
                'id' => $note->id,
                'body' => $note->body,
                'author' => $note->author?->name,
                'created_at' => optional($note->created_at)?->toIso8601String(),
            ])->values()->all(),
            'timeline' => $application->events->map(fn (RecruitmentApplicationEvent $event) => [
                'id' => $event->id,
                'type' => $event->type,
                'label' => $event->label,
                'from_status' => $event->from_status,
                'to_status' => $event->to_status,
                'actor' => $event->actor?->name,
                'created_at' => optional($event->created_at)?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeJobPayload(array $data, ?int $ignoreId = null): array
    {
        $list = static function (mixed $value): array {
            $items = is_array($value) ? $value : (preg_split('/\r\n|\r|\n/', (string) $value) ?: []);
            return array_values(array_filter(
                $items,
                static fn ($item) => trim((string) $item) !== '',
            ));
        };

        $payload = [];
        foreach (['title', 'department', 'employment_type', 'location', 'description', 'status'] as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = is_string($data[$key]) ? trim($data[$key]) : $data[$key];
            }
        }
        if (array_key_exists('slug', $data)) {
            $slug = trim((string) $data['slug']);
            $payload['slug'] = $slug === '' ? '' : RecruitmentJob::makeUniqueSlug($slug, $ignoreId);
        }
        foreach (['responsibilities', 'requirements', 'nice_to_have', 'benefits', 'skills'] as $key) {
            if (array_key_exists($key, $data)) {
                $payload[$key] = array_map(fn ($item) => trim((string) $item), $list($data[$key]));
            }
        }

        return $payload;
    }

    /**
     * @return array{disk: string, path: string, original_name: string, mime: string, size: int}
     */
    private function storePrivateFile(UploadedFile $file, string $kind, int $maxKb): array
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([$kind === 'cv' ? 'cv' : 'portfolio_file' => ['تعذر رفع الملف.']]);
        }

        $maxBytes = $maxKb * 1024;
        if ($file->getSize() > $maxBytes) {
            throw ValidationException::withMessages([
                $kind === 'cv' ? 'cv' : 'portfolio_file' => ['حجم الملف يتجاوز الحد المسموح.'],
            ]);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $allowedExt = config('recruitment.allowed_cv_extensions');
        if (! in_array($extension, $allowedExt, true)) {
            throw ValidationException::withMessages([
                $kind === 'cv' ? 'cv' : 'portfolio_file' => ['صيغة الملف غير مدعومة. استخدم PDF أو DOC أو DOCX.'],
            ]);
        }

        $detectedMime = $this->detectMime($file);
        $allowedMimes = config('recruitment.allowed_cv_mimes');
        if (! in_array($detectedMime, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                $kind === 'cv' ? 'cv' : 'portfolio_file' => ['نوع الملف غير مسموح.'],
            ]);
        }

        $original = $this->sanitizeFilename((string) $file->getClientOriginalName(), $extension);
        $path = config('recruitment.cv_directory').'/'.Str::uuid()->toString().'.'.$extension;
        $disk = (string) config('recruitment.disk', 'local');
        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()) ?: '');

        return [
            'disk' => $disk,
            'path' => $path,
            'original_name' => $original,
            'mime' => $detectedMime,
            'size' => (int) $file->getSize(),
        ];
    }

    private function detectMime(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $realPath = (string) $file->getRealPath();
        $head = is_file($realPath) ? (string) file_get_contents($realPath, false, null, 0, 8) : '';
        if ($extension === 'pdf' && str_starts_with($head, '%PDF')) {
            return 'application/pdf';
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $fromFile = $realPath !== '' ? ($finfo->file($realPath) ?: '') : '';
        if (is_string($fromFile) && $fromFile !== '' && $fromFile !== 'application/octet-stream') {
            return strtolower($fromFile);
        }

        $client = strtolower((string) ($file->getMimeType() ?: ''));
        if ($client !== '') {
            return $client;
        }

        return match ($extension) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };
    }

    private function sanitizeFilename(string $name, string $extension): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = preg_replace('/[^\p{L}\p{N}\.\-_ ]+/u', '', $base) ?: 'cv';
        $base = trim(str_replace(['/', '\\'], '', $base));
        $base = mb_substr($base, 0, 80);

        return $base.'.'.$extension;
    }

    private function safeDownloadName(string $original, string $path): string
    {
        $fromOriginal = $this->sanitizeFilename($original, pathinfo($original, PATHINFO_EXTENSION) ?: 'bin');
        if ($fromOriginal !== '.bin' && $fromOriginal !== '') {
            return $fromOriginal;
        }

        return basename($path);
    }

    private function addEvent(
        RecruitmentApplication $application,
        string $type,
        string $label,
        ?string $from = null,
        ?string $to = null,
        ?int $actorId = null,
    ): void {
        RecruitmentApplicationEvent::query()->create([
            'application_id' => $application->id,
            'actor_id' => $actorId,
            'type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'label' => $label,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function jobPostingJsonLd(RecruitmentJob $job): array
    {
        $employmentMap = [
            'full_time' => 'FULL_TIME',
            'part_time' => 'PART_TIME',
            'contract' => 'CONTRACT',
            'internship' => 'INTERN',
        ];

        $isRemote = str_contains(mb_strtolower($job->location), 'remote');
        $payload = [
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $job->title,
            'description' => $job->description ?: $job->title,
            'datePosted' => optional($job->published_at ?? $job->created_at)?->toDateString(),
            'employmentType' => $employmentMap[$job->employment_type] ?? 'FULL_TIME',
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => 'DressnMore',
                'sameAs' => 'https://dressnmore.it.com',
            ],
            'identifier' => [
                '@type' => 'PropertyValue',
                'name' => 'DressnMore',
                'value' => $job->slug,
            ],
        ];

        if ($isRemote) {
            $payload['jobLocationType'] = 'TELECOMMUTE';
        }

        $payload['jobLocation'] = [
            '@type' => 'Place',
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $job->location,
                'addressCountry' => 'EG',
            ],
        ];

        return $payload;
    }

    private function employmentLabel(string $type): string
    {
        return match ($type) {
            'part_time' => 'Part Time',
            'contract' => 'Contract',
            'internship' => 'Internship',
            default => 'Full Time',
        };
    }

    private function nullableString(mixed $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    public function adminRecipientEmails(?string $override): array
    {
        $emails = SuperAdmin::query()
            ->where('status', 'active')
            ->whereNotNull('email')
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $extra = strtolower(trim((string) ($override ?: env('PLATFORM_ADMIN_EMAIL', ''))));
        if ($extra !== '' && filter_var($extra, FILTER_VALIDATE_EMAIL) && ! in_array($extra, $emails, true)) {
            $emails[] = $extra;
        }

        return $emails;
    }
}
