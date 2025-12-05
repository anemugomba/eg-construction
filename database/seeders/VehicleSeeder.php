<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $types = VehicleType::pluck('id', 'name')->toArray();

        $vehicles = [
            // 11 Tippers
            ['reference_name' => 'Tipper 1', 'type' => 'Tipper', 'make' => 'Howo', 'model' => 'A7', 'year' => 2019, 'reg' => 'AEG 1234'],
            ['reference_name' => 'Tipper 2', 'type' => 'Tipper', 'make' => 'Howo', 'model' => 'A7', 'year' => 2019, 'reg' => 'AEG 1235'],
            ['reference_name' => 'Tipper 3', 'type' => 'Tipper', 'make' => 'Howo', 'model' => 'A7', 'year' => 2020, 'reg' => 'AEG 2201'],
            ['reference_name' => 'Tipper 4', 'type' => 'Tipper', 'make' => 'Howo', 'model' => 'A7', 'year' => 2020, 'reg' => 'AEG 2202'],
            ['reference_name' => 'Tipper 5', 'type' => 'Tipper', 'make' => 'FAW', 'model' => 'J6P', 'year' => 2021, 'reg' => 'AEG 3301'],
            ['reference_name' => 'Tipper 6', 'type' => 'Tipper', 'make' => 'FAW', 'model' => 'J6P', 'year' => 2021, 'reg' => 'AEG 3302'],
            ['reference_name' => 'Tipper 7', 'type' => 'Tipper', 'make' => 'Shacman', 'model' => 'X3000', 'year' => 2022, 'reg' => 'AEG 4401'],
            ['reference_name' => 'Tipper 8', 'type' => 'Tipper', 'make' => 'Shacman', 'model' => 'X3000', 'year' => 2022, 'reg' => 'AEG 4402'],
            ['reference_name' => 'Tipper 9', 'type' => 'Tipper', 'make' => 'Shacman', 'model' => 'F3000', 'year' => 2023, 'reg' => 'AEG 5501'],
            ['reference_name' => 'Tipper 10', 'type' => 'Tipper', 'make' => 'Howo', 'model' => 'TX7', 'year' => 2023, 'reg' => 'AEG 5502'],
            ['reference_name' => 'Tipper 11', 'type' => 'Tipper', 'make' => 'Howo', 'model' => 'TX7', 'year' => 2024, 'reg' => 'AEG 6601'],

            // 2 Bowsers (Water)
            ['reference_name' => 'Bowser 1', 'type' => 'Bowser', 'make' => 'Howo', 'model' => 'A7 Water', 'year' => 2020, 'reg' => 'ABW 1001'],
            ['reference_name' => 'Bowser 2', 'type' => 'Bowser', 'make' => 'FAW', 'model' => 'J6 Water', 'year' => 2021, 'reg' => 'ABW 1002'],

            // 2 Graders
            ['reference_name' => 'Grader 1', 'type' => 'Grader', 'make' => 'Caterpillar', 'model' => '140H', 'year' => 2018, 'reg' => null, 'chassis' => 'CAT140H2018001'],
            ['reference_name' => 'Grader 2', 'type' => 'Grader', 'make' => 'Caterpillar', 'model' => '140K', 'year' => 2021, 'reg' => null, 'chassis' => 'CAT140K2021002'],

            // 2 Bulldozers
            ['reference_name' => 'Dozer 1', 'type' => 'Bulldozer', 'make' => 'Caterpillar', 'model' => 'D6R', 'year' => 2017, 'reg' => null, 'chassis' => 'CATD6R2017001'],
            ['reference_name' => 'Dozer 2', 'type' => 'Bulldozer', 'make' => 'Caterpillar', 'model' => 'D7R', 'year' => 2019, 'reg' => null, 'chassis' => 'CATD7R2019002'],

            // 4 Front End Loaders
            ['reference_name' => 'Loader 1', 'type' => 'Front End Loader', 'make' => 'Caterpillar', 'model' => '950H', 'year' => 2018, 'reg' => null, 'chassis' => 'CAT950H2018001'],
            ['reference_name' => 'Loader 2', 'type' => 'Front End Loader', 'make' => 'Caterpillar', 'model' => '966H', 'year' => 2019, 'reg' => null, 'chassis' => 'CAT966H2019002'],
            ['reference_name' => 'Loader 3', 'type' => 'Front End Loader', 'make' => 'SDLG', 'model' => 'L956F', 'year' => 2021, 'reg' => null, 'chassis' => 'SDLG956F2021003'],
            ['reference_name' => 'Loader 4', 'type' => 'Front End Loader', 'make' => 'SDLG', 'model' => 'L958F', 'year' => 2022, 'reg' => null, 'chassis' => 'SDLG958F2022004'],

            // 4 Rollers
            ['reference_name' => 'Roller 1', 'type' => 'Roller', 'make' => 'Bomag', 'model' => 'BW211D', 'year' => 2019, 'reg' => null, 'chassis' => 'BOMAG211D2019001'],
            ['reference_name' => 'Roller 2', 'type' => 'Roller', 'make' => 'Bomag', 'model' => 'BW213D', 'year' => 2020, 'reg' => null, 'chassis' => 'BOMAG213D2020002'],
            ['reference_name' => 'Roller 3', 'type' => 'Roller', 'make' => 'Dynapac', 'model' => 'CA250', 'year' => 2021, 'reg' => null, 'chassis' => 'DYNCA2502021003'],
            ['reference_name' => 'Roller 4', 'type' => 'Roller', 'make' => 'Dynapac', 'model' => 'CA300', 'year' => 2022, 'reg' => null, 'chassis' => 'DYNCA3002022004'],

            // 1 Fuel Tanker
            ['reference_name' => 'Fuel Tanker 1', 'type' => 'Fuel Tanker', 'make' => 'Howo', 'model' => 'A7 Fuel', 'year' => 2020, 'reg' => 'AFT 1001'],

            // 4 Excavators
            ['reference_name' => 'Excavator 1', 'type' => 'Excavator', 'make' => 'Caterpillar', 'model' => '320D', 'year' => 2018, 'reg' => null, 'chassis' => 'CAT320D2018001'],
            ['reference_name' => 'Excavator 2', 'type' => 'Excavator', 'make' => 'Caterpillar', 'model' => '330D', 'year' => 2019, 'reg' => null, 'chassis' => 'CAT330D2019002'],
            ['reference_name' => 'Excavator 3', 'type' => 'Excavator', 'make' => 'Sany', 'model' => 'SY215C', 'year' => 2021, 'reg' => null, 'chassis' => 'SANY215C2021003'],
            ['reference_name' => 'Excavator 4', 'type' => 'Excavator', 'make' => 'Sany', 'model' => 'SY335H', 'year' => 2023, 'reg' => null, 'chassis' => 'SANY335H2023004'],

            // 1 TLB
            ['reference_name' => 'TLB 1', 'type' => 'TLB', 'make' => 'JCB', 'model' => '3CX', 'year' => 2020, 'reg' => null, 'chassis' => 'JCB3CX2020001'],

            // 1 Volvo Horse
            ['reference_name' => 'Volvo Horse 1', 'type' => 'Horse', 'make' => 'Volvo', 'model' => 'FH16', 'year' => 2019, 'reg' => 'AVH 1001'],

            // 1 Lowbed Trailer
            ['reference_name' => 'Lowbed 1', 'type' => 'Lowbed Trailer', 'make' => 'CIMC', 'model' => '60T Lowbed', 'year' => 2019, 'reg' => 'ALB 1001'],

            // 1 Flatbed Trailer
            ['reference_name' => 'Flatbed 1', 'type' => 'Flatbed Trailer', 'make' => 'CIMC', 'model' => '40FT Flatbed', 'year' => 2020, 'reg' => 'AFB 1001'],

            // 10 Small Vehicles (Trucks and Cars for management)
            ['reference_name' => 'Truck 1', 'type' => 'Truck', 'make' => 'Isuzu', 'model' => 'NPR 400', 'year' => 2020, 'reg' => 'ATK 1001'],
            ['reference_name' => 'Truck 2', 'type' => 'Truck', 'make' => 'Isuzu', 'model' => 'NPR 300', 'year' => 2021, 'reg' => 'ATK 1002'],
            ['reference_name' => 'Truck 3', 'type' => 'Truck', 'make' => 'Hino', 'model' => '300', 'year' => 2021, 'reg' => 'ATK 1003'],
            ['reference_name' => 'Hilux 1', 'type' => 'Light Vehicle', 'make' => 'Toyota', 'model' => 'Hilux 2.8 GD6', 'year' => 2022, 'reg' => 'AHX 2201'],
            ['reference_name' => 'Hilux 2', 'type' => 'Light Vehicle', 'make' => 'Toyota', 'model' => 'Hilux 2.4 GD6', 'year' => 2023, 'reg' => 'AHX 2302'],
            ['reference_name' => 'Fortuner 1', 'type' => 'Light Vehicle', 'make' => 'Toyota', 'model' => 'Fortuner 2.8', 'year' => 2023, 'reg' => 'AFN 2301'],
            ['reference_name' => 'Ranger 1', 'type' => 'Light Vehicle', 'make' => 'Ford', 'model' => 'Ranger Wildtrak', 'year' => 2022, 'reg' => 'ARG 2201'],
            ['reference_name' => 'Prado 1', 'type' => 'Light Vehicle', 'make' => 'Toyota', 'model' => 'Land Cruiser Prado', 'year' => 2021, 'reg' => 'APR 2101'],
            ['reference_name' => 'Land Cruiser 1', 'type' => 'Light Vehicle', 'make' => 'Toyota', 'model' => 'Land Cruiser 300', 'year' => 2024, 'reg' => 'ALC 2401'],
            ['reference_name' => 'Corolla 1', 'type' => 'Light Vehicle', 'make' => 'Toyota', 'model' => 'Corolla Cross', 'year' => 2023, 'reg' => 'ACC 2301'],
        ];

        foreach ($vehicles as $v) {
            Vehicle::firstOrCreate(
                ['reference_name' => $v['reference_name']],
                [
                    'vehicle_type_id' => $types[$v['type']],
                    'registration_number' => $v['reg'] ?? null,
                    'chassis_number' => $v['chassis'] ?? null,
                    'make' => $v['make'],
                    'model' => $v['model'],
                    'year_of_manufacture' => $v['year'],
                    'status' => 'active',
                ]
            );
        }
    }
}
