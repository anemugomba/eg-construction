<?php

namespace Database\Seeders;

use App\Models\ChecklistItem;
use App\Models\InspectionTemplate;
use Illuminate\Database\Seeder;

class InspectionTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Monthly Standard Inspection - excludes quarterly-only items
        $monthlyTemplate = InspectionTemplate::firstOrCreate(
            ['name' => 'Monthly Standard'],
            [
                'name' => 'Monthly Standard',
                'description' => 'Standard monthly inspection covering all basic items. Excludes items that require quarterly deep inspection.',
                'frequency' => 'monthly',
                'is_active' => true,
            ]
        );

        // Get all non-quarterly items
        $monthlyItems = ChecklistItem::where('is_active', true)
            ->where('is_quarterly_only', false)
            ->pluck('id');

        // Sync items (won't duplicate if already exists)
        $monthlyTemplate->checklistItems()->syncWithoutDetaching(
            $monthlyItems->mapWithKeys(fn ($id) => [$id => ['is_required' => true]])->toArray()
        );

        // Quarterly Full Inspection - includes all items
        $quarterlyTemplate = InspectionTemplate::firstOrCreate(
            ['name' => 'Quarterly Full'],
            [
                'name' => 'Quarterly Full',
                'description' => 'Comprehensive quarterly inspection including all items and deep inspections of transmission, hydraulics, brakes, and undercarriage.',
                'frequency' => 'quarterly',
                'is_active' => true,
            ]
        );

        // Get all active items
        $allItems = ChecklistItem::where('is_active', true)->pluck('id');

        // Sync items
        $quarterlyTemplate->checklistItems()->syncWithoutDetaching(
            $allItems->mapWithKeys(fn ($id) => [$id => ['is_required' => true]])->toArray()
        );

        // Pre-Dispatch Inspection - critical safety items only
        $preDispatchTemplate = InspectionTemplate::firstOrCreate(
            ['name' => 'Pre-Dispatch'],
            [
                'name' => 'Pre-Dispatch',
                'description' => 'Quick pre-dispatch inspection focusing on safety-critical items before equipment leaves site.',
                'frequency' => 'custom',
                'is_active' => true,
            ]
        );

        // Get safety-critical items
        $criticalItems = ChecklistItem::where('is_active', true)
            ->whereHas('category', function ($q) {
                $q->whereIn('name', ['Safety', 'Brakes', 'Electrical', 'Tyres/Wheels']);
            })
            ->pluck('id');

        $preDispatchTemplate->checklistItems()->syncWithoutDetaching(
            $criticalItems->mapWithKeys(fn ($id) => [$id => ['is_required' => true]])->toArray()
        );

        // Post-Repair Inspection - verify repair work
        $postRepairTemplate = InspectionTemplate::firstOrCreate(
            ['name' => 'Post-Repair'],
            [
                'name' => 'Post-Repair',
                'description' => 'Inspection performed after major repairs to verify work quality and machine readiness.',
                'frequency' => 'custom',
                'is_active' => true,
            ]
        );

        // Use same items as monthly for post-repair
        $postRepairTemplate->checklistItems()->syncWithoutDetaching(
            $monthlyItems->mapWithKeys(fn ($id) => [$id => ['is_required' => false]])->toArray()
        );
    }
}
