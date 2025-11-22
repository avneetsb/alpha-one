<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Portfolio Tables (Positions & Holdings)
 *
 * Defines core portfolio storage: open positions and equity holdings,
 * including quantities, averages, and PnL. Created only if absent.
 *
 * @example Query net positions by instrument:
 * // DB::table('positions')->where('instrument_id',$id)->sum('net_qty');
 *
 * @accepted_values
 * - position_type (examples): 'LONG', 'SHORT'
 * - product_type (examples): 'CNC', 'MIS', 'NRML'
 */
class CreatePortfolioTables
{
    public function up()
    {
        if (!Capsule::schema()->hasTable('positions')) {
            Capsule::schema()->create('positions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('broker_id')->nullable();
                $table->unsignedBigInteger('instrument_id');
                $table->string('position_type')->nullable();
                $table->string('product_type')->nullable();
                $table->integer('buy_qty')->default(0);
                $table->decimal('buy_avg', 10, 2)->default(0);
                $table->integer('sell_qty')->default(0);
                $table->decimal('sell_avg', 10, 2)->default(0);
                $table->integer('net_qty')->default(0);
                $table->decimal('realized_pnl', 10, 2)->default(0);
                $table->decimal('unrealized_pnl', 10, 2)->default(0);
                $table->timestamps();

                $table->index('instrument_id');
            });
        }

        if (!Capsule::schema()->hasTable('holdings')) {
            Capsule::schema()->create('holdings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('broker_id')->nullable();
                $table->unsignedBigInteger('instrument_id');
                $table->integer('qty')->default(0);
                $table->decimal('avg_cost', 10, 2)->default(0);
                $table->decimal('ltp', 10, 2)->default(0);
                $table->decimal('current_value', 10, 2)->default(0);
                $table->timestamps();

                $table->index('instrument_id');
            });
        }
    }

    public function down()
    {
        Capsule::schema()->dropIfExists('positions');
        Capsule::schema()->dropIfExists('holdings');
    }
}
