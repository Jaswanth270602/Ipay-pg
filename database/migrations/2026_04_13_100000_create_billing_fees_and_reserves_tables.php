<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('billing_fee_definitions')) {
            Schema::create('billing_fee_definitions', function (Blueprint $table) {
                $table->id();
                $table->string('code', 64)->unique();
                $table->string('name', 128);
                $table->string('category', 32)->default('transaction');
                $table->text('description')->nullable();
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->index(['category', 'is_active']);
            });
        }

        if (! Schema::hasTable('billing_fee_rules')) {
            Schema::create('billing_fee_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fee_definition_id');
                $table->unsignedBigInteger('merchant_id')->nullable();
                $table->unsignedBigInteger('partner_id')->nullable();
                $table->string('event_type', 32)->default('transaction');
                $table->string('applies_to_status', 16)->default('all');
                $table->string('payment_method', 32)->nullable();
                $table->string('currency', 8)->nullable();
                $table->string('pricing_model', 32)->default('percentage');
                $table->decimal('percentage_rate', 10, 4)->nullable();
                $table->decimal('fixed_amount', 15, 4)->nullable();
                $table->decimal('minimum_amount', 15, 4)->nullable();
                $table->decimal('maximum_amount', 15, 4)->nullable();
                $table->unsignedInteger('hold_days')->nullable();
                $table->decimal('rolling_reserve_cap', 15, 4)->nullable();
                $table->string('bill_to', 24)->default('merchant');
                $table->decimal('referral_commission_percentage', 10, 4)->nullable();
                $table->decimal('referral_commission_fixed', 15, 4)->nullable();
                $table->timestamp('effective_from')->nullable();
                $table->timestamp('effective_to')->nullable();
                $table->unsignedInteger('priority')->default(100);
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['merchant_id', 'event_type', 'is_active'], 'bfr_merchant_event_active_idx');
                $table->index(['effective_from', 'effective_to'], 'bfr_effective_window_idx');
                $table->index(['fee_definition_id', 'priority'], 'bfr_definition_priority_idx');
            });
        }

        if (! Schema::hasTable('fee_ledger_entries')) {
            Schema::create('fee_ledger_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('merchant_id');
                $table->unsignedBigInteger('partner_id')->nullable();
                $table->unsignedBigInteger('reseller_id')->nullable();
                $table->unsignedBigInteger('fee_definition_id')->nullable();
                $table->unsignedBigInteger('fee_rule_id')->nullable();
                $table->string('source_type', 32);
                $table->unsignedBigInteger('source_id');
                $table->string('event_type', 32);
                $table->string('fee_code', 64);
                $table->string('fee_name', 128);
                $table->string('bill_to', 24)->default('merchant');
                $table->string('entry_direction', 16)->default('debit');
                $table->string('currency', 8)->default('INR');
                $table->decimal('basis_amount', 15, 4)->default(0);
                $table->decimal('percentage_rate', 10, 4)->nullable();
                $table->decimal('fixed_amount', 15, 4)->nullable();
                $table->decimal('amount', 15, 4)->default(0);
                $table->decimal('referral_commission_amount', 15, 4)->default(0);
                $table->string('status', 24)->default('posted');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['merchant_id', 'status', 'created_at'], 'fle_merchant_status_created_idx');
                $table->index(['source_type', 'source_id'], 'fle_source_lookup_idx');
                $table->index(['event_type', 'fee_code'], 'fle_event_fee_idx');
                $table->unique(['fee_rule_id', 'source_type', 'source_id', 'entry_direction'], 'fle_rule_source_direction_uniq');
            });
        }

        if (! Schema::hasTable('rolling_reserve_holds')) {
            Schema::create('rolling_reserve_holds', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('merchant_id');
                $table->unsignedBigInteger('transaction_id');
                $table->unsignedBigInteger('fee_rule_id')->nullable();
                $table->unsignedBigInteger('ledger_entry_id')->nullable();
                $table->decimal('hold_amount', 15, 4)->default(0);
                $table->string('currency', 8)->default('INR');
                $table->dateTime('held_at');
                $table->dateTime('release_due_at');
                $table->dateTime('released_at')->nullable();
                $table->string('status', 24)->default('held');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['merchant_id', 'status', 'release_due_at'], 'rrh_merchant_status_due_idx');
                $table->index(['transaction_id', 'status'], 'rrh_transaction_status_idx');
            });
        }

        Schema::table('refunds', function (Blueprint $table) {
            if (! Schema::hasColumn('refunds', 'fee_amount')) {
                $table->decimal('fee_amount', 15, 2)->default(0)->after('amount');
            }
            if (! Schema::hasColumn('refunds', 'net_debit_amount')) {
                $table->decimal('net_debit_amount', 15, 2)->default(0)->after('fee_amount');
            }
        });

        Schema::table('settlements', function (Blueprint $table) {
            if (! Schema::hasColumn('settlements', 'fee_breakdown')) {
                $table->json('fee_breakdown')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('settlements', 'reserve_held_amount')) {
                $table->decimal('reserve_held_amount', 15, 2)->default(0)->after('fee_breakdown');
            }
            if (! Schema::hasColumn('settlements', 'reserve_released_amount')) {
                $table->decimal('reserve_released_amount', 15, 2)->default(0)->after('reserve_held_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('settlements', function (Blueprint $table) {
            $drops = [];
            foreach (['fee_breakdown', 'reserve_held_amount', 'reserve_released_amount'] as $column) {
                if (Schema::hasColumn('settlements', $column)) {
                    $drops[] = $column;
                }
            }
            if (! empty($drops)) {
                $table->dropColumn($drops);
            }
        });

        Schema::table('refunds', function (Blueprint $table) {
            $drops = [];
            foreach (['fee_amount', 'net_debit_amount'] as $column) {
                if (Schema::hasColumn('refunds', $column)) {
                    $drops[] = $column;
                }
            }
            if (! empty($drops)) {
                $table->dropColumn($drops);
            }
        });

        Schema::dropIfExists('rolling_reserve_holds');
        Schema::dropIfExists('fee_ledger_entries');
        Schema::dropIfExists('billing_fee_rules');
        Schema::dropIfExists('billing_fee_definitions');
    }
};

