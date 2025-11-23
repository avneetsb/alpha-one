<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * System Logs Migration
 *
 * Creates the `system_logs` table for centralized application logging.
 * This table captures structured logs from all components (Risk, Order Manager, Strategy Engine).
 *
 * Key Features:
 * - Structured Data: Uses JSON `context` for machine-readable log details.
 * - Distributed Tracing: Supports `trace_id` to correlate logs across services.
 * - Environment Awareness: Tags logs with environment (production/staging).
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
     * Creates the `system_logs` table with columns for:
     * - Correlation (trace_id)
     * - Classification (level, component, environment)
     * - Content (message, context)
     * - Timing (logged_at)
     */
    public function up(): void
    {
        Capsule::schema()->create('system_logs', function (Blueprint $table) {
            $table->id();

            // Distributed tracing ID
            $table->string('trace_id', 36)->nullable();

            // Log severity
            $table->string('level', 20); // info, error, warning, debug

            // Source component (e.g., "RiskEngine", "OrderManager")
            $table->string('component', 50);

            // Human-readable message
            $table->text('message');

            // Structured data (e.g., {"order_id": 123, "error_code": "E505"})
            $table->json('context')->nullable();

            // Deployment environment
            $table->string('environment', 20)->default('production');

            // Precise timestamp
            $table->timestamp('logged_at');
            $table->timestamps();

            // Indexes for troubleshooting
            $table->index(['trace_id', 'logged_at']);
            $table->index(['component', 'level']);
            $table->index('logged_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Drops the `system_logs` table.
     */
    public function down(): void
    {
        Capsule::schema()->dropIfExists('system_logs');
    }
};
