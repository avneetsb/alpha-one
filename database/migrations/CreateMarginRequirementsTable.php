<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Margin Requirements Table
 *
 * Defines margin parameters per instrument/broker (SPAN, exposure, premium).
 * Versioned by effective date for historical tracking.
 *
 * @accepted_values
 * - margin_type: 'span', 'exposure', 'option_premium', 'delivery'
 */
return new class extends Migration
{
    public function up(): void
    {
        Capsule::schema()->create('margin_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('broker_id', 50);
            $table->foreignId('instrument_id')->constrained()->onDelete('cascade');
            $table->enum('margin_type', ['span', 'exposure', 'option_premium', 'delivery']);
            $table->decimal('margin_percentage', 5, 2)->nullable();
            $table->decimal('span_margin_amount', 15, 2)->nullable();
            $table->json('margin_parameters')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
            
            $table->index(['broker_id', 'instrument_id', 'margin_type']);
            $table->index('effective_from');
        });
    }

    public function down(): void
    {
        Capsule::schema()->dropIfExists('margin_requirements');
    }
};
