<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('accounts', function (Blueprint $table): void {
            if (! Schema::connection('tenant')->hasColumn('accounts', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->after('type')->constrained('accounts')->nullOnDelete();
            }
            if (! Schema::connection('tenant')->hasColumn('accounts', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('parent_id')->constrained('branches')->nullOnDelete();
            }
            if (! Schema::connection('tenant')->hasColumn('accounts', 'normal_balance')) {
                $table->string('normal_balance', 8)->default('debit')->after('type');
            }
            if (! Schema::connection('tenant')->hasColumn('accounts', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('is_active');
            }
            if (! Schema::connection('tenant')->hasColumn('accounts', 'allow_posting')) {
                $table->boolean('allow_posting')->default(true)->after('is_system');
            }
        });

        Schema::connection('tenant')->table('journal_entries', function (Blueprint $table): void {
            if (! Schema::connection('tenant')->hasColumn('journal_entries', 'source_reference')) {
                $table->string('source_reference')->nullable()->after('reference_number');
            }
            if (! Schema::connection('tenant')->hasColumn('journal_entries', 'posted_by')) {
                $table->foreignId('posted_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::connection('tenant')->hasColumn('journal_entries', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('approved_at');
            }
            if (! Schema::connection('tenant')->hasColumn('journal_entries', 'reversed_by')) {
                $table->foreignId('reversed_by')->nullable()->after('cancelled_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::connection('tenant')->hasColumn('journal_entries', 'reversed_at')) {
                $table->timestamp('reversed_at')->nullable()->after('cancelled_at');
            }
            if (! Schema::connection('tenant')->hasColumn('journal_entries', 'reversal_reason')) {
                $table->text('reversal_reason')->nullable()->after('cancellation_reason');
            }
            if (! Schema::connection('tenant')->hasColumn('journal_entries', 'metadata')) {
                $table->json('metadata')->nullable();
            }
            if (! Schema::connection('tenant')->hasColumn('journal_entries', 'needs_review')) {
                $table->boolean('needs_review')->default(false);
            }
        });

        if (! Schema::connection('tenant')->hasTable('accounting_periods')) {
            Schema::connection('tenant')->create('accounting_periods', function (Blueprint $table): void {
                $table->id();
                $table->unsignedSmallInteger('year');
                $table->date('starts_on');
                $table->date('ends_on');
                $table->boolean('is_closed')->default(false);
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();
                $table->unique(['year', 'starts_on', 'ends_on']);
            });
        }

        if (! Schema::connection('tenant')->hasTable('accounting_events')) {
            Schema::connection('tenant')->create('accounting_events', function (Blueprint $table): void {
                $table->id();
                $table->string('event_type', 64);
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->string('source_type', 64)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->timestamp('occurred_at');
                $table->json('payload')->nullable();
                $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
                $table->timestamps();
                $table->index(['source_type', 'source_id']);
            });
        }

        if (! Schema::connection('tenant')->hasTable('accounting_audit_logs')) {
            Schema::connection('tenant')->create('accounting_audit_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 32);
                $table->string('entity_type', 64);
                $table->unsignedBigInteger('entity_id');
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->index(['entity_type', 'entity_id']);
            });
        }

        $this->backfill();
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('accounting_audit_logs');
        Schema::connection('tenant')->dropIfExists('accounting_events');
        Schema::connection('tenant')->dropIfExists('accounting_periods');
    }

    private function backfill(): void
    {
        $debitTypes = ['asset', 'expense'];

        DB::connection('tenant')->table('accounts')->orderBy('id')->each(function (object $account) use ($debitTypes): void {
            DB::connection('tenant')->table('accounts')->where('id', $account->id)->update([
                'normal_balance' => in_array($account->type, $debitTypes, true) ? 'debit' : 'credit',
                'is_system' => true,
                'allow_posting' => true,
            ]);
        });

        DB::connection('tenant')->table('journal_entries')
            ->whereNull('source_reference')
            ->whereNotNull('reference_number')
            ->update([
                'source_reference' => DB::raw('reference_number'),
            ]);

        DB::connection('tenant')->table('journal_entries')
            ->where('status', 'approved')
            ->whereNull('posted_at')
            ->update([
                'posted_at' => DB::raw('approved_at'),
                'posted_by' => DB::raw('approved_by'),
            ]);

        DB::connection('tenant')->table('journal_entries')
            ->where('is_balanced', false)
            ->whereIn('status', ['approved', 'posted'])
            ->update(['needs_review' => true]);
    }
};
