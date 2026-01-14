<?php

namespace Database\Seeders;

use App\Models\Component;
use Illuminate\Database\Seeder;

class ComponentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $components = [
            // Engine Components
            ['name' => 'Engine Assembly', 'category' => 'Engine', 'is_system_defined' => true],
            ['name' => 'Turbocharger', 'category' => 'Engine', 'is_system_defined' => true],
            ['name' => 'Fuel Injection Pump', 'category' => 'Engine', 'is_system_defined' => true],
            ['name' => 'Fuel Injectors', 'category' => 'Engine', 'is_system_defined' => true],
            ['name' => 'Water Pump', 'category' => 'Engine', 'is_system_defined' => true],
            ['name' => 'Radiator', 'category' => 'Engine', 'is_system_defined' => true],
            ['name' => 'Alternator', 'category' => 'Engine', 'is_system_defined' => true],
            ['name' => 'Starter Motor', 'category' => 'Engine', 'is_system_defined' => true],
            ['name' => 'Oil Pump', 'category' => 'Engine', 'is_system_defined' => true],
            ['name' => 'Head Gasket', 'category' => 'Engine', 'is_system_defined' => true],
            ['name' => 'Engine Mounts', 'category' => 'Engine', 'is_system_defined' => true],

            // Transmission Components
            ['name' => 'Transmission Assembly', 'category' => 'Transmission', 'is_system_defined' => true],
            ['name' => 'Torque Converter', 'category' => 'Transmission', 'is_system_defined' => true],
            ['name' => 'Clutch Assembly', 'category' => 'Transmission', 'is_system_defined' => true],
            ['name' => 'Clutch Plate', 'category' => 'Transmission', 'is_system_defined' => true],
            ['name' => 'Pressure Plate', 'category' => 'Transmission', 'is_system_defined' => true],
            ['name' => 'Clutch Release Bearing', 'category' => 'Transmission', 'is_system_defined' => true],
            ['name' => 'Transmission Pump', 'category' => 'Transmission', 'is_system_defined' => true],
            ['name' => 'Gear Set', 'category' => 'Transmission', 'is_system_defined' => true],

            // Hydraulic Components
            ['name' => 'Main Hydraulic Pump', 'category' => 'Hydraulics', 'is_system_defined' => true],
            ['name' => 'Auxiliary Hydraulic Pump', 'category' => 'Hydraulics', 'is_system_defined' => true],
            ['name' => 'Hydraulic Cylinder - Boom', 'category' => 'Hydraulics', 'is_system_defined' => true],
            ['name' => 'Hydraulic Cylinder - Arm', 'category' => 'Hydraulics', 'is_system_defined' => true],
            ['name' => 'Hydraulic Cylinder - Bucket', 'category' => 'Hydraulics', 'is_system_defined' => true],
            ['name' => 'Control Valve', 'category' => 'Hydraulics', 'is_system_defined' => true],
            ['name' => 'Hydraulic Tank', 'category' => 'Hydraulics', 'is_system_defined' => true],
            ['name' => 'Hydraulic Filter', 'category' => 'Hydraulics', 'is_system_defined' => true],
            ['name' => 'Hydraulic Hoses', 'category' => 'Hydraulics', 'is_system_defined' => true],
            ['name' => 'Swivel Joint', 'category' => 'Hydraulics', 'is_system_defined' => true],

            // Undercarriage Components
            ['name' => 'Track Assembly - Left', 'category' => 'Undercarriage', 'is_system_defined' => true],
            ['name' => 'Track Assembly - Right', 'category' => 'Undercarriage', 'is_system_defined' => true],
            ['name' => 'Track Shoes/Pads', 'category' => 'Undercarriage', 'is_system_defined' => true],
            ['name' => 'Sprocket - Left', 'category' => 'Undercarriage', 'is_system_defined' => true],
            ['name' => 'Sprocket - Right', 'category' => 'Undercarriage', 'is_system_defined' => true],
            ['name' => 'Idler - Left', 'category' => 'Undercarriage', 'is_system_defined' => true],
            ['name' => 'Idler - Right', 'category' => 'Undercarriage', 'is_system_defined' => true],
            ['name' => 'Track Roller', 'category' => 'Undercarriage', 'is_system_defined' => true],
            ['name' => 'Carrier Roller', 'category' => 'Undercarriage', 'is_system_defined' => true],
            ['name' => 'Track Adjuster', 'category' => 'Undercarriage', 'is_system_defined' => true],
            ['name' => 'Track Links', 'category' => 'Undercarriage', 'is_system_defined' => true],
            ['name' => 'Track Pins & Bushings', 'category' => 'Undercarriage', 'is_system_defined' => true],

            // Brake Components
            ['name' => 'Brake Pads - Front', 'category' => 'Brakes', 'is_system_defined' => true],
            ['name' => 'Brake Pads - Rear', 'category' => 'Brakes', 'is_system_defined' => true],
            ['name' => 'Brake Shoes', 'category' => 'Brakes', 'is_system_defined' => true],
            ['name' => 'Brake Discs', 'category' => 'Brakes', 'is_system_defined' => true],
            ['name' => 'Brake Drums', 'category' => 'Brakes', 'is_system_defined' => true],
            ['name' => 'Brake Master Cylinder', 'category' => 'Brakes', 'is_system_defined' => true],
            ['name' => 'Wheel Cylinders', 'category' => 'Brakes', 'is_system_defined' => true],
            ['name' => 'Parking Brake Assembly', 'category' => 'Brakes', 'is_system_defined' => true],

            // Steering Components
            ['name' => 'Steering Cylinder', 'category' => 'Steering', 'is_system_defined' => true],
            ['name' => 'Steering Pump', 'category' => 'Steering', 'is_system_defined' => true],
            ['name' => 'Tie Rod Assembly', 'category' => 'Steering', 'is_system_defined' => true],
            ['name' => 'Tie Rod Ends', 'category' => 'Steering', 'is_system_defined' => true],
            ['name' => 'King Pins', 'category' => 'Steering', 'is_system_defined' => true],
            ['name' => 'Steering Linkage', 'category' => 'Steering', 'is_system_defined' => true],

            // Electrical Components
            ['name' => 'Battery', 'category' => 'Electrical', 'is_system_defined' => true],
            ['name' => 'Wiring Harness', 'category' => 'Electrical', 'is_system_defined' => true],
            ['name' => 'ECU/ECM', 'category' => 'Electrical', 'is_system_defined' => true],
            ['name' => 'Sensors', 'category' => 'Electrical', 'is_system_defined' => true],
            ['name' => 'Work Lights', 'category' => 'Electrical', 'is_system_defined' => true],
            ['name' => 'Headlights', 'category' => 'Electrical', 'is_system_defined' => true],
            ['name' => 'Tail Lights', 'category' => 'Electrical', 'is_system_defined' => true],

            // Attachments
            ['name' => 'Bucket - Standard', 'category' => 'Attachments', 'is_system_defined' => true],
            ['name' => 'Bucket - Heavy Duty', 'category' => 'Attachments', 'is_system_defined' => true],
            ['name' => 'Bucket Teeth', 'category' => 'Attachments', 'is_system_defined' => true],
            ['name' => 'Cutting Edge', 'category' => 'Attachments', 'is_system_defined' => true],
            ['name' => 'Quick Coupler', 'category' => 'Attachments', 'is_system_defined' => true],
            ['name' => 'Ripper', 'category' => 'Attachments', 'is_system_defined' => true],
            ['name' => 'Blade Assembly', 'category' => 'Attachments', 'is_system_defined' => true],

            // Tyres/Wheels
            ['name' => 'Tyre - Front Left', 'category' => 'Tyres/Wheels', 'is_system_defined' => true],
            ['name' => 'Tyre - Front Right', 'category' => 'Tyres/Wheels', 'is_system_defined' => true],
            ['name' => 'Tyre - Rear Left Inner', 'category' => 'Tyres/Wheels', 'is_system_defined' => true],
            ['name' => 'Tyre - Rear Left Outer', 'category' => 'Tyres/Wheels', 'is_system_defined' => true],
            ['name' => 'Tyre - Rear Right Inner', 'category' => 'Tyres/Wheels', 'is_system_defined' => true],
            ['name' => 'Tyre - Rear Right Outer', 'category' => 'Tyres/Wheels', 'is_system_defined' => true],
            ['name' => 'Wheel Rim', 'category' => 'Tyres/Wheels', 'is_system_defined' => true],
            ['name' => 'Wheel Bearings', 'category' => 'Tyres/Wheels', 'is_system_defined' => true],
            ['name' => 'Hub Assembly', 'category' => 'Tyres/Wheels', 'is_system_defined' => true],

            // Final Drive
            ['name' => 'Final Drive - Left', 'category' => 'Final Drive', 'is_system_defined' => true],
            ['name' => 'Final Drive - Right', 'category' => 'Final Drive', 'is_system_defined' => true],
            ['name' => 'Travel Motor - Left', 'category' => 'Final Drive', 'is_system_defined' => true],
            ['name' => 'Travel Motor - Right', 'category' => 'Final Drive', 'is_system_defined' => true],
            ['name' => 'Drive Shaft', 'category' => 'Final Drive', 'is_system_defined' => true],
            ['name' => 'Differential', 'category' => 'Final Drive', 'is_system_defined' => true],
            ['name' => 'Axle Assembly - Front', 'category' => 'Final Drive', 'is_system_defined' => true],
            ['name' => 'Axle Assembly - Rear', 'category' => 'Final Drive', 'is_system_defined' => true],

            // Cab/Body
            ['name' => 'Seat Assembly', 'category' => 'Cab/Body', 'is_system_defined' => true],
            ['name' => 'Door - Left', 'category' => 'Cab/Body', 'is_system_defined' => true],
            ['name' => 'Door - Right', 'category' => 'Cab/Body', 'is_system_defined' => true],
            ['name' => 'Windscreen', 'category' => 'Cab/Body', 'is_system_defined' => true],
            ['name' => 'Rear Window', 'category' => 'Cab/Body', 'is_system_defined' => true],
            ['name' => 'Side Window', 'category' => 'Cab/Body', 'is_system_defined' => true],
            ['name' => 'Mirror - Left', 'category' => 'Cab/Body', 'is_system_defined' => true],
            ['name' => 'Mirror - Right', 'category' => 'Cab/Body', 'is_system_defined' => true],
            ['name' => 'Wipers', 'category' => 'Cab/Body', 'is_system_defined' => true],
            ['name' => 'Air Conditioning Unit', 'category' => 'Cab/Body', 'is_system_defined' => true],
            ['name' => 'Heater', 'category' => 'Cab/Body', 'is_system_defined' => true],
        ];

        foreach ($components as $component) {
            Component::firstOrCreate(
                ['name' => $component['name']],
                $component
            );
        }
    }
}
