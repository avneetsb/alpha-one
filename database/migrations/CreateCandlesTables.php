<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Migration for creating multiple candlestick data tables with different time intervals.
 * 
 * This migration creates a comprehensive set of candlestick tables for storing
 * OHLCV (Open, High, Low, Close, Volume) market data across multiple timeframes.
 * The implementation dynamically generates tables for standard trading intervals
 * including 1 minute, 5 minutes, 15 minutes, 25 minutes, 1 hour, and 1 day candles.
 * 
 * Each candle table is optimized for financial data storage with appropriate
 * decimal precision for prices, big integer support for volume data, and
 * unique constraints to prevent duplicate candle entries.
 * 
 * @package Database\Migrations
 * @author  Trading Platform Team
 * @version 1.0.0
 * 
 * @accepted_values
 * - intervals: '1m', '5m', '15m', '25m', '60m', '1d' (table names: candles_{interval})
 * 
 * @example
 * // Tables created:
 * // - candles_1m: 1-minute candlestick data
 * // - candles_5m: 5-minute candlestick data
 * // - candles_15m: 15-minute candlestick data
 * // - candles_25m: 25-minute candlestick data
 * // - candles_60m: 1-hour candlestick data
 * // - candles_1d: 1-day candlestick data
 * 
 * // Each table contains:
 * // - id: Primary key
 * // - instrument_id: Reference to trading instrument
 * // - ts: Timestamp of the candle period
 * // - open: Opening price (10,2 decimal precision)
 * // - high: Highest price during period (10,2 decimal precision)
 * // - low: Lowest price during period (10,2 decimal precision)
 * // - close: Closing price (10,2 decimal precision)
 * // - volume: Trading volume (big integer)
 * // - oi: Open interest (nullable, big integer)
 * // - checksum: Data integrity checksum (nullable, string)
 * // - unique constraint on (instrument_id, ts)
 * 
 * // Usage example:
 * // Insert a 5-minute candle:
 * DB::table('candles_5m')->insert([
 *     'instrument_id' => 123,
 *     'ts' => '2024-01-15 14:30:00',
 *     'open' => 150.25,
 *     'high' => 151.80,
 *     'low' => 149.90,
 *     'close' => 151.45,
 *     'volume' => 125000,
 *     'oi' => 50000,
 *     'checksum' => 'abc123def456'
 * ]);
 * 
 * // Query example:
 * $candles = DB::table('candles_15m')
 *     ->where('instrument_id', 123)
 *     ->whereBetween('ts', ['2024-01-15 09:15:00', '2024-01-15 15:30:00'])
 *     ->orderBy('ts')
 *     ->get();
 * 
 * @note The migration checks for existing tables before creation to prevent
 *       conflicts in environments where tables may already exist.
 * 
 * @important All price fields use decimal(10,2) for precise financial calculations.
 *           Volume and open interest use bigInteger to handle large trading volumes.
 */
class CreateCandlesTables
{
    /**
     * Run the migration to create multiple candlestick tables.
     * 
     * Creates six candlestick tables for different time intervals:
     * - 1 minute (candles_1m): High-frequency trading data
     * - 5 minutes (candles_5m): Short-term analysis
     * - 15 minutes (candles_15m): Intraday trading
     * - 25 minutes (candles_25m): Custom interval for specific strategies
     * - 60 minutes/1 hour (candles_60m): Hourly analysis
     * - 1 day (candles_1d): Daily and long-term analysis
     * 
     * Each table follows financial data best practices with:
     * - Unique constraint on (instrument_id, ts) to prevent duplicates
     * - Decimal precision suitable for financial calculations
     * - Big integer support for large volume data
     * - Optional fields for open interest and data integrity
     * 
     * The migration includes safety checks to avoid creating tables
     * that already exist, making it safe for incremental deployments.
     * 
     * @return void
     * 
     * @throws \Exception If table creation fails for any interval
     * 
     * @example
     * // Table usage scenarios:
     * // 1. High-frequency scalping strategies: Use candles_1m
     * // 2. Day trading strategies: Use candles_5m or candles_15m
     * // 3. Swing trading: Use candles_60m or candles_1d
     * // 4. Multi-timeframe analysis: Join across multiple tables
     * 
     * // Data integrity example:
     * // The checksum field can store MD5/SHA hashes of raw data
     * // to verify data integrity during ETL processes:
     * $checksum = md5($rawData);
     * DB::table('candles_5m')->where('id', $candleId)->update(['checksum' => $checksum]);
     * 
     * @note The 25-minute interval is included for strategies that require
     *       a custom timeframe different from standard intervals.
     * 
     * @important Each table enforces uniqueness on (instrument_id, ts) combination
     *           to prevent duplicate candle data which could corrupt analysis.
     */
    public function up()
    {
        // Define standard trading intervals supported by the platform
        $intervals = ['1m', '5m', '15m', '25m', '60m', '1d'];

        // Create a candlestick table for each time interval
        foreach ($intervals as $interval) {
            $tableName = "candles_{$interval}";
            
            // Safety check: only create if table doesn't already exist
            if (!Capsule::schema()->hasTable($tableName)) {
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
     * Removes all candlestick tables created by this migration. This operation
     * is destructive and will permanently delete all historical market data.
     * 
     * @return void
     * 
     * @throws \Exception If table drop operation fails for any interval
     * 
     * @warning This will permanently delete all candlestick data across all
     *          time intervals. Ensure proper backups of historical data are
     *          taken before running this migration rollback.
     * 
     * @note The operation removes tables in the same order they were created,
     *       ensuring consistent cleanup of the database schema.
     * 
     * @important Consider the impact on dependent systems such as:
     *           - Backtesting engines using historical data
     *           - Live trading strategies relying on candle data
     *           - Analytics and reporting systems
     *           - Charting and visualization components
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
