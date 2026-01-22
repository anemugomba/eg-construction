<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Settings first (no dependencies)
            SettingsSeeder::class,

            // Fleet management reference data (before users for site assignments)
            SiteSeeder::class,
            MachineTypeSeeder::class,

            // Inspection system (before vehicle types so we can assign default templates)
            ChecklistSeeder::class,
            InspectionTemplateSeeder::class,

            // Users (after sites so we can assign site DPFs)
            UserSeeder::class,

            // Vehicle data (after inspection templates for default template assignment)
            VehicleTypeSeeder::class,
            VehicleSeeder::class,
            TaxPeriodSeeder::class,

            // Components
            ComponentSeeder::class,
        ]);
    }
}
