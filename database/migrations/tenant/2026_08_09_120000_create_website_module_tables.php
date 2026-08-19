<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('website_sites')) {
            $schema->create('website_sites', function (Blueprint $table): void {
                $table->id();
                $table->string('status', 32)->default('draft'); // draft|published|maintenance
                $table->string('template_key', 64)->nullable();
                $table->string('template_name')->nullable();
                $table->string('public_slug', 120)->nullable();
                $table->string('custom_domain')->nullable();
                $table->timestamp('domain_verified_at')->nullable();
                $table->string('ssl_status', 32)->nullable(); // none|pending|active|error
                $table->timestamp('last_published_at')->nullable();
                $table->string('logo_path')->nullable();
                $table->string('favicon_path')->nullable();
                $table->json('branding')->nullable();
                $table->json('texts')->nullable();
                $table->json('seo')->nullable();
                $table->json('pixels')->nullable();
                $table->json('sharing')->nullable();
                $table->json('channels')->nullable();
                $table->json('booking_settings')->nullable();
                $table->json('notifications')->nullable();
                $table->json('general')->nullable();
                $table->unsignedBigInteger('visitors_count')->default(0);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('website_pages')) {
            $schema->create('website_pages', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('slug', 160);
                $table->string('status', 32)->default('draft'); // draft|published
                $table->boolean('is_visible')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('meta')->nullable();
                $table->timestamps();
                $table->unique('slug');
            });
        }

        if (! $schema->hasTable('website_sections')) {
            $schema->create('website_sections', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('page_id')->nullable()->constrained('website_pages')->nullOnDelete();
                $table->string('type', 64);
                $table->boolean('enabled')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->json('config')->nullable();
                $table->timestamps();
                $table->unique(['page_id', 'type']);
            });
        }

        if (! $schema->hasTable('website_menus')) {
            $schema->create('website_menus', function (Blueprint $table): void {
                $table->id();
                $table->string('title');
                $table->string('url');
                $table->string('location', 32)->default('header'); // header|footer
                $table->unsignedInteger('sort_order')->default(0);
                $table->string('status', 32)->default('active');
                $table->foreignId('parent_id')->nullable()->constrained('website_menus')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('website_media')) {
            $schema->create('website_media', function (Blueprint $table): void {
                $table->id();
                $table->string('filename');
                $table->string('path');
                $table->string('mime', 120)->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->unsignedInteger('width')->nullable();
                $table->unsignedInteger('height')->nullable();
                $table->string('usage_label')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('website_gallery_albums')) {
            $schema->create('website_gallery_albums', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->boolean('is_visible')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('website_gallery_images')) {
            $schema->create('website_gallery_images', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('album_id')->constrained('website_gallery_albums')->cascadeOnDelete();
                $table->foreignId('media_id')->nullable()->constrained('website_media')->nullOnDelete();
                $table->string('alt_text')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('website_services')) {
            $schema->create('website_services', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('status', 32)->default('active');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('website_product_publications')) {
            $schema->create('website_product_publications', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('dress_id');
                $table->boolean('is_published')->default(false);
                $table->boolean('is_featured')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->string('site_title')->nullable();
                $table->string('cta_label')->nullable();
                $table->timestamps();
                $table->unique('dress_id');
            });
        }

        if (! $schema->hasTable('website_leads')) {
            $schema->create('website_leads', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('phone', 40)->nullable();
                $table->string('email')->nullable();
                $table->string('source', 64)->default('website');
                $table->string('campaign')->nullable();
                $table->string('status', 32)->default('new');
                $table->unsignedBigInteger('assignee_user_id')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('website_forms')) {
            $schema->create('website_forms', function (Blueprint $table): void {
                $table->id();
                $table->string('key', 64);
                $table->string('name');
                $table->boolean('is_enabled')->default(true);
                $table->boolean('create_lead')->default(true);
                $table->string('notify_to')->nullable();
                $table->timestamps();
                $table->unique('key');
            });
        }

        if (! $schema->hasTable('website_messages')) {
            $schema->create('website_messages', function (Blueprint $table): void {
                $table->id();
                $table->string('sender_name');
                $table->string('sender_email')->nullable();
                $table->string('sender_phone', 40)->nullable();
                $table->string('subject')->nullable();
                $table->text('body');
                $table->string('status', 32)->default('unread'); // unread|read|archived
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        foreach ([
            'website_messages',
            'website_forms',
            'website_leads',
            'website_product_publications',
            'website_services',
            'website_gallery_images',
            'website_gallery_albums',
            'website_media',
            'website_menus',
            'website_sections',
            'website_pages',
            'website_sites',
        ] as $table) {
            $schema->dropIfExists($table);
        }
    }
};
