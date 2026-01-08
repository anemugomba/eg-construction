<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleExemption;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleExemptionController extends Controller
{
    /**
     * List all exemptions for a vehicle
     */
    public function index(Vehicle $vehicle): JsonResponse
    {
        $exemptions = $vehicle->exemptions()
            ->orderBy('start_date', 'desc')
            ->get();

        return response()->json([
            'data' => $exemptions,
        ]);
    }

    /**
     * Create a new exemption (place vehicle off-road)
     */
    public function store(Request $request, Vehicle $vehicle): JsonResponse
    {
        // Check if vehicle already has an active exemption
        if ($vehicle->currentExemption) {
            return response()->json([
                'message' => 'Vehicle already has an active exemption',
                'current_exemption' => $vehicle->currentExemption,
            ], 422);
        }

        $validated = $request->validate([
            'start_date' => 'required|date',
            'duration_months' => 'required|integer|min:1|max:12',
            'reason' => 'nullable|string|max:500',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = $startDate->copy()->addMonths($validated['duration_months']);

        $exemption = $vehicle->exemptions()->create([
            'start_date' => $startDate,
            'end_date' => $endDate,
            'duration_months' => $validated['duration_months'],
            'status' => 'active',
            'reason' => $validated['reason'] ?? null,
        ]);

        return response()->json([
            'message' => 'Vehicle exemption created successfully',
            'data' => $exemption,
        ], 201);
    }

    /**
     * Show a specific exemption
     */
    public function show(VehicleExemption $exemption): JsonResponse
    {
        $exemption->load('vehicle');

        return response()->json([
            'data' => $exemption,
        ]);
    }

    /**
     * Update an exemption (extend, change dates, etc.)
     */
    public function update(Request $request, VehicleExemption $exemption): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'duration_months' => 'nullable|integer|min:1|max:12',
            'reason' => 'nullable|string|max:500',
        ]);

        // If end_date is provided directly, use it
        if (isset($validated['end_date'])) {
            $exemption->end_date = Carbon::parse($validated['end_date']);
            // Recalculate duration
            $exemption->duration_months = $exemption->start_date->diffInMonths($exemption->end_date);
        }

        // If duration_months is provided, recalculate end_date
        if (isset($validated['duration_months']) && !isset($validated['end_date'])) {
            $startDate = isset($validated['start_date'])
                ? Carbon::parse($validated['start_date'])
                : $exemption->start_date;
            $exemption->end_date = $startDate->copy()->addMonths($validated['duration_months']);
            $exemption->duration_months = $validated['duration_months'];
        }

        if (isset($validated['start_date'])) {
            $exemption->start_date = Carbon::parse($validated['start_date']);
        }

        if (array_key_exists('reason', $validated)) {
            $exemption->reason = $validated['reason'];
        }

        $exemption->save();

        return response()->json([
            'message' => 'Exemption updated successfully',
            'data' => $exemption->fresh(),
        ]);
    }

    /**
     * End an exemption early (remove from off-road)
     */
    public function endExemption(VehicleExemption $exemption): JsonResponse
    {
        if ($exemption->status !== 'active') {
            return response()->json([
                'message' => 'Exemption is not active',
            ], 422);
        }

        $exemption->endExemption();

        return response()->json([
            'message' => 'Exemption ended successfully. Vehicle now requires tax.',
            'data' => $exemption->fresh(),
        ]);
    }

    /**
     * Delete an exemption
     */
    public function destroy(VehicleExemption $exemption): JsonResponse
    {
        $exemption->delete();

        return response()->json([
            'message' => 'Exemption deleted successfully',
        ]);
    }

    /**
     * Get current exemption for a vehicle
     */
    public function current(Vehicle $vehicle): JsonResponse
    {
        $exemption = $vehicle->currentExemption;

        if (!$exemption) {
            return response()->json([
                'message' => 'No active exemption found',
                'data' => null,
            ]);
        }

        return response()->json([
            'data' => $exemption,
        ]);
    }
}
