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
        Schema::create('job_card_parts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('job_card_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('part_catalog_id')->nullable()->constrained('parts_catalog')->nullOnDelete();
            $table->string('part_description', 255);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_cost', 10, 2)->nullable();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('job_card_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_card_parts');
    }
};
