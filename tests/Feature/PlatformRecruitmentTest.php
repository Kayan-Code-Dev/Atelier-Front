<?php

namespace Tests\Feature;

use App\Models\Central\RecruitmentApplication;
use App\Models\Central\RecruitmentJob;
use App\Models\Central\SuperAdmin;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformRecruitmentTest extends TestCase
{
    private string $centralDatabasePath;

    private SuperAdmin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSqliteDatabases();
        Artisan::call('migrate:fresh', [
            '--database' => 'central',
            '--force' => true,
        ]);
        Storage::fake('local');
        Mail::fake();

        $this->admin = SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => 'platform@example.com',
            'password' => 'password',
            'status' => 'active',
        ]);
    }

    public function test_public_careers_lists_only_published_jobs(): void
    {
        RecruitmentJob::query()->where('slug', 'frontend-developer')->update(['status' => 'draft']);

        $response = $this->getJson('/api/v1/careers');

        $response->assertOk()->assertJsonPath('success', true);
        $slugs = collect($response->json('data.jobs'))->pluck('slug')->all();
        $this->assertContains('sales-executive', $slugs);
        $this->assertNotContains('frontend-developer', $slugs);
        $this->assertStringNotContainsString('cv_path', $response->getContent());
    }

    public function test_public_job_details_include_jobposting_schema(): void
    {
        $response = $this->getJson('/api/v1/careers/frontend-developer');

        $response->assertOk()
            ->assertJsonPath('data.slug', 'frontend-developer')
            ->assertJsonPath('data.json_ld.@type', 'JobPosting')
            ->assertJsonPath('data.json_ld.hiringOrganization.name', 'DressnMore');
    }

    public function test_draft_job_is_not_public(): void
    {
        RecruitmentJob::query()->where('slug', 'frontend-developer')->update(['status' => 'draft']);

        $this->getJson('/api/v1/careers/frontend-developer')->assertNotFound();
    }

    public function test_applicant_can_submit_cv_privately(): void
    {
        $cv = $this->fakePdf('ahmed-cv.pdf');

        $response = $this->post('/api/v1/careers/frontend-developer/applications', [
            'full_name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'phone' => '01000000000',
            'city' => 'المنصورة',
            'linkedin_url' => 'https://linkedin.com/in/ahmed',
            'portfolio_url' => 'https://github.com/ahmed',
            'years_experience' => 3,
            'specialty' => 'Frontend',
            'bio' => 'أحب بناء منتجات حقيقية.',
            'consent' => 1,
            'cv' => $cv,
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('success', true);
        $number = $response->json('data.application_number');
        $this->assertMatchesRegularExpression('/^APP-\d{4,}$/', (string) $number);
        $this->assertStringNotContainsString('cv_path', $response->getContent());
        $this->assertStringNotContainsString('storage/cv', $response->getContent());

        $row = RecruitmentApplication::query()->where('application_number', $number)->first();
        $this->assertNotNull($row);
        $this->assertSame('new', $row->status);
        $this->assertNotNull($row->cv_path);
        $this->assertStringStartsWith('recruitment/cvs/', (string) $row->cv_path);
        Storage::disk('local')->assertExists($row->cv_path);
    }

    public function test_closed_job_rejects_application(): void
    {
        RecruitmentJob::query()->where('slug', 'sales-executive')->update(['status' => 'closed']);

        $this->post('/api/v1/careers/sales-executive/applications', [
            'full_name' => 'سارة علي',
            'email' => 'sara@example.com',
            'consent' => 1,
            'cv' => $this->fakePdf('sara.pdf'),
        ], ['Accept' => 'application/json'])->assertNotFound();

        $this->assertSame(0, RecruitmentApplication::query()->count());
    }

    public function test_honeypot_does_not_store_application(): void
    {
        $this->post('/api/v1/careers/applications', [
            'full_name' => 'Bot',
            'email' => 'bot@example.com',
            'consent' => 1,
            'website' => 'https://spam.test',
            'cv' => $this->fakePdf('bot.pdf'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $this->assertSame(0, RecruitmentApplication::query()->count());
    }

    public function test_guest_cannot_download_cv(): void
    {
        $application = $this->storeApplication();

        $this->getJson('/api/platform/recruitment/applications/'.$application->id.'/cv')
            ->assertUnauthorized();
    }

    public function test_admin_can_manage_jobs_pipeline_and_download_cv(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        $create = $this->postJson('/api/platform/recruitment/jobs', [
            'title' => 'Product Designer',
            'department' => 'Design',
            'employment_type' => 'full_time',
            'location' => 'Remote',
            'description' => 'صمم تجربة المنتج.',
            'skills' => ['Figma', 'UI'],
            'status' => 'draft',
        ]);
        $create->assertCreated()->assertJsonPath('data.status', 'draft');
        $id = (int) $create->json('data.id');

        $this->getJson('/api/v1/careers/product-designer')->assertNotFound();

        $this->postJson('/api/platform/recruitment/jobs/'.$id.'/publish')
            ->assertOk()
            ->assertJsonPath('data.status', 'published');
        $this->getJson('/api/v1/careers/product-designer')->assertOk();

        $this->postJson('/api/platform/recruitment/jobs/'.$id.'/hide')
            ->assertOk()
            ->assertJsonPath('data.status', 'draft');
        $this->postJson('/api/platform/recruitment/jobs/'.$id.'/archive')
            ->assertOk()
            ->assertJsonPath('data.status', 'archived');

        $application = $this->storeApplication();

        $this->getJson('/api/platform/recruitment/applications/summary')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.new', 1);

        $this->patchJson('/api/platform/recruitment/applications/'.$application->id.'/status', [
            'status' => 'screening',
        ])->assertOk()->assertJsonPath('data.status', 'screening');

        $this->postJson('/api/platform/recruitment/applications/'.$application->id.'/notes', [
            'body' => 'سيرة قوية، نحدد مقابلة.',
        ])->assertOk();

        $detail = $this->getJson('/api/platform/recruitment/applications/'.$application->id);
        $detail->assertOk()
            ->assertJsonPath('data.full_name', 'أحمد محمد');
        $this->assertArrayNotHasKey('cv_path', $detail->json('data'));

        $download = $this->get('/api/platform/recruitment/applications/'.$application->id.'/cv');
        $download->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $download->headers->get('Content-Type'));
    }

    public function test_admin_settings_roundtrip(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        $this->putJson('/api/platform/recruitment/settings', [
            'notify_email' => 'hr@dressnmore.it.com',
            'accepting_applications' => false,
            'cv_max_kilobytes' => 2048,
        ])->assertOk()->assertJsonPath('data.notify_email', 'hr@dressnmore.it.com');

        $this->post('/api/v1/careers/applications', [
            'full_name' => 'ليلى',
            'email' => 'laila@example.com',
            'consent' => 1,
            'cv' => $this->fakePdf('laila.pdf'),
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }

    private function storeApplication(): RecruitmentApplication
    {
        $this->post('/api/v1/careers/frontend-developer/applications', [
            'full_name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'consent' => 1,
            'cv' => $this->fakePdf('ahmed.pdf'),
        ], ['Accept' => 'application/json'])->assertCreated();

        return RecruitmentApplication::query()->latest('id')->firstOrFail();
    }

    private function fakePdf(string $name): UploadedFile
    {
        $content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<<>>\n%%EOF";

        return UploadedFile::fake()->createWithContent($name, $content);
    }

    private function prepareSqliteDatabases(): void
    {
        $testingPath = storage_path('framework/testing');
        if (! is_dir($testingPath)) {
            mkdir($testingPath, 0777, true);
        }

        $this->centralDatabasePath = $testingPath.'/central-recruitment.sqlite';
        @unlink($this->centralDatabasePath);
        touch($this->centralDatabasePath);

        Config::set('database.default', 'central');
        Config::set('database.connections.central', [
            'driver' => 'sqlite',
            'database' => $this->centralDatabasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        DB::purge('central');
    }
}
