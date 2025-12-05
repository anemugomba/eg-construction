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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('reference_name', 100)->unique();
            $table->foreignId('vehicle_type_id')->constrained('vehicle_types');
            $table->string('registration_number', 20)->nullable();
            $table->string('chassis_number', 50)->nullable();
            $table->string('engine_number', 50)->nullable();
            $table->string('make', 50)->nullable();
            $table->string('model', 50)->nullable();
            $table->year('year_of_manufacture')->nullable();
            $table->enum('status', ['active', 'disposed', 'sold'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('vehicle_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
