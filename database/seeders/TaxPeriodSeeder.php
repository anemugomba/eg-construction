<?php

namespace Database\Seeders;

use App\Models\TaxPeriod;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TaxPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = Vehicle::all();
        $today = Carbon::today();

        foreach ($vehicles as $index => $vehicle) {
            // Create varied tax scenarios for realistic demo
            $scenario = $index % 10;

            switch ($scenario) {
                case 0:
                case 1:
                case 2:
                    // Valid - expires in 60-120 days (30% of fleet)
                    $this->createTaxPeriod($vehicle, $today->copy()->subMonths(6), $today->copy()->addDays(rand(60, 120)));
                    break;

                case 3:
                case 4:
                    // Expiring soon - expires in 7-30 days (20% of fleet)
                    $this->createTaxPeriod($vehicle, $today->copy()->subMonths(8), $today->copy()->addDays(rand(7, 30)));
                    break;

                case 5:
                    // Very soon - expires in 1-7 days (10% of fleet)
                    $this->createTaxPeriod($vehicle, $today->copy()->subMonths(4), $today->copy()->addDays(rand(1, 7)));
                    break;

                case 6:
                case 7:
                    // Expired but in grace period - 1-25 days overdue (20% of fleet)
                    $this->createTaxPeriod($vehicle, $today->copy()->subMonths(12), $today->copy()->subDays(rand(1, 25)));
                    break;

                case 8:
                    // In penalty zone - 35-60 days overdue (10% of fleet)
                    $this->createTaxPeriod($vehicle, $today->copy()->subMonths(12), $today->copy()->subDays(rand(35, 60)));
                    break;

                case 9:
                    // Valid with penalty history - renewed late in the past (10% of fleet)
                    // First period - old
                    TaxPeriod::create([
                        'vehicle_id' => $vehicle->id,
                        'start_date' => $today->copy()->subMonths(20),
                        'end_date' => $today->copy()->subMonths(16),
                        'amount_paid' => rand(150, 300),
                        'status' => 'expired',
                        'penalty_incurred' => false,
                    ]);
                    // Second period - renewed late (penalty)
                    TaxPeriod::create([
                        'vehicle_id' => $vehicle->id,
                        'start_date' => $today->copy()->subMonths(14),
                        'end_date' => $today->copy()->subMonths(6),
                        'amount_paid' => rand(300, 500), // Double due to penalty
                        'status' => 'expired',
                        'penalty_incurred' => true,
                    ]);
                    // Current period - valid
                    $this->createTaxPeriod($vehicle, $today->copy()->subMonths(6), $today->copy()->addDays(rand(60, 90)));
                    break;
            }
        }
    }

    private function createTaxPeriod(Vehicle $vehicle, Carbon $startDate, Carbon $endDate): void
    {
        // Determine period length (4, 8, or 12 months typically)
        $months = $startDate->diffInMonths($endDate);

        // Base amount varies by period length
        $baseAmount = match (true) {
            $months <= 5 => rand(120, 180),   // 4 month period
            $months <= 9 => rand(220, 320),   // 8 month period
            default => rand(350, 500),         // 12 month period
        };

        TaxPeriod::create([
            'vehicle_id' => $vehicle->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'amount_paid' => $baseAmount,
            'status' => 'active',
            'penalty_incurred' => false,
        ]);
    }
}
