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
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Note: vehicles table uses bigint IDs (existing table)
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->enum('service_type', ['minor', 'major']);
            $table->date('service_date');
            $table->unsignedInteger('reading_at_service');
            $table->foreignUuid('site_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('site_assignment_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->decimal('total_parts_cost', 10, 2)->default(0);

            // Approval workflow
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'archived'])->default('pending');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignUuid('previous_submission_id')->nullable();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vehicle_id', 'status']);
            $table->index(['status', 'submitted_at']);
            $table->index('site_id');
        });

        // Add self-referencing foreign key after table creation
        Schema::table('services', function (Blueprint $table) {
            $table->foreign('previous_submission_id')
                ->references('id')
                ->on('services')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
