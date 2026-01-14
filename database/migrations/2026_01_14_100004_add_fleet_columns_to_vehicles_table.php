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
        Schema::table('vehicles', function (Blueprint $table) {
            // Fleet tracking columns
            $table->unsignedInteger('current_hours')->nullable()->after('status');
            $table->unsignedInteger('current_km')->nullable()->after('current_hours');
            $table->timestamp('last_reading_at')->nullable()->after('current_km');
            $table->boolean('is_yellow_machine')->default(false)->after('last_reading_at');

            // Foreign keys for fleet management (UUIDs for new tables)
            $table->foreignUuid('machine_type_id')->nullable()->after('is_yellow_machine')
                ->constrained()->nullOnDelete();
            $table->foreignUuid('primary_site_id')->nullable()->after('machine_type_id')
                ->constrained('sites')->nullOnDelete();

            // Per-machine overrides for service intervals
            $table->unsignedInteger('warning_threshold_hours')->nullable()->after('primary_site_id');
            $table->unsignedInteger('warning_threshold_km')->nullable()->after('warning_threshold_hours');
            $table->unsignedInteger('minor_interval_override')->nullable()->after('warning_threshold_km');
            $table->unsignedInteger('major_interval_override')->nullable()->after('minor_interval_override');

            // Data quality tracking
            $table->unsignedInteger('reading_stale_days')->default(7)->after('major_interval_override');
            $table->boolean('has_reading_anomaly')->default(false)->after('reading_stale_days');

            // Service tracking
            $table->unsignedInteger('last_minor_service_reading')->nullable()->after('has_reading_anomaly');
            $table->unsignedInteger('last_major_service_reading')->nullable()->after('last_minor_service_reading');
            $table->date('last_minor_service_date')->nullable()->after('last_major_service_reading');
            $table->date('last_major_service_date')->nullable()->after('last_minor_service_date');

            // Indexes for performance
            $table->index('is_yellow_machine');
            $table->index('machine_type_id');
            $table->index('primary_site_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['is_yellow_machine']);
            $table->dropIndex(['machine_type_id']);
            $table->dropIndex(['primary_site_id']);

            // Drop foreign keys
            $table->dropForeign(['machine_type_id']);
            $table->dropForeign(['primary_site_id']);

            // Drop columns
            $table->dropColumn([
                'current_hours',
                'current_km',
                'last_reading_at',
                'is_yellow_machine',
                'machine_type_id',
                'primary_site_id',
                'warning_threshold_hours',
                'warning_threshold_km',
                'minor_interval_override',
                'major_interval_override',
                'reading_stale_days',
                'has_reading_anomaly',
                'last_minor_service_reading',
                'last_major_service_reading',
                'last_minor_service_date',
                'last_major_service_date',
            ]);
        });
    }
};
