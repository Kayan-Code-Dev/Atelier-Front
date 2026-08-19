<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('journal_entries', function (Blueprint $table): void {
            if (! Schema::connection('tenant')->hasColumn('journal_entries', 'notes')) {
                $table->text('notes')->nullable()->after('description');
            }
            if (! Schema::connection('tenant')->hasColumn('journal_entries', 'attachments')) {
                $table->json('attachments')->nullable()->after('metadata');
            }
            if (! Schema::connection('tenant')->hasColumn('journal_entries', 'submitted_by')) {
                $table->foreignId('submitted_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::connection('tenant')->hasColumn('journal_entries', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('created_at');
            }
        });

        $entries = DB::connection('tenant')->table('journal_entries')->where('status', 'approved')->get();
        foreach ($entries as $entry) {
            DB::connection('tenant')->table('journal_entries')->where('id', $entry->id)->update([
                'status' => 'posted',
                'posted_at' => $entry->posted_at ?? $entry->approved_at ?? now(),
                'posted_by' => $entry->posted_by ?? $entry->approved_by,
            ]);
        }

        Schema::connection('tenant')->table('cashboxes', function (Blueprint $table): void {
            if (! Schema::connection('tenant')->hasColumn('cashboxes', 'kind')) {
                $table->string('kind', 24)->default('cash')->after('name');
            }
            if (! Schema::connection('tenant')->hasColumn('cashboxes', 'account_id')) {
                $table->foreignId('account_id')->nullable()->after('branch_id')->constrained('accounts')->nullOnDelete();
            }
        });

        Schema::connection('tenant')->table('cash_movements', function (Blueprint $table): void {
            if (! Schema::connection('tenant')->hasColumn('cash_movements', 'contra_account_id')) {
                $table->foreignId('contra_account_id')->nullable()->after('cashbox_id')->constrained('accounts')->nullOnDelete();
            }
            if (! Schema::connection('tenant')->hasColumn('cash_movements', 'counterparty_cashbox_id')) {
                $table->foreignId('counterparty_cashbox_id')->nullable()->after('contra_account_id')->constrained('cashboxes')->nullOnDelete();
            }
            if (! Schema::connection('tenant')->hasColumn('cash_movements', 'journal_entry_id')) {
                $table->foreignId('journal_entry_id')->nullable()->after('reference_id')->constrained('journal_entries')->nullOnDelete();
            }
        });

        if (! Schema::connection('tenant')->hasTable('opening_balance_batches')) {
            Schema::connection('tenant')->create('opening_balance_batches', function (Blueprint $table): void {
                $table->id();
                $table->date('entry_date');
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('status', 24)->default('draft');
                $table->string('description')->nullable();
                $table->decimal('total_debit', 14, 2)->default(0);
                $table->decimal('total_credit', 14, 2)->default(0);
                $table->boolean('is_balanced')->default(false);
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('posted_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('tenant')->hasTable('opening_balance_lines')) {
            Schema::connection('tenant')->create('opening_balance_lines', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('opening_balance_batch_id')->constrained('opening_balance_batches')->cascadeOnDelete();
                $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
                $table->decimal('debit', 14, 2)->default(0);
                $table->decimal('credit', 14, 2)->default(0);
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('opening_balance_lines');
        Schema::connection('tenant')->dropIfExists('opening_balance_batches');
    }
};
