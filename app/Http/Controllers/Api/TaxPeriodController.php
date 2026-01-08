<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TaxPeriod;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaxPeriodController extends Controller
{
    public function index(Vehicle $vehicle): JsonResponse
    {
        $taxPeriods = $vehicle->taxPeriods()
            ->orderBy('end_date', 'desc')
            ->get();

        return response()->json([
            'data' => $taxPeriods,
        ]);
    }

    public function store(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'amount_paid' => 'nullable|numeric|min:0',
        ]);

        $validated['amount_paid'] = $validated['amount_paid'] ?? 0;

        // Check if this is a late renewal (penalty)
        $previousPeriod = $vehicle->taxPeriods()
            ->orderBy('end_date', 'desc')
            ->first();

        $penaltyIncurred = false;
        if ($previousPeriod) {
            $previousEndDate = Carbon::parse($previousPeriod->end_date);
            $newStartDate = Carbon::parse($validated['start_date']);
            $daysSinceExpiry = $previousEndDate->diffInDays($newStartDate, false);

            // If renewing more than 30 days after previous expiry, penalty applies
            if ($daysSinceExpiry > 30) {
                $penaltyIncurred = true;
            }
        }

        $taxPeriod = $vehicle->taxPeriods()->create([
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'amount_paid' => $validated['amount_paid'],
            'status' => 'active',
            'penalty_incurred' => $penaltyIncurred,
        ]);

        return response()->json([
            'message' => 'Tax period created successfully',
            'data' => $taxPeriod,
        ], 201);
    }

    public function update(Request $request, TaxPeriod $taxPeriod): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'amount_paid' => 'nullable|numeric|min:0',
        ]);

        $validated['amount_paid'] = $validated['amount_paid'] ?? 0;

        $taxPeriod->update($validated);

        return response()->json([
            'message' => 'Tax period updated successfully',
            'data' => $taxPeriod->fresh(),
        ]);
    }

    public function destroy(TaxPeriod $taxPeriod): JsonResponse
    {
        $taxPeriod->delete();

        return response()->json([
            'message' => 'Tax period deleted successfully',
        ]);
    }
}
