<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('recruitment_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('department', 120);
            $table->string('employment_type', 40)->default('full_time');
            $table->string('location', 180);
            $table->text('description')->nullable();
            $table->json('responsibilities')->nullable();
            $table->json('requirements')->nullable();
            $table->json('nice_to_have')->nullable();
            $table->json('benefits')->nullable();
            $table->json('skills')->nullable();
            $table->string('status', 32)->default('draft')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::connection('central')->create('recruitment_applications', function (Blueprint $table): void {
            $table->id();
            $table->string('application_number', 32)->unique();
            $table->foreignId('job_id')->nullable()->constrained('recruitment_jobs')->nullOnDelete();
            $table->string('full_name');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('linkedin_url', 500)->nullable();
            $table->string('portfolio_url', 500)->nullable();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->string('specialty', 180)->nullable();
            $table->text('bio')->nullable();
            $table->string('cv_disk', 32)->default('local');
            $table->string('cv_path')->nullable();
            $table->string('cv_original_name')->nullable();
            $table->string('cv_mime', 120)->nullable();
            $table->unsignedInteger('cv_size')->nullable();
            $table->string('portfolio_file_path')->nullable();
            $table->string('portfolio_file_name')->nullable();
            $table->boolean('consent')->default(false);
            $table->string('status', 32)->default('new')->index();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('recruitment_application_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained('recruitment_applications')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('super_admins')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::connection('central')->create('recruitment_application_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('application_id')->constrained('recruitment_applications')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('super_admins')->nullOnDelete();
            $table->string('type', 64);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->string('label');
            $table->text('meta')->nullable();
            $table->timestamps();
        });

        Schema::connection('central')->create('recruitment_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('notify_email', 255)->nullable();
            $table->boolean('accepting_applications')->default(true);
            $table->boolean('honeypot_enabled')->default(true);
            $table->unsignedInteger('cv_max_kilobytes')->default(5120);
            $table->timestamps();
        });

        $now = now();

        DB::connection('central')->table('recruitment_settings')->insert([
            'notify_email' => null,
            'accepting_applications' => true,
            'honeypot_enabled' => true,
            'cv_max_kilobytes' => 5120,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::connection('central')->table('recruitment_jobs')->insert([
            [
                'title' => 'Frontend Developer',
                'slug' => 'frontend-developer',
                'department' => 'Engineering',
                'employment_type' => 'full_time',
                'location' => 'Remote / Hybrid',
                'description' => 'نبحث عن مطور واجهات يحب بناء منتجات SaaS أنيقة وسريعة، ويعمل مباشرة على تجربة أصحاب الأعمال على DressnMore.',
                'responsibilities' => json_encode([
                    'بناء واجهات عامة ولوحات إدارة بـ React و TypeScript.',
                    'الربط مع REST APIs الحالية مع الحفاظ على تجربة متناسقة.',
                    'تحسين الأداء وتجربة الاستخدام على الموبايل والديسكتوب.',
                    'المساهمة في جودة الكود عبر مراجعات واضحة واختبارات عملية.',
                ], JSON_UNESCAPED_UNICODE),
                'requirements' => json_encode([
                    'خبرة عملية في React و TypeScript.',
                    'فهم جيد لـ REST APIs وحالات التحميل والأخطاء.',
                    'عين تصميمية للتفاصيل والمساحات والحركة الخفيفة.',
                    'قدرة على العمل ضمن فريق صغير يركز على النتيجة.',
                ], JSON_UNESCAPED_UNICODE),
                'nice_to_have' => json_encode([
                    'خبرة في Tailwind CSS.',
                    'معرفة بـ RTL والواجهات العربية.',
                    'اهتمام بـ SEO وتجربة الصفحات العامة.',
                ], JSON_UNESCAPED_UNICODE),
                'benefits' => json_encode([
                    'تأثير مباشر على منتج يستخدمه أصحاب أعمال حقيقيون.',
                    'مساحة لتجربة أفكار جديدة.',
                    'عمل عن بُعد أو هجين حسب الاتفاق.',
                ], JSON_UNESCAPED_UNICODE),
                'skills' => json_encode(['React', 'TypeScript', 'REST API'], JSON_UNESCAPED_UNICODE),
                'status' => 'published',
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Sales Executive',
                'slug' => 'sales-executive',
                'department' => 'Sales',
                'employment_type' => 'full_time',
                'location' => 'Mansoura / Hybrid',
                'description' => 'نبحث عن شخص يحب فهم مشاكل الأتيليهات وتحويلها إلى علاقة طويلة مع المنتج، لا مجرد إغلاق صفقة.',
                'responsibilities' => json_encode([
                    'التواصل مع أصحاب الأتيليهات وفهم احتياجهم الفعلي.',
                    'تقديم DressnMore بوضوح ومتابعة الرحلة حتى التحويل.',
                    'الحفاظ على علاقة صادقة بعد البيع مع فريق النجاح.',
                    'توثيق المحادثات والفرص داخل أدوات العمل المعتمدة.',
                ], JSON_UNESCAPED_UNICODE),
                'requirements' => json_encode([
                    'خبرة في مبيعات B2B أو SaaS.',
                    'قدرة على الاستماع قبل البيع.',
                    'تنظيم عالٍ في المتابعة وإدارة العلاقات.',
                    'لغة عربية ممتازة، والإنجليزية ميزة.',
                ], JSON_UNESCAPED_UNICODE),
                'nice_to_have' => json_encode([
                    'معرفة بسوق الأتيليهات أو الأزياء.',
                    'خبرة مع CRM.',
                    'وجود في المنصورة أو القدرة على العمل الهجين.',
                ], JSON_UNESCAPED_UNICODE),
                'benefits' => json_encode([
                    'عمولة واضحة مرتبطة بالنتيجة.',
                    'منتج ينمو في أسواق متعددة.',
                    'فريق يركز على الجودة لا عدد ساعات الشاشة.',
                ], JSON_UNESCAPED_UNICODE),
                'skills' => json_encode(['B2B', 'SaaS', 'CRM'], JSON_UNESCAPED_UNICODE),
                'status' => 'published',
                'published_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('recruitment_application_events');
        Schema::connection('central')->dropIfExists('recruitment_application_notes');
        Schema::connection('central')->dropIfExists('recruitment_applications');
        Schema::connection('central')->dropIfExists('recruitment_jobs');
        Schema::connection('central')->dropIfExists('recruitment_settings');
    }
};
