<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * System Logs Table
 *
 * Application-level structured logs persisted for audit and troubleshooting.
 * Includes correlation trace IDs, level, component, context, and timestamps.
 *
 * @accepted_values
 * - level: 'info', 'error', 'warning', 'debug'
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->string('trace_id', 36)->nullable();
            $table->string('level', 20); // info, error, warning, debug
            $table->string('component', 50);
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('environment', 20)->default('production');
            $table->timestamp('logged_at');
            $table->timestamps();
            
            $table->index(['trace_id', 'logged_at']);
            $table->index(['component', 'level']);
            $table->index('logged_at');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('system_logs');
    }
};
