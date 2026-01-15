<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OilAnalysis;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OilAnalysisController extends Controller
{
    /**
     * Display oil analyses for a vehicle.
     */
    public function index(Request $request, Vehicle $vehicle): JsonResponse
    {
        $query = $vehicle->oilAnalyses()->with('createdByUser');

        if ($request->filled('from_date')) {
            $query->whereDate('analysis_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('analysis_date', '<=', $request->to_date);
        }

        $query->orderBy('analysis_date', 'desc');

        $perPage = $request->get('per_page', 50);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a new oil analysis for a vehicle.
     */
    public function store(Request $request, Vehicle $vehicle): JsonResponse
    {
        // Site DPF, senior DPF, or admin can create oil analyses
        $allowedRoles = [User::ROLE_SITE_DPF, User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR];
        if (!in_array($request->user()->role, $allowedRoles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'analysis_date' => 'required|date',
            'reading_at_analysis' => 'required|integer|min:0',
            'lab_reference' => 'nullable|string|max:50',
            'results_json' => 'required|array',
            'iron_ppm' => 'nullable|integer|min:0',
            'silicon_ppm' => 'nullable|integer|min:0',
            'viscosity_40c' => 'nullable|numeric|min:0',
            'viscosity_100c' => 'nullable|numeric|min:0',
            'interpretation' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'next_analysis_due' => 'nullable|date',
        ]);

        $validated['vehicle_id'] = $vehicle->id;
        $validated['created_by'] = $request->user()->id;

        $oilAnalysis = OilAnalysis::create($validated);

        return response()->json([
            'message' => 'Oil analysis created successfully',
            'data' => $oilAnalysis,
        ], 201);
    }

    /**
     * Display the specified oil analysis.
     */
    public function show(OilAnalysis $oilAnalysis): JsonResponse
    {
        $oilAnalysis->load(['vehicle', 'createdByUser']);

        return response()->json([
            'data' => $oilAnalysis,
        ]);
    }

    /**
     * Update the specified oil analysis.
     */
    public function update(Request $request, OilAnalysis $oilAnalysis): JsonResponse
    {
        // Site DPF, senior DPF, or admin can update oil analyses
        $allowedRoles = [User::ROLE_SITE_DPF, User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR];
        if (!in_array($request->user()->role, $allowedRoles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'analysis_date' => 'sometimes|date',
            'reading_at_analysis' => 'sometimes|integer|min:0',
            'lab_reference' => 'nullable|string|max:50',
            'results_json' => 'sometimes|array',
            'iron_ppm' => 'nullable|integer|min:0',
            'silicon_ppm' => 'nullable|integer|min:0',
            'viscosity_40c' => 'nullable|numeric|min:0',
            'viscosity_100c' => 'nullable|numeric|min:0',
            'interpretation' => 'nullable|string',
            'recommendations' => 'nullable|string',
            'next_analysis_due' => 'nullable|date',
        ]);

        $oilAnalysis->update($validated);

        return response()->json([
            'message' => 'Oil analysis updated successfully',
            'data' => $oilAnalysis->fresh(),
        ]);
    }

    /**
     * Remove the specified oil analysis.
     */
    public function destroy(Request $request, OilAnalysis $oilAnalysis): JsonResponse
    {
        // Only admin can delete oil analyses
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $oilAnalysis->delete();

        return response()->json([
            'message' => 'Oil analysis deleted successfully',
        ]);
    }
}
