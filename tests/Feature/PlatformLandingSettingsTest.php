<?php

namespace Tests\Feature;

use App\Models\Central\LandingSetting;
use App\Models\Central\SuperAdmin;
use App\Services\Platform\LandingSettingsService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformLandingSettingsTest extends TestCase
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
        $this->admin = SuperAdmin::query()->create([
            'name' => 'Platform Admin',
            'email' => 'platform@example.com',
            'password' => Hash::make('password'),
            'status' => 'active',
        ]);
    }

    public function test_public_landing_returns_default_contact_and_new_modules(): void
    {
        $response = $this->getJson('/api/v1/landing');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'info@dressnmore.com')
            ->assertJsonPath('data.phone', '+966 50 000 0000')
            ->assertJsonPath('data.whatsapp_href', 'https://wa.me/201070205189')
            ->assertJsonPath('data.modules_count', 15);

        $labels = collect($response->json('data.modules'))->pluck('label')->all();
        $this->assertContains('الشات الذكي', $labels);
        $this->assertContains('المساعد الذكي', $labels);
        $this->assertContains('الموقع الإلكتروني', $labels);
    }

    public function test_admin_can_update_landing_settings(): void
    {
        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->putJson('/api/platform/landing-settings', [
            'phone' => '+20 100 111 2222',
            'whatsapp' => '201001112222',
            'email' => 'hello@dressnmore.com',
            'address' => 'القاهرة، مصر',
            'working_hours' => 'السبت - الخميس، 10ص - 8م',
            'facebook_url' => 'https://facebook.com/dressnmore',
            'instagram_url' => 'https://instagram.com/dressnmore',
            'footer_copyright' => '© 2026 DressnMore',
            'modules' => [
                ['icon' => 'ri-chat-smile-3-line', 'label' => 'الشات الذكي'],
                ['icon' => 'ri-robot-2-line', 'label' => 'المساعد الذكي'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.phone', '+20 100 111 2222')
            ->assertJsonPath('data.email', 'hello@dressnmore.com')
            ->assertJsonPath('data.address', 'القاهرة، مصر')
            ->assertJsonPath('data.facebook_url', 'https://facebook.com/dressnmore')
            ->assertJsonPath('data.footer_copyright', '© 2026 DressnMore')
            ->assertJsonPath('data.whatsapp_href', 'https://wa.me/201001112222')
            ->assertJsonPath('data.modules_count', 2);

        $this->assertDatabaseHas('platform_landing_settings', [
            'email' => 'hello@dressnmore.com',
            'phone' => '+20 100 111 2222',
        ], 'central');

        $public = $this->getJson('/api/v1/landing');
        $public->assertOk()
            ->assertJsonPath('data.email', 'hello@dressnmore.com')
            ->assertJsonPath('data.modules.1.label', 'المساعد الذكي');
    }

    public function test_guest_cannot_update_landing_settings(): void
    {
        $this->putJson('/api/platform/landing-settings', [
            'email' => 'hacked@example.com',
        ])->assertUnauthorized();

        $this->assertSame(0, LandingSetting::query()->count());
    }

    public function test_whatsapp_href_strips_international_prefix(): void
    {
        $this->assertSame(
            'https://wa.me/201070205189',
            LandingSettingsService::whatsappHref('00201070205189'),
        );
    }

    private function prepareSqliteDatabases(): void
    {
        $testingPath = storage_path('framework/testing');
        if (! is_dir($testingPath)) {
            mkdir($testingPath, 0777, true);
        }

        $this->centralDatabasePath = $testingPath.'/central-landing-settings.sqlite';
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
