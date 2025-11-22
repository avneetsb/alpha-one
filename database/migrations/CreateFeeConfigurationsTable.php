<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Fee Configurations Table
 *
 * Versioned fee rule definitions per broker, asset class, and segment.
 * Enables time-bounded fee application during calculations.
 *
 * @example Active fee config lookup:
 * // DB::table('fee_configurations')->where('broker_id','dhan')->where('asset_class','equity')->whereDate('effective_from','<=',now())->where(function($q){$q->whereNull('effective_to')->orWhereDate('effective_to','>=',now());})->first();
 *
 * @accepted_values
 * - asset_class: 'equity', 'currency', 'commodity'
 * - segment: 'intraday', 'delivery', 'futures', 'options'
 * - broker_id: examples 'dhan', 'zerodha', 'upstox'
 *
 * @example fee_rules JSON:
 * {
 *   "brokerage_flat": 20.0,
 *   "stt_percent": 0.00125,
 *   "exchange_txn_charges_percent": 0.000032,
 *   "gst_percent": 0.18,
 *   "sebi_charges_percent": 0.000001,
 *   "stamp_duty_percent": 0.000015
 * }
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('fee_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('broker_id', 50);
            $table->enum('asset_class', ['equity', 'currency', 'commodity']);
            $table->enum('segment', ['intraday', 'delivery', 'futures', 'options']);
            $table->json('fee_rules');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->string('version', 20)->default('1.0');
            $table->timestamps();
            
            $table->index(['broker_id', 'asset_class', 'segment']);
            $table->index('effective_from');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('fee_configurations');
    }
};
