<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Risk Limits Table
 *
 * Stores portfolio/strategy/instrument risk constraints and current utilization.
 * Used to enforce risk checks before order placement.
 *
 * @accepted_values
 * - limit_type: 'position_size', 'notional', 'drawdown', 'var', 'concentration'
 * - is_active: true/false
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('risk_limits', function (Blueprint $table) {
            $table->id();
            $table->string('portfolio_id', 100);
            $table->string('strategy_id', 100)->nullable();
            $table->foreignId('instrument_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('limit_type', ['position_size', 'notional', 'drawdown', 'var', 'concentration']);
            $table->decimal('limit_value', 15, 2);
            $table->decimal('current_value', 15, 2)->default(0);
            $table->decimal('utilization_percentage', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['portfolio_id', 'limit_type']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('risk_limits');
    }
};
