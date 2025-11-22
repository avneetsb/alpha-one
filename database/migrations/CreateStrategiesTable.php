<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Migration for creating the strategies table that stores trading strategy configurations.
 * 
 * This migration establishes a comprehensive strategies table for managing algorithmic
 * trading strategies. The table supports multiple strategy categories, version control,
 * hyperparameter definitions, and active/inactive states. Each strategy can be
 * uniquely identified by name and includes metadata for strategy management.
 * 
 * @package Database\Migrations
 * @author  Trading Platform Team
 * @version 1.0.0
 * 
 * @accepted_values
 * - category: 'momentum', 'trend', 'mean_reversion', 'multi_indicator', 'custom'
 * - version: semantic string (e.g., '1.0.0')
 * - is_active: true/false
 * 
 * @example
 * // Table structure created:
 * // - id: Primary key
 * // - name: Unique strategy name
 * // - class_name: PHP class name implementing the strategy
 * // - description: Optional strategy description
 * // - category: Strategy category ('momentum', 'trend', 'mean_reversion', 'multi_indicator', 'custom')
 * // - version: Strategy version (default '1.0.0')
 * // - hyperparameters_schema: JSON schema defining configurable parameters
 * // - created_by: Optional creator identifier
 * // - is_active: Boolean flag for strategy availability
 * // - timestamps: created_at and updated_at
 * // - indexes: name, category, is_active
 * 
 * // Usage example:
 * $strategy = Strategy::create([
 *     'name' => 'RSI_Momentum_v2',
 *     'class_name' => 'App\Domain\Strategy\RSIMomentumStrategy',
 *     'description' => 'RSI-based momentum strategy with dynamic thresholds',
 *     'category' => 'momentum',
 *     'version' => '2.1.0',
 *     'hyperparameters_schema' => json_encode([
 *         'rsi_period' => ['type' => 'integer', 'min' => 10, 'max' => 50, 'default' => 14],
 *         'oversold_threshold' => ['type' => 'number', 'min' => 10, 'max' => 40, 'default' => 30],
 *         'overbought_threshold' => ['type' => 'number', 'min' => 60, 'max' => 90, 'default' => 70]
 *     ]),
 *     'created_by' => 'trader@example.com',
 *     'is_active' => true
 * ]);
 * 
 * // Query examples:
 * $activeStrategies = Strategy::where('is_active', true)->get();
 * $momentumStrategies = Strategy::where('category', 'momentum')->get();
 * $strategy = Strategy::where('name', 'RSI_Momentum_v2')->first();
 */
return new class extends Migration
{
    /**
     * Run the migrations to create the strategies table.
     * 
     * Creates a comprehensive strategies table with the following structure:
     * - Primary key auto-incrementing ID
     * - Unique strategy name for identification
     * - Class name linking to PHP implementation
     * - Optional description for strategy documentation
     * - Category enumeration for strategy classification
     * - Version string with semantic versioning support
     * - JSON schema for hyperparameter definitions
     * - Creator reference for accountability
     * - Active flag for strategy availability
     * - Automatic timestamps for audit trail
     * - Strategic indexes for query performance
     * 
     * The hyperparameters_schema field stores a JSON structure that defines
     * all configurable parameters for the strategy, including their types,
     * ranges, and default values. This enables dynamic strategy configuration
     * and validation.
     * 
     * @return void
     * 
     * @throws \Exception If table creation fails
     * 
     * @example
     * // Hyperparameters schema structure:
     * {
     *   "rsi_period": {
     *     "type": "integer",
     *     "min": 10,
     *     "max": 50,
     *     "default": 14,
     *     "description": "RSI calculation period"
     *   },
     *   "oversold_threshold": {
     *     "type": "number",
     *     "min": 10,
     *     "max": 40,
     *     "default": 30,
     *     "description": "Oversold threshold for buy signals"
     *   }
     * }
     * 
     * // Category options and their purposes:
     * // - momentum: Strategies based on price momentum indicators
     * // - trend: Strategies that follow market trends
     * // - mean_reversion: Strategies betting on price reversions
     * // - multi_indicator: Complex strategies using multiple indicators
     * // - custom: User-defined or experimental strategies
     */
    public function up(): void
    {
        Capsule::schema()->create('strategies', function (Blueprint $table) {
            // Primary key for unique strategy identification
            $table->id();
            
            // Unique strategy name for identification and reference
            $table->string('name')->unique();
            
            // PHP class name that implements the strategy logic
            $table->string('class_name');
            
            // Optional description for strategy documentation and understanding
            $table->text('description')->nullable();
            
            // Strategy category for classification and filtering
            $table->enum('category', ['momentum', 'trend', 'mean_reversion', 'multi_indicator', 'custom']);
            
            // Version string supporting semantic versioning (default: '1.0.0')
            $table->string('version', 50)->default('1.0.0');
            
            // JSON schema defining configurable hyperparameters and their constraints
            $table->json('hyperparameters_schema'); // Definition of available hyperparameters
            
            // Optional creator reference for accountability and ownership
            $table->string('created_by')->nullable();
            
            // Boolean flag indicating if strategy is available for use (default: true)
            $table->boolean('is_active')->default(true);
            
            // Automatic timestamps for audit trail
            $table->timestamps();
            
            // Performance indexes for common query patterns
            $table->index('name');     // Fast strategy name lookups
            $table->index('category'); // Fast category-based filtering
            $table->index('is_active'); // Fast active strategy queries
        });
    }

    /**
     * Reverse the migrations by dropping the strategies table.
     * 
     * Removes the strategies table and all associated data. This operation
     * is destructive and will permanently delete all strategy configurations.
     * 
     * @return void
     * 
     * @throws \Exception If table drop operation fails
     * 
     * @warning This will permanently delete all strategy data including
     *          hyperparameter schemas and configuration history. Ensure proper
     *          backups are taken before running this migration rollback.
     * 
     * @note Consider the impact on dependent tables such as backtest results,
     *       optimization runs, and live trading sessions before executing
     *       this rollback.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('strategies');
    }
};
