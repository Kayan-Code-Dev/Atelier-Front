<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        $schema = Schema::connection('tenant');

        if (! $schema->hasTable('fixed_asset_categories')) {
            $schema->create('fixed_asset_categories', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->unsignedBigInteger('asset_account_id');
                $table->unsignedBigInteger('accumulated_depreciation_account_id');
                $table->unsignedBigInteger('depreciation_expense_account_id');
                $table->unsignedBigInteger('disposal_gain_loss_account_id');
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->foreign('asset_account_id', 'fac_asset_fk')->references('id')->on('accounts');
                $table->foreign('accumulated_depreciation_account_id', 'fac_accum_fk')->references('id')->on('accounts');
                $table->foreign('depreciation_expense_account_id', 'fac_exp_fk')->references('id')->on('accounts');
                $table->foreign('disposal_gain_loss_account_id', 'fac_gain_fk')->references('id')->on('accounts');
            });
        }

        if (! $schema->hasTable('fixed_assets')) {
            $schema->create('fixed_assets', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('category_id')->constrained('fixed_asset_categories');
                $table->string('asset_code')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('location')->nullable();
                $table->date('purchase_date');
                $table->date('placed_in_service_date');
                $table->decimal('acquisition_cost', 14, 2);
                $table->decimal('salvage_value', 14, 2)->default(0);
                $table->unsignedInteger('useful_life');
                $table->string('useful_life_unit', 16)->default('months');
                $table->string('depreciation_method', 32)->default('straight_line');
                $table->string('acquisition_method', 16)->default('cash');
                $table->string('status', 32)->default('draft');
                $table->foreignId('asset_account_id')->constrained('accounts');
                $table->foreignId('accumulated_depreciation_account_id')->constrained('accounts');
                $table->foreignId('depreciation_expense_account_id')->constrained('accounts');
                $table->foreignId('disposal_gain_loss_account_id')->constrained('accounts');
                $table->foreignId('payment_account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->foreignId('purchase_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->json('attachments')->nullable();
                $table->timestamps();
                $table->index(['status', 'branch_id']);
                $table->index('placed_in_service_date');
            });
        }

        if (! $schema->hasTable('fixed_asset_depreciation_schedules')) {
            $schema->create('fixed_asset_depreciation_schedules', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
                $table->string('period', 7);
                $table->unsignedInteger('sequence');
                $table->decimal('amount', 14, 2);
                $table->decimal('accumulated', 14, 2);
                $table->decimal('book_value', 14, 2);
                $table->string('status', 16)->default('pending');
                $table->timestamps();
                $table->unique(['fixed_asset_id', 'period']);
            });
        }

        if (! $schema->hasTable('fixed_asset_depreciation_runs')) {
            $schema->create('fixed_asset_depreciation_runs', function (Blueprint $table): void {
                $table->id();
                $table->string('period', 7);
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('status', 16)->default('pending');
                $table->unsignedInteger('assets_count')->default(0);
                $table->decimal('total_amount', 14, 2)->default(0);
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->string('idempotency_key')->nullable()->unique();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->unsignedBigInteger('reversed_by')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamp('reversed_at')->nullable();
                $table->string('reversal_reason')->nullable();
                $table->timestamps();
                $table->index(['period', 'branch_id', 'status']);
            });
        }

        if (! $schema->hasTable('fixed_asset_depreciation_entries')) {
            $schema->create('fixed_asset_depreciation_entries', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('run_id')->constrained('fixed_asset_depreciation_runs')->cascadeOnDelete();
                $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
                $table->foreignId('schedule_id')->nullable()->constrained('fixed_asset_depreciation_schedules')->nullOnDelete();
                $table->string('period', 7);
                $table->decimal('amount', 14, 2);
                $table->string('status', 16)->default('pending');
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->string('idempotency_key')->nullable()->unique();
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->unsignedBigInteger('reversed_by')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamp('reversed_at')->nullable();
                $table->string('reversal_reason')->nullable();
                $table->timestamps();
                $table->index(['fixed_asset_id', 'period']);
            });
        }

        if (! $schema->hasTable('fixed_asset_transfers')) {
            $schema->create('fixed_asset_transfers', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
                $table->date('transferred_at');
                $table->foreignId('from_branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('to_branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('from_location')->nullable();
                $table->string('to_location')->nullable();
                $table->string('reason')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('fixed_asset_disposals')) {
            $schema->create('fixed_asset_disposals', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
                $table->string('type', 16);
                $table->date('disposed_at');
                $table->decimal('acquisition_cost', 14, 2);
                $table->decimal('accumulated_depreciation', 14, 2);
                $table->decimal('net_book_value', 14, 2);
                $table->decimal('proceeds', 14, 2)->default(0);
                $table->decimal('gain_loss', 14, 2)->default(0);
                $table->foreignId('proceeds_account_id')->nullable()->constrained('accounts')->nullOnDelete();
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('fixed_asset_transactions')) {
            $schema->create('fixed_asset_transactions', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();
                $table->string('type', 32);
                $table->date('occurred_at');
                $table->decimal('amount', 14, 2)->default(0);
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->json('payload')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->index(['fixed_asset_id', 'type']);
            });
        }

        if (! $schema->hasTable('equity_operations')) {
            $schema->create('equity_operations', function (Blueprint $table): void {
                $table->id();
                $table->string('type', 16);
                $table->string('owner_name');
                $table->date('occurred_at');
                $table->decimal('amount', 14, 2);
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('cash_account_id')->constrained('accounts');
                $table->foreignId('equity_account_id')->constrained('accounts');
                $table->string('description')->nullable();
                $table->json('attachments')->nullable();
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('liabilities')) {
            $schema->create('liabilities', function (Blueprint $table): void {
                $table->id();
                $table->string('type', 24)->default('loan');
                $table->string('lender')->nullable();
                $table->string('number')->nullable();
                $table->string('name');
                $table->decimal('principal', 14, 2);
                $table->date('start_date');
                $table->date('due_date')->nullable();
                $table->string('status', 16)->default('active');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->foreignId('liability_account_id')->constrained('accounts');
                $table->foreignId('cash_account_id')->constrained('accounts');
                $table->text('notes')->nullable();
                $table->foreignId('receipt_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
            });
        }

        if (! $schema->hasTable('loan_settlements')) {
            $schema->create('loan_settlements', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('liability_id')->constrained('liabilities')->cascadeOnDelete();
                $table->date('settled_at');
                $table->decimal('amount', 14, 2);
                $table->foreignId('cash_account_id')->constrained('accounts');
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        $schema = Schema::connection('tenant');
        $schema->dropIfExists('loan_settlements');
        $schema->dropIfExists('liabilities');
        $schema->dropIfExists('equity_operations');
        $schema->dropIfExists('fixed_asset_transactions');
        $schema->dropIfExists('fixed_asset_disposals');
        $schema->dropIfExists('fixed_asset_transfers');
        $schema->dropIfExists('fixed_asset_depreciation_entries');
        $schema->dropIfExists('fixed_asset_depreciation_runs');
        $schema->dropIfExists('fixed_asset_depreciation_schedules');
        $schema->dropIfExists('fixed_assets');
        $schema->dropIfExists('fixed_asset_categories');
    }
};
