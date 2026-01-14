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
        Schema::create('machine_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 50)->unique();
            $table->enum('tracking_unit', ['hours', 'kilometers']);
            $table->unsignedInteger('minor_service_interval');
            $table->unsignedInteger('major_service_interval');
            $table->unsignedInteger('warning_threshold')->comment('Hours or km before service due to trigger warning');
            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machine_types');
    }
};
