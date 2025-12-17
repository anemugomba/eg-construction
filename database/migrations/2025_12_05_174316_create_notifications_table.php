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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tax_period_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type'); // tax_expiry_reminder, tax_expired, tax_penalty
            $table->string('channel')->default('email');
            $table->string('subject');
            $table->text('body');

            $table->enum('status', ['pending', 'sent', 'delivered', 'failed'])->default('pending');
            $table->string('resend_id')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('days_before_expiry')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['vehicle_id', 'type']);
            $table->index('resend_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
