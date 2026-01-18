<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Rename columns for consistency:
     * - part_description → name (we're already in a parts table)
     * - component_description → name (we're already in a components table)
     * - action_taken → action (simpler)
     */
    public function up(): void
    {
        // Rename columns in job_card_parts
        Schema::table('job_card_parts', function (Blueprint $table) {
            $table->renameColumn('part_description', 'name');
        });

        // Rename columns in service_parts
        Schema::table('service_parts', function (Blueprint $table) {
            $table->renameColumn('part_description', 'name');
        });

        // Rename columns in job_card_components
        Schema::table('job_card_components', function (Blueprint $table) {
            $table->renameColumn('component_description', 'name');
            $table->renameColumn('action_taken', 'action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_card_parts', function (Blueprint $table) {
            $table->renameColumn('name', 'part_description');
        });

        Schema::table('service_parts', function (Blueprint $table) {
            $table->renameColumn('name', 'part_description');
        });

        Schema::table('job_card_components', function (Blueprint $table) {
            $table->renameColumn('name', 'component_description');
            $table->renameColumn('action', 'action_taken');
        });
    }
};
