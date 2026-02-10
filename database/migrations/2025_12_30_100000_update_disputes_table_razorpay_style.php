<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // First, populate dispute_id for existing records if column doesn't exist
        if (!Schema::hasColumn('disputes', 'dispute_id')) {
            Schema::table('disputes', function (Blueprint $table) {
                $table->string('dispute_id', 50)->nullable()->after('id');
            });
            
            // Populate dispute_id for existing records
            DB::table('disputes')->whereNull('dispute_id')->orWhere('dispute_id', '')->chunkById(100, function ($disputes) {
                foreach ($disputes as $dispute) {
                    DB::table('disputes')
                        ->where('id', $dispute->id)
                        ->update(['dispute_id' => 'dp_' . strtoupper(Str::random(14))]);
                }
            });
        }

        Schema::table('disputes', function (Blueprint $table) {
            // Drop old columns if they exist
            if (Schema::hasColumn('disputes', 'evidence_url')) {
                $table->dropColumn('evidence_url');
            }
            if (Schema::hasColumn('disputes', 'notes')) {
                $table->dropColumn('notes');
            }
        });

        Schema::table('disputes', function (Blueprint $table) {
            // Add unique constraint if not already unique
            if (Schema::hasColumn('disputes', 'dispute_id')) {
                // Make sure all dispute_ids are unique before adding constraint
                try {
                    DB::statement('ALTER TABLE disputes MODIFY dispute_id VARCHAR(50) NOT NULL');
                    DB::statement('ALTER TABLE disputes ADD UNIQUE KEY disputes_dispute_id_unique (dispute_id)');
                } catch (\Exception $e) {
                    // Constraint might already exist, ignore
                }
            } else {
                $table->string('dispute_id', 50)->unique()->after('id');
            }
            
            // Add new columns matching Razorpay structure (only if they don't exist)
            if (!Schema::hasColumn('disputes', 'payment_id')) {
                $table->unsignedBigInteger('payment_id')->nullable()->after('transaction_id');
            }
            if (!Schema::hasColumn('disputes', 'order_id')) {
                $table->string('order_id', 100)->nullable()->after('payment_id');
            }
            if (!Schema::hasColumn('disputes', 'card_network')) {
                $table->enum('card_network', ['VISA', 'MASTERCARD', 'RUPAY'])->nullable()->after('reason');
            }
            if (!Schema::hasColumn('disputes', 'currency')) {
                $table->string('currency', 3)->default('INR')->after('amount');
            }
            if (!Schema::hasColumn('disputes', 'due_by')) {
                $table->timestamp('due_by')->nullable()->after('currency');
            }
            if (!Schema::hasColumn('disputes', 'evidence_submitted')) {
                $table->boolean('evidence_submitted')->default(false)->after('due_by');
            }
            if (!Schema::hasColumn('disputes', 'dispute_fee')) {
                $table->decimal('dispute_fee', 10, 2)->default(0)->after('evidence_submitted');
            }
            if (!Schema::hasColumn('disputes', 'frozen_amount')) {
                $table->decimal('frozen_amount', 15, 2)->default(0)->after('dispute_fee');
            }
            if (!Schema::hasColumn('disputes', 'internal_notes')) {
                $table->text('internal_notes')->nullable()->after('frozen_amount');
            }
            
            // Update status enum to match Razorpay statuses (only if status column exists and needs updating)
            if (Schema::hasColumn('disputes', 'status')) {
                // Check if status enum needs updating
                $currentStatuses = DB::select("SHOW COLUMNS FROM disputes WHERE Field = 'status'");
                if (!empty($currentStatuses)) {
                    $statusEnum = $currentStatuses[0]->Type;
                    if (strpos($statusEnum, 'action_required') === false) {
                        $table->dropColumn('status');
                    }
                }
            }
        });

        // Add status enum if it was dropped or doesn't exist
        if (!Schema::hasColumn('disputes', 'status')) {
            Schema::table('disputes', function (Blueprint $table) {
                $table->enum('status', [
                    'action_required',
                    'under_review',
                    'insufficient_evidence',
                    'won',
                    'lost',
                    'closed'
                ])->default('action_required')->after('card_network');
            });
        }

        // Add indexes (only if they don't exist)
        Schema::table('disputes', function (Blueprint $table) {
            $indexes = DB::select("SHOW INDEXES FROM disputes WHERE Key_name != 'PRIMARY'");
            $existingIndexes = array_column($indexes, 'Key_name');
            
            if (!in_array('disputes_dispute_id_index', $existingIndexes) && Schema::hasColumn('disputes', 'dispute_id')) {
                $table->index('dispute_id');
            }
            if (!in_array('disputes_payment_id_index', $existingIndexes) && Schema::hasColumn('disputes', 'payment_id')) {
                $table->index('payment_id');
            }
            if (!in_array('disputes_order_id_index', $existingIndexes) && Schema::hasColumn('disputes', 'order_id')) {
                $table->index('order_id');
            }
            if (!in_array('disputes_status_index', $existingIndexes) && Schema::hasColumn('disputes', 'status')) {
                $table->index('status');
            }
            if (!in_array('disputes_due_by_index', $existingIndexes) && Schema::hasColumn('disputes', 'due_by')) {
                $table->index('due_by');
            }
            if (!in_array('disputes_evidence_submitted_index', $existingIndexes) && Schema::hasColumn('disputes', 'evidence_submitted')) {
                $table->index('evidence_submitted');
            }
        });
    }

    public function down(): void
    {
        Schema::table('disputes', function (Blueprint $table) {
            $table->dropColumn([
                'dispute_id',
                'payment_id',
                'order_id',
                'card_network',
                'currency',
                'due_by',
                'evidence_submitted',
                'dispute_fee',
                'frozen_amount',
                'internal_notes'
            ]);
            $table->dropIndex(['dispute_id']);
            $table->dropIndex(['payment_id']);
            $table->dropIndex(['order_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['due_by']);
            $table->dropIndex(['evidence_submitted']);
        });

        Schema::table('disputes', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->enum('status', ['open', 'needs_evidence', 'won', 'lost', 'closed'])->default('open');
            $table->string('evidence_url')->nullable();
            $table->text('notes')->nullable();
        });
    }
};

