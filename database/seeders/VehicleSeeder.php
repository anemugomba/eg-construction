<?php

namespace Database\Seeders;

use App\Models\MachineType;
use App\Models\Site;
use App\Models\Vehicle;
use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $types = VehicleType::pluck('id', 'name')->toArray();
        $machineTypes = MachineType::pluck('id', 'name')->toArray();
        $sites = Site::pluck('id', 'name')->toArray();

        // Yellow machine types (track hours)
        $yellowMachineTypes = [
            'Grader', 'Bulldozer', 'Front End Loader', 'Roller',
            'Excavator', 'TLB', 'Bowser'
        ];

        // Road vehicles that track kilometers
        $roadVehicleTypes = [
            'Tipper', 'Truck', 'Light Vehicle', 'Horse',
            'Fuel Tanker', 'Lowbed Trailer', 'Flatbed Trailer'
        ];

        $vehicles = [
            // 11 Tippers (road vehicles - track km)
            ['reference_name' => 'Tipper 1', 'type' => 'Tipper', 'make' => 'Howo', 'model' => 'A7', 'year' => 2019, 'reg' => 'AEG 1234', 'km' => 145000],
            ['reference_name' => 'Tipper 2', 'type' => 'Tipper', 'make' => 'Howo', 'model' => 'A7', 'year' => 2019, 'reg' => 'AEG 1235', 'km' => 138000],
            ['reference_name' => 'Tipper 3', 'type' => 'Tipper', 'make' => 'Howo', 'model' => 'A7', 'year' => 2020, 'reg' => 'AEG 2201', 'km' => 112000],
            ['reference_name' => 'Tipper 4', 'type' => 'Tipper', 'make' => 'Howo', 'model' => 'A7', 'year' => 2020, 'reg' => 'AEG 2202', 'km' => 108000],
            ['reference_name' => 'Tipper 5', 'type' => 'Tipper', 'make' => 'FAW', 'model' => 'J6P', 'year' => 2021, 'reg' => 'AEG 3301', 'km' => 89000],
            ['reference_name' => 'Tipper 6', 'type' => 'Tipper', 'make' => 'FAW', 'model' => 'J6P', 'year' => 2021, 'reg' => 'AEG 3302', 'km' => 92000],
            ['reference_name' => 'Tipper 7', 'type' => 'Tipper', 'make' => 'Shacman', 'model' => 'X3000', 'year' => 2022, 'reg' => 'AEG 4401', 'km' => 67000],
            ['reference_name' => 'Tipper 8', 'type' => 'Tipper', 'make' => 'Shacman', 'model' => 'X3000', 'year' => 2022, 'reg' => 'AEG 4402', 'km' => 71000],
            ['reference_name' => 'Tipper 9', 'type' => 'Tipper', 'make' => 'Shacman', 'model' => 'F3000', 'year' => 2023, 'reg' => 'AEG 5501', 'km' => 45000],
            ['reference_name' => 'Tipper 10', 'type' => 'Tipper', 'make' => 'Howo', 'model' => 'TX7', 'year' => 2023, 'reg' => 'AEG 5502', 'km' => 38000],
            ['reference_name' => 'Tipper 11', 'type' => 'Tipper', 'make' => 'Howo', 'model' => 'TX7', 'year' => 2024, 'reg' => 'AEG 6601', 'km' => 12000],

            // 2 Bowsers (Water) - yellow machines, track hours
            ['reference_name' => 'Bowser 1', 'type' => 'Bowser', 'make' => 'Howo', 'model' => 'A7 Water', 'year' => 2020, 'reg' => 'ABW 1001', 'hours' => 4500, 'machine' => 'Bowser'],
            ['reference_name' => 'Bowser 2', 'type' => 'Bowser', 'make' => 'FAW', 'model' => 'J6 Water', 'year' => 2021, 'reg' => 'ABW 1002', 'hours' => 3200, 'machine' => 'Bowser'],

            // 2 Graders - yellow machines, track hours
            ['reference_name' => 'Grader 1', 'type' => 'Grader', 'make' => 'Caterpillar', 'model' => '140H', 'year' => 2018, 'reg' => null, 'chassis' => 'CAT140H2018001', 'hours' => 8500, 'machine' => 'Grader'],
            ['reference_name' => 'Grader 2', 'type' => 'Grader', 'make' => 'Caterpillar', 'model' => '140K', 'year' => 2021, 'reg' => null, 'chassis' => 'CAT140K2021002', 'hours' => 4200, 'machine' => 'Grader'],

            // 2 Bulldozers - yellow machines, track hours
            ['reference_name' => 'Dozer 1', 'type' => 'Bulldozer', 'make' => 'Caterpillar', 'model' => 'D6R', 'year' => 2017, 'reg' => null, 'chassis' => 'CATD6R2017001', 'hours' => 12500, 'machine' => 'Bulldozer'],
            ['reference_name' => 'Dozer 2', 'type' => 'Bulldozer', 'make' => 'Caterpillar', 'model' => 'D7R', 'year' => 2019, 'reg' => null, 'chassis' => 'CATD7R2019002', 'hours' => 7800, 'machine' => 'Bulldozer'],

            // 4 Front End Loaders - yellow machines, track hours
            ['reference_name' => 'Loader 1', 'type' => 'Front End Loader', 'make' => 'Caterpillar', 'model' => '950H', 'year' => 2018, 'reg' => null, 'chassis' => 'CAT950H2018001', 'hours' => 9200, 'machine' => 'Front End Loader'],
            ['reference_name' => 'Loader 2', 'type' => 'Front End Loader', 'make' => 'Caterpillar', 'model' => '966H', 'year' => 2019, 'reg' => null, 'chassis' => 'CAT966H2019002', 'hours' => 7600, 'machine' => 'Front End Loader'],
            ['reference_name' => 'Loader 3', 'type' => 'Front End Loader', 'make' => 'SDLG', 'model' => 'L956F', 'year' => 2021, 'reg' => null, 'chassis' => 'SDLG956F2021003', 'hours' => 4100, 'machine' => 'Front End Loader'],
            ['reference_name' => 'Loader 4', 'type' => 'Front End Loader', 'make' => 'SDLG', 'model' => 'L958F', 'year' => 2022, 'reg' => null, 'chassis' => 'SDLG958F2022004', 'hours' => 2800, 'machine' => 'Front End Loader'],

            // 4 Rollers - yellow machines, track hours
            ['reference_name' => 'Roller 1', 'type' => 'Roller', 'make' => 'Bomag', 'model' => 'BW211D', 'year' => 2019, 'reg' => null, 'chassis' => 'BOMAG211D2019001', 'hours' => 5600, 'machine' => 'Roller'],
            ['reference_name' => 'Roller 2', 'type' => 'Roller', 'make' => 'Bomag', 'model' => 'BW213D', 'year' => 2020, 'reg' => null, 'chassis' => 'BOMAG213D2020002', 'hours' => 4300, 'machine' => 'Roller'],
            ['reference_name' => 'Roller 3', 'type' => 'Roller', 'make' => 'Dynapac', 'model' => 'CA250', 'year' => 2021, 'reg' => null, 'chassis' => 'DYNCA2502021003', 'hours' => 3100, 'machine' => 'Roller'],
            ['reference_name' => 'Roller 4', 'type' => 'Roller', 'make' => 'Dynapac', 'model' => 'CA300', 'year' => 2022, 'reg' => null, 'chassis' => 'DYNCA3002022004', 'hours' => 1900, 'machine' => 'Roller'],

            // 1 Fuel Tanker (road vehicle - track km)
            ['reference_name' => 'Fuel Tanker 1', 'type' => 'Fuel Tanker', 'make' => 'Howo', 'model' => 'A7 Fuel', 'year' => 2020, 'reg' => 'AFT 1001', 'km' => 95000],

            // 4 Excavators - yellow machines, track hours
            ['reference_name' => 'Excavator 1', 'type' => 'Excavator', 'make' => 'Caterpillar', 'model' => '320D', 'year' => 2018, 'reg' => null, 'chassis' => 'CAT320D2018001', 'hours' => 11200, 'machine' => 'Excavator'],
            ['reference_name' => 'Excavator 2', 'type' => 'Excavator', 'make' => 'Caterpillar', 'model' => '330D', 'year' => 2019, 'reg' => null, 'chassis' => 'CAT330D2019002', 'hours' => 8900, 'machine' => 'Excavator'],
            ['reference_name' => 'Excavator 3', 'type' => 'Excavator', 'make' => 'Sany', 'model' => 'SY215C', 'year' => 2021, 'reg' => null, 'chassis' => 'SANY215C2021003', 'hours' => 4500, 'machine' => 'Excavator'],
            ['reference_name' => 'Excavator 4', 'type' => 'Excavator', 'make' => 'Sany', 'model' => 'SY335H', 'year' => 2023, 'reg' => null, 'chassis' => 'SANY335H2023004', 'hours' => 1800, 'machine' => 'Excavator'],

            // 1 TLB - yellow machine, track hours
            ['reference_name' => 'TLB 1', 'type' => 'TLB', 'make' => 'JCB', 'model' => '3CX', 'year' => 2020, 'reg' => null, 'chassis' => 'JCB3CX2020001', 'hours' => 3800, 'machine' => 'TLB'],

            // 1 Volvo Horse (road vehicle - track km)
            ['reference_name' => 'Volvo Horse 1', 'type' => 'Horse', 'make' => 'Volvo', 'model' => 'FH16', 'year' => 2019, 'reg' => 'AVH 1001', 'km' => 320000],

            // 1 Lowbed Trailer (road vehicle - track km, or no tracking)
            ['reference_name' => 'Lowbed 1', 'type' => 'Lowbed Trailer', 'make' => 'CIMC', 'model' => '60T Lowbed', 'year' => 2019, 'reg' => 'ALB 1001', 'km' => 0],

            // 1 Flatbed Trailer (road vehicle - track km, or no tracking)
            ['reference_name' => 'Flatbed 1', 'type' => 'Flatbed Trailer', 'make' => 'CIMC', 'model' => '40FT Flatbed', 'year' => 2020, 'reg' => 'AFB 1001', 'km' => 0],

            // 10 Small Vehicles (Trucks and Cars - track km)
            ['reference_name' => 'Truck 1', 'type' => 'Truck', 'make' => 'Isuzu', 'model' => 'NPR 400', 'year' => 2020, 'reg' => 'ATK 1001', 'km' => 78000],
            ['reference_name' => 'Truck 2', 'type' => 'Truck', 'make' => 'Isuzu', 'model' => 'NPR 300', 'year' => 2021, 'reg' => 'ATK 1002', 'km' => 52000],
            ['reference_name' => 'Truck 3', 'type' => 'Truck', 'make' => 'Hino', 'model' => '300', 'year' => 2021, 'reg' => 'ATK 1003', 'km' => 48000],
            ['reference_name' => 'Hilux 1', 'type' => 'Light Vehicle', 'make' => 'Toyota', 'model' => 'Hilux 2.8 GD6', 'year' => 2022, 'reg' => 'AHX 2201', 'km' => 45000],
            ['reference_name' => 'Hilux 2', 'type' => 'Light Vehicle', 'make' => 'Toyota', 'model' => 'Hilux 2.4 GD6', 'year' => 2023, 'reg' => 'AHX 2302', 'km' => 28000],
            ['reference_name' => 'Fortuner 1', 'type' => 'Light Vehicle', 'make' => 'Toyota', 'model' => 'Fortuner 2.8', 'year' => 2023, 'reg' => 'AFN 2301', 'km' => 32000],
            ['reference_name' => 'Ranger 1', 'type' => 'Light Vehicle', 'make' => 'Ford', 'model' => 'Ranger Wildtrak', 'year' => 2022, 'reg' => 'ARG 2201', 'km' => 41000],
            ['reference_name' => 'Prado 1', 'type' => 'Light Vehicle', 'make' => 'Toyota', 'model' => 'Land Cruiser Prado', 'year' => 2021, 'reg' => 'APR 2101', 'km' => 58000],
            ['reference_name' => 'Land Cruiser 1', 'type' => 'Light Vehicle', 'make' => 'Toyota', 'model' => 'Land Cruiser 300', 'year' => 2024, 'reg' => 'ALC 2401', 'km' => 8500],
            ['reference_name' => 'Corolla 1', 'type' => 'Light Vehicle', 'make' => 'Toyota', 'model' => 'Corolla Cross', 'year' => 2023, 'reg' => 'ACC 2301', 'km' => 22000],
        ];

        // Get site IDs for assignment
        $siteIds = array_values($sites);
        $siteIndex = 0;

        foreach ($vehicles as $v) {
            $isYellowMachine = in_array($v['type'], $yellowMachineTypes);
            $machineTypeId = isset($v['machine']) && isset($machineTypes[$v['machine']])
                ? $machineTypes[$v['machine']]
                : null;

            // Assign to sites round-robin style
            $primarySiteId = !empty($siteIds) ? $siteIds[$siteIndex % count($siteIds)] : null;
            $siteIndex++;

            Vehicle::updateOrCreate(
                ['reference_name' => $v['reference_name']],
                [
                    'vehicle_type_id' => $types[$v['type']] ?? null,
                    'registration_number' => $v['reg'] ?? null,
                    'chassis_number' => $v['chassis'] ?? null,
                    'make' => $v['make'],
                    'model' => $v['model'],
                    'year_of_manufacture' => $v['year'],
                    'status' => 'active',
                    // Fleet management fields
                    'is_yellow_machine' => $isYellowMachine,
                    'machine_type_id' => $machineTypeId,
                    'primary_site_id' => $primarySiteId,
                    'current_hours' => $v['hours'] ?? null,
                    'current_km' => $v['km'] ?? null,
                    'last_reading_at' => ($v['hours'] ?? $v['km'] ?? null) ? now()->subDays(rand(1, 14)) : null,
                ]
            );
        }
    }
}
