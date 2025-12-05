<?php

namespace Database\Seeders;

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

        foreach ($types as $type) {
            VehicleType::firstOrCreate(['name' => $type]);
        }
    }
}
