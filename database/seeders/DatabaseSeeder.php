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

            // Users (after sites so we can assign site DPFs)
            UserSeeder::class,

            // Vehicle data
            VehicleTypeSeeder::class,
            VehicleSeeder::class,
            TaxPeriodSeeder::class,

            // Inspection system
            ChecklistSeeder::class,
            InspectionTemplateSeeder::class,
            ComponentSeeder::class,
        ]);
    }
}
