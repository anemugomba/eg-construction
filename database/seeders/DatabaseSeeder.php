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
            // Core user and settings
            UserSeeder::class,
            SettingsSeeder::class,

            // Fleet management reference data
            SiteSeeder::class,
            MachineTypeSeeder::class,

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
