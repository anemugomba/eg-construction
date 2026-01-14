<?php

namespace Database\Seeders;

use App\Models\ChecklistCategory;
use App\Models\ChecklistItem;
use Illuminate\Database\Seeder;

class ChecklistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Engine',
                'display_order' => 1,
                'items' => [
                    ['name' => 'Engine Oil Level', 'description' => 'Check engine oil level and condition'],
                    ['name' => 'Engine Oil Leaks', 'description' => 'Inspect for any oil leaks around engine'],
                    ['name' => 'Engine Coolant Level', 'description' => 'Check coolant level in reservoir'],
                    ['name' => 'Engine Coolant Leaks', 'description' => 'Inspect for coolant leaks'],
                    ['name' => 'Fan Belt Condition', 'description' => 'Check fan belt for wear and tension'],
                    ['name' => 'Air Filter Condition', 'description' => 'Inspect air filter for cleanliness'],
                    ['name' => 'Fuel Filter Condition', 'description' => 'Check fuel filter for blockage'],
                    ['name' => 'Engine Mount Condition', 'description' => 'Inspect engine mounts for wear/damage'],
                ],
            ],
            [
                'name' => 'Transmission',
                'display_order' => 2,
                'items' => [
                    ['name' => 'Transmission Oil Level', 'description' => 'Check transmission fluid level'],
                    ['name' => 'Transmission Oil Leaks', 'description' => 'Inspect for transmission fluid leaks'],
                    ['name' => 'Gear Shifting', 'description' => 'Test gear engagement and smooth shifting'],
                    ['name' => 'Clutch Operation', 'description' => 'Check clutch engagement and slippage'],
                    ['name' => 'Torque Converter', 'description' => 'Inspect torque converter operation', 'is_quarterly_only' => true],
                ],
            ],
            [
                'name' => 'Hydraulics',
                'display_order' => 3,
                'items' => [
                    ['name' => 'Hydraulic Oil Level', 'description' => 'Check hydraulic fluid level'],
                    ['name' => 'Hydraulic Oil Leaks', 'description' => 'Inspect all hydraulic lines for leaks'],
                    ['name' => 'Hydraulic Hose Condition', 'description' => 'Check hoses for wear, cracks, bulges'],
                    ['name' => 'Hydraulic Cylinder Operation', 'description' => 'Test all cylinder functions'],
                    ['name' => 'Hydraulic Pump Condition', 'description' => 'Check pump for noise and pressure', 'is_quarterly_only' => true],
                    ['name' => 'Control Valve Operation', 'description' => 'Test all control valves', 'is_quarterly_only' => true],
                ],
            ],
            [
                'name' => 'Undercarriage',
                'display_order' => 4,
                'items' => [
                    ['name' => 'Track Condition', 'description' => 'Inspect track pads/shoes for wear'],
                    ['name' => 'Track Tension', 'description' => 'Check and adjust track tension'],
                    ['name' => 'Sprocket Condition', 'description' => 'Inspect drive sprocket for wear'],
                    ['name' => 'Idler Condition', 'description' => 'Check idler wheels for wear/damage'],
                    ['name' => 'Roller Condition', 'description' => 'Inspect track rollers'],
                    ['name' => 'Track Frame', 'description' => 'Check track frame for damage', 'is_quarterly_only' => true],
                ],
            ],
            [
                'name' => 'Electrical',
                'display_order' => 5,
                'items' => [
                    ['name' => 'Battery Condition', 'description' => 'Check battery terminals and charge'],
                    ['name' => 'Starter Motor', 'description' => 'Test starter operation'],
                    ['name' => 'Alternator Output', 'description' => 'Check charging system output'],
                    ['name' => 'Wiring Condition', 'description' => 'Inspect wiring for damage/wear'],
                    ['name' => 'Warning Lights', 'description' => 'Test all warning indicators'],
                    ['name' => 'Work Lights', 'description' => 'Test all work lights and headlights'],
                    ['name' => 'Horn Operation', 'description' => 'Test horn function'],
                ],
            ],
            [
                'name' => 'Brakes',
                'display_order' => 6,
                'items' => [
                    ['name' => 'Service Brake Operation', 'description' => 'Test service brake effectiveness'],
                    ['name' => 'Parking Brake Operation', 'description' => 'Test parking brake hold'],
                    ['name' => 'Brake Pad/Shoe Condition', 'description' => 'Check brake pad/shoe wear'],
                    ['name' => 'Brake Fluid Level', 'description' => 'Check brake fluid level'],
                    ['name' => 'Brake Lines', 'description' => 'Inspect brake lines for leaks/damage'],
                    ['name' => 'Brake Drums/Discs', 'description' => 'Inspect drums/discs for wear', 'is_quarterly_only' => true],
                ],
            ],
            [
                'name' => 'Steering',
                'display_order' => 7,
                'items' => [
                    ['name' => 'Steering Response', 'description' => 'Test steering wheel response'],
                    ['name' => 'Steering Cylinder', 'description' => 'Check steering cylinder operation'],
                    ['name' => 'Tie Rod Ends', 'description' => 'Inspect tie rod ends for wear'],
                    ['name' => 'King Pins', 'description' => 'Check king pin condition'],
                    ['name' => 'Power Steering Fluid', 'description' => 'Check power steering fluid level'],
                ],
            ],
            [
                'name' => 'Body/Cab',
                'display_order' => 8,
                'items' => [
                    ['name' => 'Cab Condition', 'description' => 'Inspect cab for damage/corrosion'],
                    ['name' => 'Door Operation', 'description' => 'Test all doors open/close properly'],
                    ['name' => 'Windows Condition', 'description' => 'Check all windows for cracks'],
                    ['name' => 'Mirrors', 'description' => 'Inspect all mirrors condition'],
                    ['name' => 'Seat Condition', 'description' => 'Check seat adjustment and belt'],
                    ['name' => 'HVAC System', 'description' => 'Test heating/AC operation'],
                    ['name' => 'Wipers/Washers', 'description' => 'Test wiper and washer operation'],
                ],
            ],
            [
                'name' => 'Attachments',
                'display_order' => 9,
                'items' => [
                    ['name' => 'Bucket Condition', 'description' => 'Inspect bucket for wear/damage'],
                    ['name' => 'Bucket Teeth', 'description' => 'Check bucket teeth wear'],
                    ['name' => 'Quick Coupler', 'description' => 'Test quick coupler operation'],
                    ['name' => 'Attachment Pins', 'description' => 'Inspect pins and bushings'],
                    ['name' => 'Boom/Arm Condition', 'description' => 'Check boom/arm for cracks'],
                    ['name' => 'Blade Condition', 'description' => 'Inspect blade edge wear (graders/dozers)'],
                ],
            ],
            [
                'name' => 'Safety',
                'display_order' => 10,
                'items' => [
                    ['name' => 'Fire Extinguisher', 'description' => 'Check fire extinguisher present and charged'],
                    ['name' => 'First Aid Kit', 'description' => 'Verify first aid kit present and stocked'],
                    ['name' => 'Warning Triangle', 'description' => 'Check warning triangle present'],
                    ['name' => 'Reflective Tape', 'description' => 'Inspect reflective tape condition'],
                    ['name' => 'Backup Alarm', 'description' => 'Test backup alarm operation'],
                    ['name' => 'ROPS/FOPS Condition', 'description' => 'Inspect roll-over/fall-over protection'],
                    ['name' => 'Seat Belt', 'description' => 'Check seat belt condition and function'],
                ],
            ],
            [
                'name' => 'Tyres/Wheels',
                'display_order' => 11,
                'items' => [
                    ['name' => 'Tyre Condition', 'description' => 'Check tyre tread and sidewalls'],
                    ['name' => 'Tyre Pressure', 'description' => 'Check and adjust tyre pressure'],
                    ['name' => 'Wheel Nuts', 'description' => 'Check wheel nut torque'],
                    ['name' => 'Rim Condition', 'description' => 'Inspect rims for cracks/damage'],
                    ['name' => 'Spare Tyre', 'description' => 'Check spare tyre condition and tools'],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $items = $categoryData['items'];
            unset($categoryData['items']);

            $category = ChecklistCategory::firstOrCreate(
                ['name' => $categoryData['name']],
                $categoryData
            );

            $displayOrder = 1;
            foreach ($items as $item) {
                ChecklistItem::firstOrCreate(
                    [
                        'category_id' => $category->id,
                        'name' => $item['name'],
                    ],
                    [
                        'category_id' => $category->id,
                        'name' => $item['name'],
                        'description' => $item['description'] ?? null,
                        'is_quarterly_only' => $item['is_quarterly_only'] ?? false,
                        'photo_required_on_repair' => true,
                        'photo_required_on_replace' => true,
                        'display_order' => $displayOrder++,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
