<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServicePart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServicePartController extends Controller
{
    /**
     * Display parts for a service.
     */
    public function index(Service $service): JsonResponse
    {
        $parts = $service->parts()->with('partCatalog')->get();

        return response()->json([
            'data' => $parts,
            'total_cost' => $parts->sum(fn ($p) => ($p->unit_cost ?? 0) * $p->quantity),
        ]);
    }

    /**
     * Store a new part for a service.
     */
    public function store(Request $request, Service $service): JsonResponse
    {
        // Can only add parts to draft or rejected services
        if (!$service->canBeEdited()) {
            return response()->json([
                'message' => 'Parts can only be added to draft or rejected services',
            ], 422);
        }

        $validated = $request->validate([
            'part_catalog_id' => 'nullable|exists:parts_catalog,id',
            'part_description' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

        $validated['service_id'] = $service->id;

        $part = ServicePart::create($validated);

        // Update total parts cost
        $this->updateTotalPartsCost($service);

        return response()->json([
            'message' => 'Part added successfully',
            'data' => $part->load('partCatalog'),
        ], 201);
    }

    /**
     * Remove a part from a service.
     */
    public function destroy(Request $request, ServicePart $servicePart): JsonResponse
    {
        $service = $servicePart->service;

        // Can only remove parts from draft or rejected services
        if (!$service->canBeEdited()) {
            return response()->json([
                'message' => 'Parts can only be removed from draft or rejected services',
            ], 422);
        }

        $servicePart->delete();

        // Update total parts cost
        $this->updateTotalPartsCost($service);

        return response()->json([
            'message' => 'Part removed successfully',
        ]);
    }

    /**
     * Update total parts cost on service.
     */
    private function updateTotalPartsCost(Service $service): void
    {
        $totalCost = $service->parts()->get()->sum(fn ($p) => ($p->unit_cost ?? 0) * $p->quantity);
        $service->update(['total_parts_cost' => $totalCost]);
    }
}
