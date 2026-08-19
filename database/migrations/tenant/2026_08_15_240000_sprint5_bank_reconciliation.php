<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection($this->connection)->create('bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('bank_name');
            $table->string('account_number_last4', 4);
            $table->string('account_number_fingerprint', 64);
            $table->string('iban_last4', 4)->nullable();
            $table->string('iban_fingerprint', 64)->nullable();
            $table->string('currency', 8)->default('LYD');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('cashbox_id')->nullable()->constrained('cashboxes')->nullOnDelete();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->string('status', 24)->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['account_number_fingerprint', 'bank_name'], 'bank_acct_fingerprint_unique');
            $table->index(['branch_id', 'status']);
            $table->index('account_id');
        });

        Schema::connection($this->connection)->create('bank_statement_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->unsignedBigInteger('reconciliation_id')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->string('original_filename');
            $table->string('storage_path');
            $table->string('checksum', 64);
            $table->unsignedInteger('row_count')->default(0);
            $table->string('status', 24)->default('imported');
            $table->unsignedBigInteger('imported_by')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['bank_account_id', 'checksum'], 'bank_stmt_import_checksum_unique');
            $table->index('reconciliation_id');
        });

        Schema::connection($this->connection)->create('bank_statement_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('import_id')->constrained('bank_statement_imports')->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->date('line_date');
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->decimal('debit', 14, 2)->default(0);
            $table->decimal('credit', 14, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('fingerprint', 64);
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['bank_account_id', 'line_date']);
            $table->index('fingerprint');
        });

        Schema::connection($this->connection)->create('bank_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->cascadeOnDelete();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->date('date_from');
            $table->date('date_to');
            $table->decimal('statement_balance', 14, 2);
            $table->decimal('ledger_balance', 14, 2)->default(0);
            $table->decimal('deposits_in_transit', 14, 2)->default(0);
            $table->decimal('outstanding_payments', 14, 2)->default(0);
            $table->decimal('adjusted_bank_balance', 14, 2)->default(0);
            $table->decimal('difference', 14, 2)->default(0);
            $table->string('status', 24)->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('locked_by')->nullable();
            $table->unsignedBigInteger('reopened_by')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('reopened_at')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->timestamps();

            $table->index(['bank_account_id', 'status']);
            $table->index(['date_from', 'date_to']);
        });

        Schema::connection($this->connection)->create('bank_reconciliation_matches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->foreignId('statement_line_id')->constrained('bank_statement_lines')->cascadeOnDelete();
            $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedBigInteger('journal_entry_line_id')->nullable();
            $table->string('grade', 24);
            $table->string('match_type', 24)->default('manual');
            $table->unsignedTinyInteger('confidence')->default(0);
            $table->unsignedBigInteger('matched_by')->nullable();
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();

            $table->unique(['reconciliation_id', 'statement_line_id'], 'bank_recon_stmt_unique');
            $table->unique(['reconciliation_id', 'journal_entry_line_id'], 'bank_recon_line_unique');
            $table->index('journal_entry_id');
        });

        Schema::connection($this->connection)->create('bank_reconciliation_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->foreignId('statement_line_id')->nullable()->constrained('bank_statement_lines')->nullOnDelete();
            $table->unsignedBigInteger('journal_entry_id')->nullable();
            $table->string('kind', 32);
            $table->decimal('amount', 14, 2);
            $table->string('description')->nullable();
            $table->unsignedBigInteger('expense_account_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('journal_entry_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connection)->dropIfExists('bank_reconciliation_adjustments');
        Schema::connection($this->connection)->dropIfExists('bank_reconciliation_matches');
        Schema::connection($this->connection)->dropIfExists('bank_reconciliations');
        Schema::connection($this->connection)->dropIfExists('bank_statement_lines');
        Schema::connection($this->connection)->dropIfExists('bank_statement_imports');
        Schema::connection($this->connection)->dropIfExists('bank_accounts');
    }
};
