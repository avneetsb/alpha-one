# Hyperparameter Optimization Guide

## Overview

The platform now includes a production-grade **Hyperparameter Optimization Engine** using genetic algorithms to automatically find the best parameters for your trading strategies.

## Architecture

### Components

1. **HyperparameterOptimizer** - Genetic algorithm engine
   - Tournament selection
   - Crossover and mutation operations
   - DNA encoding/decoding
   - Multi-objective fitness optimization

2. **Strategy Base Class** - Extended with hyperparameter support
   - `hyperparameters()` - Define optimizable parameters
   - `dna()` - Use optimized parameters
   - `hp[]` - Access hyperparameters in strategy

3. **OptimizeStrategyCommand** - CLI interface for running optimization

## Complete Indicator Library

### Trend Indicators (9)
- SMA, EMA, DEMA, TEMA, HMA
- Donchian Channels, Keltner Channel
- SuperTrend, Bollinger Bands

### Momentum Indicators (7)
- RSI, MACD, Stochastic, ADX
- CCI, Williams %R, TSI

### Volume Indicators (3)
- MFI, OBV, VWAP

### Volatility Indicators (2)
- ATR, Bollinger Bands

**Total**: 20+ production-ready indicators

## Usage

### Step 1: Define Hyperparameters

Create a strategy with optimizable hyperparameters:

```php
public function hyperparameters(): array
{
    return [
        ['name' => 'rsi_period', 'type' => 'int', 'min' => 10, 'max' => 20, 'default' => 14],
        ['name' => 'rsi_oversold', 'type' => 'int', 'min' => 25, 'max' => 35, 'default' => 30],
        ['name' => 'stop_loss_atr', 'type' => 'float', 'min' => 1.5, 'max' => 3.0, 'step' => 0.5, 'default' => 2.0],
        ['name' => 'trend_method', 'type' => 'categorical', 'options' => ['supertrend', 'ema'], 'default' => 'supertrend'],
    ];
}
```

**Parameter Types**:
- `int`: Integer range with min/max
- `float`: Float range with min/max and optional step
- `categorical`: Choose from list of options

### Step 2: Access Hyperparameters in Strategy

```php
public function onCandle(Candle $candle): ?Signal
{
    $rsi = $this->indicators->calculate('rsi', $this->candles, [
        'period' => $this->hp['rsi_period'],  // Use hyperparameter
    ]);

    if ($rsi < $this->hp['rsi_oversold']) {
        return new Signal('BUY', ...);
    }
}
```

### Step 3: Run Optimization

```bash
php bin/console cli:strategy:optimize \
  "TradingPlatform\Domain\Strategy\MultiIndicatorStrategy" \
  --population=50 \
  --generations=100 \
  --initial-capital=100000
```

**Options**:
- `--population`: Population size (default: 50)
- `--generations`: Number of generations (default: 100)
- `--initial-capital`: Starting capital (default: 100000)
- `--data-file`: Historical data CSV file (optional)

### Step 4: Use Optimized Parameters

After optimization completes, you'll get output like:

```
Best DNA: i14_i30_f2.00_c0
Best Fitness: 145.67

Optimized Parameters:
  rsi_period: 14
  rsi_oversold: 30
  stop_loss_atr: 2.00
  trend_method: supertrend
```

Add the DNA to your strategy:

```php
public function dna(): ?string
{
    return 'i14_i30_f2.00_c0';  // Paste optimized DNA here
}
```

## Multi-Indicator Demo Strategy

The platform includes `MultiIndicatorStrategy.php` - a production-ready strategy using:

- **SuperTrend** (Trend identification)
- **RSI** (Momentum)
- **MFI** (Volume strength)
- **ATR** (Volatility for risk management)

**10 Optimizable Parameters**:
1. SuperTrend period (7-14)
2. SuperTrend multiplier (2.0-4.0)
3. RSI period (10-20)
4. RSI oversold threshold (25-35)
5. RSI overbought threshold (65-75)
6. MFI period (10-20)
7. MFI oversold threshold (15-25)
8. MFI overbought threshold (75-85)
9. ATR period (10-20)
10. ATR multiplier for stops (1.5-3.0)

**Trading Logic**:
- **Buy**: Uptrend (SuperTrend) + Oversold (RSI + MFI)
- **Sell**: Downtrend (SuperTrend) + Overbought (RSI + MFI)
- **Risk Management**: ATR-based stop-loss and take-profit

## Genetic Algorithm Details

### How It Works

1. **Initialize Population**: Create random parameter combinations
2. **Evaluate Fitness**: Backtest each individual
3. **Selection**: Tournament selection chooses best performers
4. **Crossover**: Combine genes from two parents
5. **Mutation**: Random changes to maintain diversity
6. **Repeat**: Evolve over multiple generations

### Fitness Function

Multi-objective optimization considering:

```
Fitness = Total Return (%) 
        - Trade Penalty (if trades > 100)
        - Drawdown Penalty (0.5 × Max Drawdown %)
```

This balances:
- **Profitability** (maximize returns)
- **Efficiency** (minimize overtrading)
- **Risk** (minimize drawdowns)

### DNA Encoding

DNA string format: `{type}{value}_{type}{value}_...`

- `i{value}`: Integer (e.g., `i14` = 14)
- `f{value}`: Float (e.g., `f2.50` = 2.5)
- `c{index}`: Categorical (e.g., `c0` = first option)

Example: `i14_i30_f2.00_c0`
- Parameter 1: int = 14
- Parameter 2: int = 30
- Parameter 3: float = 2.00
- Parameter 4: categorical = index 0

## Best Practices

### 1. Avoid Overfitting
- Use longer time periods (6+ months)
- Validate on out-of-sample data
- Don't optimize on last 1-2 weeks

### 2. Reasonable Parameter Ranges
- Too wide: slow convergence
- Too narrow: miss optimal values
- Use domain knowledge to set ranges

### 3. Population & Generations
- **Small dataset**: population=30, generations=50
- **Medium dataset**: population=50, generations=100
- **Large dataset**: population=100, generations=200

### 4. Multi-Objective Balance
- Set trade penalties to avoid overtrading
- Consider transaction costs in fitness
- Prioritize risk-adjusted returns

### 5. Validation
Always validate optimized strategies:
```bash
# 1. Optimize on training data (60%)
# 2. Validate on test data (20%)
# 3. Final check on holdout data (20%)
```

## Creating Custom Strategies

```php
namespace TradingPlatform\Domain\Strategy;

class MyCustomStrategy extends Strategy
{
    public function hyperparameters(): array
    {
        return [
            ['name' => 'fast_ma', 'type' => 'int', 'min' => 5, 'max' => 20, 'default' => 10],
            ['name' => 'slow_ma', 'type' => 'int', 'min' => 20, 'max' => 100, 'default' => 50],
        ];
    }

    public function dna(): ?string
    {
        // Return null during optimization
        // After optimization, paste best DNA:
        // return 'i10_i50';
        return null;
    }

    public function onCandle(Candle $candle): ?Signal
    {
        // Use $this->hp['fast_ma'] and $this->hp['slow_ma']
        // Your strategy logic here
    }
}
```

## Performance Tips

1. **Parallel Processing**: Run multiple optimizations in parallel
2. **Early Stopping**: Stop if fitness plateaus for 20+ generations
3. **Warm Start**: Use previous best DNA as starting point
4. **Incremental Optimization**: Optimize subsets of parameters

## Troubleshooting

### Low Fitness Scores
- Check if strategy has positive PNL on default parameters
- Verify indicator calculations are correct
- Ensure sufficient historical data

### Slow Convergence
- Reduce population size
- Narrow parameter ranges
- Increase mutation rate (0.15-0.2)

### Overfitting
- Use walk-forward optimization
- Validate on multiple time periods
- Add regularization to fitness function

## Example Output

```
Starting Hyperparameter Optimization
Strategy: MultiIndicatorStrategy
Population: 50
Generations: 100

Loaded 10000 candles
Optimizing 10 parameters

Running optimization...
Generation 10: Best Fitness = 85.23
Generation 20: Best Fitness = 112.45
Generation 30: Best Fitness = 128.67
...
Generation 100: Best Fitness = 156.89

Optimization Complete!
Duration: 245.67 seconds

Best DNA: i10_f3.00_i14_i30_i70_i14_i20_i80_i14_f2.00
Best Fitness: 156.89

Optimized Parameters:
  supertrend_period: 10
  supertrend_multiplier: 3.00
  rsi_period: 14
  rsi_oversold: 30
  rsi_overbought: 70
  mfi_period: 14
  mfi_oversold: 20
  mfi_overbought: 80
  atr_period: 14
  atr_multiplier: 2.00
```

## Integration with Backtesting

The optimizer automatically integrates with the backtesting engine:

1. Each DNA candidate is backtested
2. Performance metrics are calculated
3. Fitness score combines multiple objectives
4. Best performers evolve to next generation

This ensures optimized parameters work in realistic trading conditions.

## Conclusion

The hyperparameter optimization engine provides:
- ✅ Automated parameter tuning
- ✅ Multi-objective optimization
- ✅ Genetic algorithm efficiency
- ✅ Production-ready implementation
- ✅ Integration with 20+ indicators
- ✅ Comprehensive backtesting

Start optimizing your strategies today!
