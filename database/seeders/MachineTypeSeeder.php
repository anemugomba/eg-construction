<?php

namespace Database\Seeders;

use App\Models\MachineType;
use Illuminate\Database\Seeder;

class MachineTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $machineTypes = [
            // Yellow machines (tracked by hours)
            [
                'name' => 'Excavator',
                'tracking_unit' => 'hours',
                'minor_service_interval' => 250,
                'major_service_interval' => 500,
                'warning_threshold' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Front-End Loader',
                'tracking_unit' => 'hours',
                'minor_service_interval' => 250,
                'major_service_interval' => 500,
                'warning_threshold' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Bulldozer',
                'tracking_unit' => 'hours',
                'minor_service_interval' => 250,
                'major_service_interval' => 500,
                'warning_threshold' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Grader',
                'tracking_unit' => 'hours',
                'minor_service_interval' => 250,
                'major_service_interval' => 500,
                'warning_threshold' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Roller/Compactor',
                'tracking_unit' => 'hours',
                'minor_service_interval' => 250,
                'major_service_interval' => 500,
                'warning_threshold' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Backhoe Loader',
                'tracking_unit' => 'hours',
                'minor_service_interval' => 250,
                'major_service_interval' => 500,
                'warning_threshold' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Crane',
                'tracking_unit' => 'hours',
                'minor_service_interval' => 250,
                'major_service_interval' => 500,
                'warning_threshold' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Generator',
                'tracking_unit' => 'hours',
                'minor_service_interval' => 250,
                'major_service_interval' => 500,
                'warning_threshold' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Compressor',
                'tracking_unit' => 'hours',
                'minor_service_interval' => 250,
                'major_service_interval' => 500,
                'warning_threshold' => 50,
                'is_active' => true,
            ],
            [
                'name' => 'Water Bowser',
                'tracking_unit' => 'hours',
                'minor_service_interval' => 250,
                'major_service_interval' => 500,
                'warning_threshold' => 50,
                'is_active' => true,
            ],
            // Road vehicles (tracked by kilometers)
            [
                'name' => 'Tipper Truck',
                'tracking_unit' => 'kilometers',
                'minor_service_interval' => 10000,
                'major_service_interval' => 20000,
                'warning_threshold' => 1000,
                'is_active' => true,
            ],
            [
                'name' => 'Flatbed Truck',
                'tracking_unit' => 'kilometers',
                'minor_service_interval' => 10000,
                'major_service_interval' => 20000,
                'warning_threshold' => 1000,
                'is_active' => true,
            ],
            [
                'name' => 'Lowbed Trailer',
                'tracking_unit' => 'kilometers',
                'minor_service_interval' => 10000,
                'major_service_interval' => 20000,
                'warning_threshold' => 1000,
                'is_active' => true,
            ],
            [
                'name' => 'Light Vehicle',
                'tracking_unit' => 'kilometers',
                'minor_service_interval' => 5000,
                'major_service_interval' => 10000,
                'warning_threshold' => 500,
                'is_active' => true,
            ],
            [
                'name' => 'Pick-up',
                'tracking_unit' => 'kilometers',
                'minor_service_interval' => 5000,
                'major_service_interval' => 10000,
                'warning_threshold' => 500,
                'is_active' => true,
            ],
        ];

        foreach ($machineTypes as $type) {
            MachineType::firstOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
