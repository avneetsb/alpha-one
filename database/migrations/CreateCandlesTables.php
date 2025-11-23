<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Candlestick Tables Migration
 *
 * This migration dynamically creates multiple tables to store OHLCV (Open, High,
 * Low, Close, Volume) market data for different time intervals.
 *
 * Instead of a single massive table, the platform uses a "table-per-interval"
 * strategy (e.g., `candles_1m`, `candles_5m`, `candles_1d`) to improve query
 * performance and data management.
 *
 * Supported Intervals:
 * - 1m: 1-minute candles (High frequency)
 * - 5m: 5-minute candles (Intraday analysis)
 * - 15m: 15-minute candles (Intraday trends)
 * - 25m: 25-minute candles (Custom strategy interval)
 * - 60m: 1-hour candles (Swing trading)
 * - 1d: Daily candles (Long-term trends)
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 */
class CreateCandlesTables
{
    /**
     * Run the migration to create multiple candlestick tables.
     *
     * Iterates through the defined intervals and creates a table for each if it
     * doesn't already exist. Each table schema is identical and optimized for
     * financial time-series data.
     *
     * Table Schema:
     * - id: Primary Key
     * - instrument_id: Foreign Key to instruments table
     * - ts: Timestamp of the candle open time
     * - open, high, low, close: Price data (Decimal 10,2)
     * - volume: Traded volume (BigInteger)
     * - oi: Open Interest (BigInteger, optional)
     * - checksum: Data integrity hash
     *
     * Constraints:
     * - Unique(instrument_id, ts): Ensures no duplicate candles for the same time.
     *
     * @return void
     */
    public function up()
    {
        // Define standard trading intervals supported by the platform
        $intervals = ['1m', '5m', '15m', '25m', '60m', '1d'];

        // Create a candlestick table for each time interval
        foreach ($intervals as $interval) {
            $tableName = "candles_{$interval}";

            // Safety check: only create if table doesn't already exist
            if (! Capsule::schema()->hasTable($tableName)) {
                Capsule::schema()->create($tableName, function (Blueprint $table) {
                    // Primary key for unique candle identification
                    $table->id();

                    // Trading instrument reference (foreign key concept)
                    $table->unsignedBigInteger('instrument_id');

                    // Timestamp marking the start of the candle period
                    $table->dateTime('ts');

                    // Opening price with 2 decimal precision for financial accuracy
                    $table->decimal('open', 10, 2);

                    // Highest price during the candle period
                    $table->decimal('high', 10, 2);

                    // Lowest price during the candle period
                    $table->decimal('low', 10, 2);

                    // Closing price with precise decimal handling
                    $table->decimal('close', 10, 2);

                    // Trading volume using big integer for large volume handling
                    $table->bigInteger('volume');

                    // Open interest (optional) for derivatives data
                    $table->bigInteger('oi')->nullable();

                    // Data integrity checksum for verification purposes
                    $table->string('checksum')->nullable();

                    // Unique constraint preventing duplicate candles
                    $table->unique(['instrument_id', 'ts']);
                });
            }
        }
    }

    /**
     * Reverse the migration by dropping all candlestick tables.
     *
     * Iterates through the defined intervals and drops the corresponding tables.
     *
     * @warning This action is destructive and will delete all historical market data.
     *
     * @return void
     */
    public function down()
    {
        // Define intervals in the same order as creation for consistency
        $intervals = ['1m', '5m', '15m', '25m', '60m', '1d'];

        // Drop each candlestick table
        foreach ($intervals as $interval) {
            Capsule::schema()->dropIfExists("candles_{$interval}");
        }
    }
}
