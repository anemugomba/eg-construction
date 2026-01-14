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
        Schema::create('watch_list_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Note: vehicles table uses bigint IDs (existing table)
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('component_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('inspection_result_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('rating_at_creation', ['service', 'repair']);
            $table->date('review_date')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'resolved', 'machine_disposed'])->default('active');
            $table->foreignUuid('resolved_by_job_card_id')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehicle_id', 'status']);
            $table->index('review_date');
            $table->index('status');
        });

        // Add foreign key for resolved_by_job_card_id after job_cards table exists
        Schema::table('watch_list_items', function (Blueprint $table) {
            $table->foreign('resolved_by_job_card_id')
                ->references('id')
                ->on('job_cards')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watch_list_items');
    }
};
