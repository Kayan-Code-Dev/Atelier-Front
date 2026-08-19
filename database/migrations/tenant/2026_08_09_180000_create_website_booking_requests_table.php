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

        if (! $schema->hasTable('website_booking_requests')) {
            $schema->create('website_booking_requests', function (Blueprint $table): void {
                $table->id();
                $table->string('kind', 32); // fitting|appointment|order
                $table->string('name');
                $table->string('phone', 40);
                $table->string('email')->nullable();
                $table->string('service')->nullable();
                $table->string('preferred_date', 64)->nullable();
                $table->text('notes')->nullable();
                $table->string('branch')->nullable();
                $table->string('status', 32)->default('new');
                $table->decimal('amount', 12, 2)->nullable();
                $table->json('meta')->nullable();
                $table->foreignId('lead_id')->nullable()->constrained('website_leads')->nullOnDelete();
                $table->timestamps();

                $table->index('kind');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('website_booking_requests');
    }
};
