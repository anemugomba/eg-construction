<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('readings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Note: vehicles table uses bigint IDs (existing table)
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('reading_value');
            $table->enum('reading_type', ['hours', 'kilometers']);
            $table->enum('source', ['manual', 'telematics', 'import', 'adjustment'])->default('manual');
            $table->boolean('is_anomaly_override')->default(false);
            $table->text('anomaly_reason')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('recorded_at');

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehicle_id', 'recorded_at']);
            $table->index(['vehicle_id', 'reading_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('readings');
    }
};
