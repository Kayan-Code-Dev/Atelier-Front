<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('smart_assistant_wa_dispatches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('invoice_id')->index();
            $table->string('kind', 40); // invoice_confirmed|pickup_reminder|return_reminder|return_congrats
            $table->string('to_phone', 80)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'invoice_id', 'kind'], 'sa_wa_dispatch_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('smart_assistant_wa_dispatches');
    }
};
