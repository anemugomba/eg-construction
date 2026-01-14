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
        Schema::create('oil_analyses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Note: vehicles table uses bigint IDs (existing table)
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->date('analysis_date');
            $table->unsignedInteger('reading_at_analysis');
            $table->string('lab_reference', 50)->nullable();
            $table->json('results_json')->comment('Full results blob from lab');

            // Extracted key metrics for queries
            $table->unsignedInteger('iron_ppm')->nullable();
            $table->unsignedInteger('silicon_ppm')->nullable();
            $table->decimal('viscosity_40c', 6, 2)->nullable();
            $table->decimal('viscosity_100c', 6, 2)->nullable();

            $table->text('interpretation')->nullable();
            $table->text('recommendations')->nullable();
            $table->date('next_analysis_due')->nullable();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehicle_id', 'analysis_date']);
            $table->index('next_analysis_due');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oil_analyses');
    }
};
