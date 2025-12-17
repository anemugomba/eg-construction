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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->boolean('notify_email')->default(true)->after('phone');
            $table->boolean('notify_sms')->default(false)->after('notify_email');
            $table->boolean('notify_whatsapp')->default(false)->after('notify_sms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'notify_email', 'notify_sms', 'notify_whatsapp']);
        });
    }
};
