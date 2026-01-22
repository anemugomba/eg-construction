<?php

namespace Database\Seeders;

use App\Models\InspectionTemplate;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Tipper',
            'Grader',
            'Bulldozer',
            'Bowser',
            'Front End Loader',
            'Roller',
            'Fuel Tanker',
            'Excavator',
            'TLB',
            'Horse',
            'Lowbed Trailer',
            'Flatbed Trailer',
            'Truck',
            'Light Vehicle',
            'Other',
        ];

        // Get the Monthly Standard template as default
        $defaultTemplate = InspectionTemplate::where('name', 'Monthly Standard')->first();

        foreach ($types as $type) {
            VehicleType::updateOrCreate(
                ['name' => $type],
                ['default_inspection_template_id' => $defaultTemplate?->id]
            );
        }
    }
}
