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
        $parts = $service->parts()->with('catalogEntry')->get();

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
            'name' => 'nullable|string|max:255',
            'part_number' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

        $part = ServicePart::create([
            'service_id' => $service->id,
            'part_catalog_id' => $validated['part_catalog_id'] ?? null,
            'name' => $validated['name'] ?? $validated['part_number'] ?? 'Part',
            'quantity' => $validated['quantity'],
            'unit_cost' => $validated['unit_cost'] ?? 0,
        ]);

        // Update total parts cost
        $this->updateTotalPartsCost($service);

        return response()->json([
            'message' => 'Part added successfully',
            'data' => $part->load('catalogEntry'),
        ], 201);
    }

    /**
     * Update a service part (nested route).
     */
    public function update(Request $request, Service $service, ServicePart $servicePart): JsonResponse
    {
        return $this->performUpdate($request, $servicePart, $service);
    }

    /**
     * Update a service part (flat route).
     */
    public function updateFlat(Request $request, ServicePart $servicePart): JsonResponse
    {
        return $this->performUpdate($request, $servicePart, $servicePart->service);
    }

    /**
     * Perform the actual update.
     */
    private function performUpdate(Request $request, ServicePart $servicePart, Service $service): JsonResponse
    {
        // Can only update parts on draft or rejected services
        if (!$service->canBeEdited()) {
            return response()->json([
                'message' => 'Parts can only be updated on draft or rejected services',
            ], 422);
        }

        $validated = $request->validate([
            'part_catalog_id' => 'nullable|exists:parts_catalog,id',
            'name' => 'nullable|string|max:255',
            'part_number' => 'nullable|string|max:100',
            'quantity' => 'sometimes|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

        $servicePart->update($validated);

        // Update total parts cost
        $this->updateTotalPartsCost($service);

        return response()->json([
            'message' => 'Part updated successfully',
            'data' => $servicePart->fresh()->load('catalogEntry'),
        ]);
    }

    /**
     * Remove a part from a service (nested route).
     */
    public function destroy(Request $request, Service $service, ServicePart $servicePart): JsonResponse
    {
        return $this->performDestroy($servicePart, $service);
    }

    /**
     * Remove a part from a service (flat route).
     */
    public function destroyFlat(Request $request, ServicePart $servicePart): JsonResponse
    {
        return $this->performDestroy($servicePart, $servicePart->service);
    }

    /**
     * Perform the actual delete.
     */
    private function performDestroy(ServicePart $servicePart, Service $service): JsonResponse
    {
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
