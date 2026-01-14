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
        Schema::create('inspection_template_items', function (Blueprint $table) {
            $table->foreignUuid('template_id')->constrained('inspection_templates')->cascadeOnDelete();
            $table->foreignUuid('checklist_item_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['template_id', 'checklist_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_template_items');
    }
};
