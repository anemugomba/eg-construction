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
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained('checklist_categories')->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_quarterly_only')->default(false);
            $table->boolean('photo_required_on_repair')->default(true);
            $table->boolean('photo_required_on_replace')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'display_order']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklist_items');
    }
};
