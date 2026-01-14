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
        Schema::create('machine_type_checklist_items', function (Blueprint $table) {
            $table->foreignUuid('machine_type_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('checklist_item_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['machine_type_id', 'checklist_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_type_checklist_items');
    }
};
