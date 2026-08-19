<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        $schema = Schema::connection($this->connection);
        $connection = $schema->getConnection();
        $driver = $connection->getDriverName();

        $schema->table('subscriptions', function (Blueprint $table): void {
            $table->boolean('is_custom')->default(false);
            $table->string('billing_interval', 16)->nullable();
            $table->decimal('price_monthly', 12, 2)->nullable();
            $table->decimal('price_yearly', 12, 2)->nullable();
            $table->string('currency', 8)->nullable();
            $table->json('entitlements')->nullable();
            $table->index(['tenant_id', 'is_custom']);
        });

        $this->makePlanIdNullable($schema, $connection, $driver);
    }

    public function down(): void
    {
        Schema::connection($this->connection)->table('subscriptions', function (Blueprint $table): void {
            $table->dropIndex(['tenant_id', 'is_custom']);
            $table->dropColumn([
                'is_custom',
                'billing_interval',
                'price_monthly',
                'price_yearly',
                'currency',
                'entitlements',
            ]);
        });
    }

    private function makePlanIdNullable($schema, $connection, string $driver): void
    {
        if ($driver === 'mysql') {
            try {
                $schema->table('subscriptions', function (Blueprint $table): void {
                    $table->dropForeign(['plan_id']);
                });
            } catch (\Throwable) {
            }

            $connection->statement('ALTER TABLE subscriptions MODIFY plan_id BIGINT UNSIGNED NULL');

            $schema->table('subscriptions', function (Blueprint $table): void {
                $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();
            });

            return;
        }

        $schema->table('subscriptions', function (Blueprint $table): void {
            $table->unsignedBigInteger('plan_id')->nullable()->change();
        });
    }
};
