<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $today = Carbon::today();
        $in30Days = $today->copy()->addDays(30);
        $grace30Days = $today->copy()->subDays(30);

        // Get all active vehicles with their current tax periods
        $vehicles = Vehicle::active()
            ->with('currentTaxPeriod')
            ->get();

        $totalVehicles = $vehicles->count();
        $expiringSoon = 0;
        $overdue = 0;
        $inPenalty = 0;

        foreach ($vehicles as $vehicle) {
            $taxStatus = $vehicle->tax_status;

            switch ($taxStatus) {
                case 'expiring_soon':
                    $expiringSoon++;
                    break;
                case 'expired':
                    $overdue++;
                    break;
                case 'penalty':
                    $inPenalty++;
                    break;
            }
        }

        return response()->json([
            'total_vehicles' => $totalVehicles,
            'expiring_soon' => $expiringSoon,
            'overdue' => $overdue,
            'in_penalty' => $inPenalty,
        ]);
    }

    public function alerts(): JsonResponse
    {
        $today = Carbon::today();
        $in7Days = $today->copy()->addDays(7);

        // Get vehicles that are:
        // 1. Expiring within 7 days
        // 2. Already expired (overdue)
        // 3. In penalty zone
        $vehicles = Vehicle::active()
            ->with(['currentTaxPeriod', 'vehicleType'])
            ->get()
            ->filter(function ($vehicle) use ($in7Days) {
                $taxStatus = $vehicle->tax_status;
                $daysRemaining = $vehicle->days_remaining;

                // No tax period at all
                if ($taxStatus === 'no_tax') {
                    return true;
                }

                // Already expired or in penalty
                if (in_array($taxStatus, ['expired', 'penalty'])) {
                    return true;
                }

                // Expiring within 7 days
                if ($taxStatus === 'expiring_soon' && $daysRemaining !== null && $daysRemaining <= 7) {
                    return true;
                }

                return false;
            })
            ->sortBy(function ($vehicle) {
                // Sort by urgency: penalty first, then expired, then expiring soon
                $order = [
                    'penalty' => 0,
                    'expired' => 1,
                    'no_tax' => 2,
                    'expiring_soon' => 3,
                    'valid' => 4,
                ];
                return $order[$vehicle->tax_status] ?? 5;
            })
            ->values()
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'reference_name' => $vehicle->reference_name,
                    'vehicle_type' => $vehicle->vehicleType?->name,
                    'tax_status' => $vehicle->tax_status,
                    'tax_expiry_date' => $vehicle->tax_expiry_date,
                    'days_remaining' => $vehicle->days_remaining,
                ];
            });

        return response()->json([
            'data' => $vehicles,
            'count' => $vehicles->count(),
        ]);
    }
}
