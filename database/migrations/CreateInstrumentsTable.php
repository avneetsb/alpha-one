<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Instruments Table
 *
 * Stores tradable instrument master records across exchanges and derivatives.
 * Includes option metadata (series, strike, type) and trading flags.
 *
 * @example Ensure uniqueness per exchange+symbol for lookups.
 *
 * @accepted_values
 * - option_type: 'CE' (Call), 'PE' (Put), '' (non-option)
 * - asm_gsm_flag: 'N' (None), 'R' (Restricted), 'Y' (Yes)
 * - instrument_type: examples 'EQ', 'FUT', 'OPT'
 */
class CreateInstrumentsTable
{
    public function up()
    {
        Capsule::schema()->create('instruments', function (Blueprint $table) {
            $table->id();
            $table->string('exchange');
            $table->string('symbol');
            $table->string('instrument_type');
            $table->string('series')->nullable();
            $table->integer('lot_size');
            $table->dateTime('expiry_date')->nullable();
            $table->decimal('strike_price', 10, 2)->nullable();
            $table->enum('option_type', ['CE', 'PE', ''])->nullable();
            $table->decimal('tick_size', 10, 2);
            $table->boolean('bracket_flag')->default(false);
            $table->boolean('cover_flag')->default(false);
            $table->enum('asm_gsm_flag', ['N', 'R', 'Y'])->default('N');
            $table->char('buy_sell_indicator', 1)->default('');
            $table->dateTime('updated_at')->useCurrent();

            $table->unique(['exchange', 'symbol']);
            $table->index(['instrument_type', 'expiry_date']);
            $table->index('strike_price');
            $table->index('option_type');
        });
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('instruments');
    }
}
