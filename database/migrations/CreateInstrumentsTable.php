<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Instruments Table Migration
 *
 * Creates the `instruments` table, which acts as the master directory for all
 * tradable assets on the platform. It normalizes data across different exchanges
 * (NSE, BSE, MCX) and instrument types (Equity, Futures, Options).
 *
 * Key features:
 * - Unique constraint on Exchange + Symbol to prevent duplicates.
 * - Support for derivatives with expiry, strike price, and option type.
 * - Flags for trading restrictions (ASM/GSM) and special order types (Bracket/Cover).
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 */
class CreateInstrumentsTable
{
    /**
     * Run the migration.
     *
     * Creates the `instruments` table with columns for:
     * - Identification (exchange, symbol, instrument_type)
     * - Derivative details (series, lot_size, expiry, strike, option_type)
     * - Trading parameters (tick_size, lot_size)
     * - Capability flags (bracket_flag, cover_flag)
     * - Regulatory flags (asm_gsm_flag)
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->create('instruments', function (Blueprint $table) {
            $table->id();

            // Core identification
            $table->string('exchange'); // NSE, BSE, MCX
            $table->string('symbol'); // RELIANCE, NIFTY24JANFUT
            $table->string('instrument_type'); // EQ, FUT, OPT

            // Derivative specifics
            $table->string('series')->nullable(); // EQ, BE, BL (for equities)
            $table->integer('lot_size'); // 1 for EQ, variable for F&O
            $table->dateTime('expiry_date')->nullable();
            $table->decimal('strike_price', 10, 2)->nullable();
            $table->enum('option_type', ['CE', 'PE', ''])->nullable(); // Call, Put, or None

            // Trading rules
            $table->decimal('tick_size', 10, 2); // Minimum price movement (e.g., 0.05)
            $table->boolean('bracket_flag')->default(false); // Supports Bracket Orders?
            $table->boolean('cover_flag')->default(false); // Supports Cover Orders?
            $table->enum('asm_gsm_flag', ['N', 'R', 'Y'])->default('N'); // Surveillance status

            // Market status
            $table->char('buy_sell_indicator', 1)->default(''); // Open for Buy/Sell?

            $table->dateTime('updated_at')->useCurrent();

            // Constraints and Indexes
            $table->unique(['exchange', 'symbol']);
            $table->index(['instrument_type', 'expiry_date']);
            $table->index('strike_price');
            $table->index('option_type');
        });
    }

    /**
     * Reverse the migration.
     *
     * Drops the `instruments` table.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->dropIfExists('instruments');
    }
}
