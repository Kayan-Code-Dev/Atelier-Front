<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);

        if (! $schema->hasTable('marketplace_stores')) {
            $schema->create('marketplace_stores', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('phone', 32)->nullable();
                $table->string('address')->nullable();
                $table->string('logo_path')->nullable();
                $table->string('cover_path')->nullable();
                $table->string('instagram')->nullable();
                $table->string('facebook')->nullable();
                $table->string('whatsapp', 32)->nullable();
                $table->boolean('published')->default(false);
                $table->boolean('accept_orders')->default(true);
                $table->boolean('contact_visible')->default(true);
                $table->string('city', 64)->nullable();
                $table->string('area', 120)->nullable();
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();
                $table->unsignedSmallInteger('radius_km')->default(40);
                $table->boolean('sort_by_nearest')->default(true);
                $table->boolean('hide_outside_radius')->default(false);
                $table->json('covered_cities')->nullable();
                $table->json('delivery_options')->nullable();
                $table->json('payment_methods')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('marketplace_products')) {
            $schema->create('marketplace_products', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('dress_id')->nullable()->constrained('dresses')->nullOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('category', 64);
                $table->decimal('price', 12, 2);
                $table->decimal('compare_at_price', 12, 2)->nullable();
                $table->string('status', 32)->default('draft');
                $table->string('condition', 32)->default('available');
                $table->timestamp('published_at')->nullable();
                $table->string('image_path')->nullable();
                $table->json('gallery')->nullable();
                $table->string('visible_name')->nullable();
                $table->string('visible_phone', 32)->nullable();
                $table->string('city', 64)->nullable();
                $table->string('area', 120)->nullable();
                $table->decimal('lat', 10, 7)->nullable();
                $table->decimal('lng', 10, 7)->nullable();
                $table->unsignedInteger('orders_count')->default(0);
                $table->decimal('rating', 3, 2)->default(0);
                $table->unsignedInteger('reviews_count')->default(0);
                $table->timestamps();
                $table->softDeletes();
                $table->index(['status', 'category']);
                $table->index(['city', 'status']);
            });
        }

        if (! $schema->hasTable('marketplace_offers')) {
            $schema->create('marketplace_offers', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('type', 32);
                $table->decimal('value', 12, 2)->default(0);
                $table->date('starts_at');
                $table->date('ends_at');
                $table->string('status', 32)->default('scheduled');
                $table->string('applies_to', 64)->default('all');
                $table->timestamps();
                $table->index(['status', 'starts_at', 'ends_at']);
            });
        }

        if (! $schema->hasTable('marketplace_orders')) {
            $schema->create('marketplace_orders', function (Blueprint $table): void {
                $table->id();
                $table->string('number', 32)->unique();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->string('customer_name');
                $table->string('customer_phone', 32);
                $table->decimal('total', 12, 2)->default(0);
                $table->string('payment_status', 32)->default('unpaid');
                $table->string('status', 32)->default('new');
                $table->string('address')->nullable();
                $table->text('note')->nullable();
                $table->string('delivery_option_id', 64)->nullable();
                $table->string('payment_method_id', 64)->nullable();
                $table->timestamps();
                $table->index(['status', 'created_at']);
                $table->index('customer_phone');
            });
        }

        if (! $schema->hasTable('marketplace_order_items')) {
            $schema->create('marketplace_order_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('order_id')->constrained('marketplace_orders')->cascadeOnDelete();
                $table->foreignId('product_id')->nullable()->constrained('marketplace_products')->nullOnDelete();
                $table->string('name');
                $table->unsignedInteger('qty')->default(1);
                $table->decimal('price', 12, 2);
                $table->string('image_path')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('marketplace_reviews')) {
            $schema->create('marketplace_reviews', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->constrained('marketplace_products')->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('marketplace_orders')->nullOnDelete();
                $table->string('customer_name');
                $table->unsignedTinyInteger('rating');
                $table->text('comment')->nullable();
                $table->string('status', 32)->default('awaiting_reply');
                $table->text('reply')->nullable();
                $table->timestamp('replied_at')->nullable();
                $table->timestamps();
                $table->index(['product_id', 'status']);
            });
        }

        if (! $schema->hasTable('marketplace_threads')) {
            $schema->create('marketplace_threads', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->nullable()->constrained('marketplace_products')->nullOnDelete();
                $table->string('customer_name');
                $table->string('customer_phone', 32);
                $table->string('status', 32)->default('open');
                $table->unsignedInteger('unread_count')->default(0);
                $table->text('last_message')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'last_message_at']);
                $table->index('customer_phone');
            });
        }

        if (! $schema->hasTable('marketplace_messages')) {
            $schema->create('marketplace_messages', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('thread_id')->constrained('marketplace_threads')->cascadeOnDelete();
                $table->string('author', 16);
                $table->text('body');
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('marketplace_fittings')) {
            $schema->create('marketplace_fittings', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('product_id')->nullable()->constrained('marketplace_products')->nullOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('customer_name');
                $table->string('customer_phone', 32);
                $table->date('date');
                $table->string('time', 8);
                $table->unsignedSmallInteger('duration_min')->default(45);
                $table->string('branch_name')->nullable();
                $table->string('city', 64)->nullable();
                $table->string('status', 32)->default('upcoming');
                $table->text('note')->nullable();
                $table->timestamps();
                $table->index(['status', 'date']);
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection($this->connection);
        $schema->dropIfExists('marketplace_fittings');
        $schema->dropIfExists('marketplace_messages');
        $schema->dropIfExists('marketplace_threads');
        $schema->dropIfExists('marketplace_reviews');
        $schema->dropIfExists('marketplace_order_items');
        $schema->dropIfExists('marketplace_orders');
        $schema->dropIfExists('marketplace_offers');
        $schema->dropIfExists('marketplace_products');
        $schema->dropIfExists('marketplace_stores');
    }
};
