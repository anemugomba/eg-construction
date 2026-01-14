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
        Schema::create('site_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Note: vehicles table uses bigint IDs (existing table)
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('site_id')->constrained()->cascadeOnDelete();
            $table->date('assigned_at');
            $table->date('ended_at')->nullable();
            // Note: users table uses bigint IDs (existing table)
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->text('notes')->nullable();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for querying
            $table->index(['vehicle_id', 'assigned_at']);
            $table->index(['site_id', 'assigned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_assignments');
    }
};
