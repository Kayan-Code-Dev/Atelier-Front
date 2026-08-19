<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('marketplace_store_listings')) {
            Schema::connection('central')->create('marketplace_store_listings', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->string('slug', 120);
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('phone', 32)->nullable();
                $table->string('city', 64)->nullable();
                $table->string('area', 120)->nullable();
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();
                $table->unsignedSmallInteger('radius_km')->default(40);
                $table->json('covered_cities')->nullable();
                $table->boolean('sort_by_nearest')->default(true);
                $table->boolean('hide_outside_radius')->default(false);
                $table->boolean('published')->default(false);
                $table->boolean('accept_orders')->default(true);
                $table->boolean('contact_visible')->default(true);
                $table->string('logo_path')->nullable();
                $table->timestamps();
                $table->unique('tenant_id');
                $table->index(['published', 'city']);
            });
        }

        if (! Schema::connection('central')->hasTable('marketplace_product_listings')) {
            Schema::connection('central')->create('marketplace_product_listings', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('store_listing_id');
                $table->unsignedBigInteger('source_product_id');
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('category', 64);
                $table->decimal('price', 12, 2);
                $table->decimal('compare_at_price', 12, 2)->nullable();
                $table->string('image_path')->nullable();
                $table->string('city', 64)->nullable();
                $table->string('area', 120)->nullable();
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();
                $table->decimal('rating', 3, 2)->default(0);
                $table->unsignedInteger('reviews_count')->default(0);
                $table->string('condition', 32)->default('available');
                $table->boolean('published')->default(false);
                $table->timestamps();
                $table->unique(['tenant_id', 'source_product_id'], 'mpl_tenant_source_unique');
                $table->index(['published', 'category']);
                $table->index(['lat', 'lng']);
                $table->foreign('store_listing_id')->references('id')->on('marketplace_store_listings')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('marketplace_product_listings');
        Schema::connection('central')->dropIfExists('marketplace_store_listings');
    }
};
