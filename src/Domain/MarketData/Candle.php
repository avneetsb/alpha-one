<?php

namespace TradingPlatform\Domain\MarketData;

use Illuminate\Database\Eloquent\Model;

/**
 * Market Candle Model
 *
 * Represents an OHLCV candle for a given instrument and interval. The table
 * name is dynamic (e.g., `candles_1m`, `candles_5m`) and should be set via
 * `setTable` for the desired timeframe.
 *
 * @package TradingPlatform\Domain\MarketData
 * @version 1.0.0
 *
 * @example Load 5m candles:
 * $candle = (new Candle())->setTable('candles_5m')->find($id);
 */
class Candle extends Model
{
    // Table name is dynamic based on interval, so we might need a factory or setTable
    protected $guarded = [];
    public $timestamps = false;

    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    protected $casts = [
        'instrument_id' => 'integer',
        'ts' => 'datetime',
        'open' => 'decimal:2',
        'high' => 'decimal:2',
        'low' => 'decimal:2',
        'close' => 'decimal:2',
        'volume' => 'integer',
        'oi' => 'integer',
    ];

    /**
     * Create Candle from array (for backtesting).
     */
    public static function fromArray(array $data): self
    {
        $candle = new self();
        $candle->fill($data);
        return $candle;
    }
}
