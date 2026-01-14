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
        Schema::create('job_card_components', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('job_card_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('component_id')->nullable()->constrained()->nullOnDelete();
            $table->string('component_description', 255);
            $table->enum('action_taken', ['repaired', 'replaced', 'adjusted', 'other']);
            $table->unsignedInteger('reading_at_action')->nullable();
            $table->text('notes')->nullable();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('job_card_id');
            $table->index('component_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_card_components');
    }
};
