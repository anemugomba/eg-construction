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
        Schema::create('interval_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Note: vehicles table uses bigint IDs (existing table)
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->enum('override_type', ['minor_interval', 'major_interval', 'warning_threshold']);
            $table->unsignedInteger('previous_value')->nullable();
            $table->unsignedInteger('new_value');
            $table->text('reason');
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();

            // Audit fields (created_by would be same as changed_by in this case)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehicle_id', 'created_at']);
            $table->index('override_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interval_overrides');
    }
};
