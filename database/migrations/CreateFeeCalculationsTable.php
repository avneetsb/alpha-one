<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Fee Calculations Table
 *
 * Stores per-trade/order brokerage and statutory fee breakdowns for reporting
 * and reconciliation. Includes asset class/segment context and computed totals.
 *
 * @example Get total fees for a broker on a day:
 * // DB::table('fee_calculations')->where('broker_id','dhan')->whereDate('calculation_timestamp','2024-01-15')->sum('total_fees');
 *
 * @accepted_values
 * - asset_class: 'equity', 'currency', 'commodity'
 * - segment: 'intraday', 'delivery', 'futures', 'options'
 * - broker_id: examples 'dhan', 'zerodha', 'upstox'
 *
 * @example Insert:
 * DB::table('fee_calculations')->insert([
 *   'order_id' => 1001,
 *   'trade_id' => 'TRD-20240115-0001',
 *   'broker_id' => 'dhan',
 *   'instrument_id' => 123,
 *   'asset_class' => 'equity',
 *   'segment' => 'intraday',
 *   'order_value' => 250000.00,
 *   'quantity' => 100,
 *   'brokerage' => 20.00,
 *   'stt' => 12.50,
 *   'exchange_transaction_charges' => 5.75,
 *   'gst' => 4.50,
 *   'sebi_charges' => 0.50,
 *   'stamp_duty' => 1.25,
 *   'total_fees' => 44.50,
 *   'calculation_timestamp' => now()
 * ]);
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('fee_calculations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('trade_id', 100)->nullable();
            $table->string('broker_id', 50);
            $table->foreignId('instrument_id')->constrained()->onDelete('cascade');
            $table->enum('asset_class', ['equity', 'currency', 'commodity']);
            $table->enum('segment', ['intraday', 'delivery', 'futures', 'options']);
            $table->decimal('order_value', 15, 2);
            $table->integer('quantity');
            $table->decimal('brokerage', 10, 2);
            $table->decimal('stt', 10, 2)->default(0);
            $table->decimal('ctt', 10, 2)->default(0);
            $table->decimal('exchange_transaction_charges', 10, 2);
            $table->decimal('gst', 10, 2);
            $table->decimal('sebi_charges', 10, 2);
            $table->decimal('stamp_duty', 10, 2);
            $table->decimal('total_fees', 10, 2);
            $table->timestamp('calculation_timestamp');
            $table->timestamps();
            
            $table->index('order_id');
            $table->index(['broker_id', 'asset_class']);
            $table->index('calculation_timestamp');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('fee_calculations');
    }
};
