<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Fee Calculations Migration
 *
 * Creates the `fee_calculations` table to store detailed breakdowns of
 * brokerage and statutory charges for every order. This data is critical for:
 * 1. Accurate P&L reporting (Net vs Gross).
 * 2. Tax reporting (GST, STT, Stamp Duty).
 * 3. Reconciliation with broker contract notes.
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
     * Creates the `fee_calculations` table with columns for:
     * - Transaction context (order_id, trade_id, broker, instrument)
     * - Classification (asset_class, segment)
     * - Base values (order_value, quantity)
     * - Fee components (brokerage, STT, exchange charges, GST, SEBI, stamp duty)
     * - Totals and timestamps
     */
    public function up(): void
    {
        Capsule::schema()->create('fee_calculations', function (Blueprint $table) {
            $table->id();

            // Link to the order (nullable in case of manual fee entries)
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade');

            // Broker's trade ID for reconciliation
            $table->string('trade_id', 100)->nullable();

            // Broker identifier (e.g., 'dhan', 'zerodha')
            $table->string('broker_id', 50);

            // Instrument reference
            $table->foreignId('instrument_id')->constrained()->onDelete('cascade');

            // Asset class and segment for fee rule application
            $table->enum('asset_class', ['equity', 'currency', 'commodity']);
            $table->enum('segment', ['intraday', 'delivery', 'futures', 'options']);

            // Transaction details
            $table->decimal('order_value', 15, 2);
            $table->integer('quantity');

            // Fee components breakdown
            $table->decimal('brokerage', 10, 2);
            $table->decimal('stt', 10, 2)->default(0); // Securities Transaction Tax
            $table->decimal('ctt', 10, 2)->default(0); // Commodities Transaction Tax
            $table->decimal('exchange_transaction_charges', 10, 2);
            $table->decimal('gst', 10, 2); // Goods and Services Tax
            $table->decimal('sebi_charges', 10, 2); // SEBI Turnover Fees
            $table->decimal('stamp_duty', 10, 2); // State-wise Stamp Duty

            // Total calculated fees
            $table->decimal('total_fees', 10, 2);

            // When the calculation was performed
            $table->timestamp('calculation_timestamp');
            $table->timestamps();

            // Indexes for reporting and reconciliation
            $table->index('order_id');
            $table->index(['broker_id', 'asset_class']);
            $table->index('calculation_timestamp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `fee_calculations` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('fee_calculations');
    }
};
