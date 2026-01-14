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
        Schema::create('parts_catalog', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sku', 50)->nullable()->unique();
            $table->string('name', 255);
            $table->string('category', 100)->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->string('supplier', 100)->nullable();
            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('category');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parts_catalog');
    }
};
