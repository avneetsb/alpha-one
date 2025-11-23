<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Fee Configurations Migration
 *
 * Creates the `fee_configurations` table, which acts as a versioned rule engine
 * for fee calculations. It allows the platform to support:
 * 1. Multiple brokers with different fee structures.
 * 2. Historical fee changes (e.g., government tax updates).
 * 3. Different rates per asset class and segment (Equity vs F&O).
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the `fee_configurations` table with columns for:
     * - Context (broker_id, asset_class, segment)
     * - Rules (fee_rules JSON blob)
     * - Validity period (effective_from, effective_to)
     * - Versioning (version string)
     *
     * The `fee_rules` JSON column stores the specific rates and fixed costs:
     * ```json
     * {
     *   "brokerage_type": "flat", // or "percent"
     *   "brokerage_value": 20.0,
     *   "stt_percent": 0.001,
     *   "gst_percent": 0.18,
     *   "min_brokerage": 0
     * }
     * ```
     */
    public function up(): void
    {
        Capsule::schema()->create('fee_configurations', function (Blueprint $table) {
            $table->id();

            // Configuration context
            $table->string('broker_id', 50);
            $table->enum('asset_class', ['equity', 'currency', 'commodity']);
            $table->enum('segment', ['intraday', 'delivery', 'futures', 'options']);

            // The core rules definition
            $table->json('fee_rules');

            // Validity period for historical accuracy
            $table->date('effective_from');
            $table->date('effective_to')->nullable(); // Null means currently active

            // Semantic versioning for rule sets
            $table->string('version', 20)->default('1.0');

            $table->timestamps();

            // Indexes for efficient lookup of active rules
            $table->index(['broker_id', 'asset_class', 'segment']);
            $table->index('effective_from');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `fee_configurations` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('fee_configurations');
    }
};
