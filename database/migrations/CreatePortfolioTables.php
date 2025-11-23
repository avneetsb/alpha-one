<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Portfolio Tables Migration
 *
 * Creates the `positions` and `holdings` tables to manage the user's portfolio.
 *
 * 1. Positions: Tracks intraday and open derivative positions.
 *    - Real-time P&L tracking (Realized vs Unrealized).
 *    - Net quantity monitoring (Buy Qty - Sell Qty).
 *
 * 2. Holdings: Tracks long-term equity delivery holdings.
 *    - Investment value tracking (Avg Cost vs LTP).
 *    - Overnight risk assessment.
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 */
class CreatePortfolioTables
{
    /**
     * Run the migration.
     *
     * Creates `positions` and `holdings` tables if they don't exist.
     *
     * Positions Table:
     * - Context (user_id, broker_id, instrument_id)
     * - Type (position_type: LONG/SHORT, product_type: MIS/NRML)
     * - Metrics (buy_qty, buy_avg, sell_qty, sell_avg, net_qty)
     * - P&L (realized_pnl, unrealized_pnl)
     *
     * Holdings Table:
     * - Context (user_id, broker_id, instrument_id)
     * - Metrics (qty, avg_cost, ltp, current_value)
     *
     * @return void
     */
    public function up()
    {
        if (! Capsule::schema()->hasTable('positions')) {
            Capsule::schema()->create('positions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('broker_id')->nullable();
                $table->unsignedBigInteger('instrument_id');

                // Position classification
                $table->string('position_type')->nullable(); // LONG, SHORT
                $table->string('product_type')->nullable(); // MIS, NRML, CNC

                // Quantity and Price tracking
                $table->integer('buy_qty')->default(0);
                $table->decimal('buy_avg', 10, 2)->default(0);
                $table->integer('sell_qty')->default(0);
                $table->decimal('sell_avg', 10, 2)->default(0);
                $table->integer('net_qty')->default(0);

                // Profit and Loss
                $table->decimal('realized_pnl', 10, 2)->default(0);
                $table->decimal('unrealized_pnl', 10, 2)->default(0);

                $table->timestamps();

                $table->index('instrument_id');
            });
        }

        if (! Capsule::schema()->hasTable('holdings')) {
            Capsule::schema()->create('holdings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('broker_id')->nullable();
                $table->unsignedBigInteger('instrument_id');

                // Holding metrics
                $table->integer('qty')->default(0);
                $table->decimal('avg_cost', 10, 2)->default(0);
                $table->decimal('ltp', 10, 2)->default(0); // Last Traded Price
                $table->decimal('current_value', 10, 2)->default(0); // qty * ltp

                $table->timestamps();

                $table->index('instrument_id');
            });
        }
    }

    /**
     * Reverse the migration.
     *
     * Drops the `positions` and `holdings` tables.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->dropIfExists('positions');
        Capsule::schema()->dropIfExists('holdings');
    }
}
