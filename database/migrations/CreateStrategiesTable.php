<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * Strategies Table Migration
 *
 * Creates the `strategies` table, which serves as the registry for all trading
 * algorithms available on the platform.
 *
 * This table defines the "blueprint" of a strategy (e.g., "RSI Momentum"),
 * while `strategy_configurations` defines specific instances (e.g., "RSI 14 Period").
 *
 * Key Features:
 * - Versioning: Supports semantic versioning for strategy evolution.
 * - Categorization: Groups strategies by logic (Momentum, Mean Reversion).
 * - Schema Definition: Stores the JSON schema for valid hyperparameters, ensuring
 *   that configurations are validated before use.
 *
 * @author  Trading Platform Team
 *
 * @version 1.0.0
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates the `strategies` table with columns for:
     * - Identity (name, class_name, description)
     * - Classification (category, version)
     * - Configuration Contract (hyperparameters_schema)
     * - Governance (created_by, is_active)
     *
     * The `hyperparameters_schema` follows the JSON Schema standard to define:
     * - Parameter names (e.g., "period", "threshold")
     * - Data types (integer, number, string)
     * - Constraints (min, max, default)
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
     * Reverse the migrations.
     *
     * Drops the `strategies` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('strategies');
    }
};
