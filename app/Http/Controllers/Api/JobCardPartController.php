<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\JobCardPart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobCardPartController extends Controller
{
    /**
     * Display parts for a job card.
     */
    public function index(JobCard $jobCard): JsonResponse
    {
        $parts = $jobCard->parts()->with('catalogEntry')->get();

        return response()->json([
            'data' => $parts,
            'total_cost' => $parts->sum(fn ($p) => ($p->unit_cost ?? 0) * $p->quantity),
        ]);
    }

    /**
     * Store a new part for a job card.
     */
    public function store(Request $request, JobCard $jobCard): JsonResponse
    {
        // Can only add parts to draft or rejected job cards
        if (!$jobCard->canBeEdited()) {
            return response()->json([
                'message' => 'Parts can only be added to draft or rejected job cards',
            ], 422);
        }

        $validated = $request->validate([
            'part_catalog_id' => 'nullable|exists:parts_catalog,id',
            'name' => 'nullable|string|max:255',
            'part_number' => 'nullable|string|max:100',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

        $part = JobCardPart::create([
            'job_card_id' => $jobCard->id,
            'part_catalog_id' => $validated['part_catalog_id'] ?? null,
            'name' => $validated['name'] ?? $validated['part_number'] ?? 'Part',
            'quantity' => $validated['quantity'],
            'unit_cost' => $validated['unit_cost'] ?? 0,
        ]);

        // Update total parts cost
        $this->updateTotalPartsCost($jobCard);

        return response()->json([
            'message' => 'Part added successfully',
            'data' => $part->load('catalogEntry'),
        ], 201);
    }

    /**
     * Update a job card part (nested route).
     */
    public function update(Request $request, JobCard $jobCard, JobCardPart $jobCardPart): JsonResponse
    {
        return $this->performUpdate($request, $jobCardPart, $jobCard);
    }

    /**
     * Update a job card part (flat route).
     */
    public function updateFlat(Request $request, JobCardPart $jobCardPart): JsonResponse
    {
        return $this->performUpdate($request, $jobCardPart, $jobCardPart->jobCard);
    }

    /**
     * Perform the actual update.
     */
    private function performUpdate(Request $request, JobCardPart $jobCardPart, JobCard $jobCard): JsonResponse
    {
        // Can only update parts on draft or rejected job cards
        if (!$jobCard->canBeEdited()) {
            return response()->json([
                'message' => 'Parts can only be updated on draft or rejected job cards',
            ], 422);
        }

        $validated = $request->validate([
            'part_catalog_id' => 'nullable|exists:parts_catalog,id',
            'name' => 'nullable|string|max:255',
            'part_number' => 'nullable|string|max:100',
            'quantity' => 'sometimes|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

        $jobCardPart->update($validated);

        // Update total parts cost
        $this->updateTotalPartsCost($jobCard);

        return response()->json([
            'message' => 'Part updated successfully',
            'data' => $jobCardPart->fresh()->load('catalogEntry'),
        ]);
    }

    /**
     * Remove a part from a job card (nested route).
     */
    public function destroy(Request $request, JobCard $jobCard, JobCardPart $jobCardPart): JsonResponse
    {
        return $this->performDestroy($jobCardPart, $jobCard);
    }

    /**
     * Remove a part from a job card (flat route).
     */
    public function destroyFlat(Request $request, JobCardPart $jobCardPart): JsonResponse
    {
        return $this->performDestroy($jobCardPart, $jobCardPart->jobCard);
    }

    /**
     * Perform the actual delete.
     */
    private function performDestroy(JobCardPart $jobCardPart, JobCard $jobCard): JsonResponse
    {
        // Can only remove parts from draft or rejected job cards
        if (!$jobCard->canBeEdited()) {
            return response()->json([
                'message' => 'Parts can only be removed from draft or rejected job cards',
            ], 422);
        }

        $jobCardPart->delete();

        // Update total parts cost
        $this->updateTotalPartsCost($jobCard);

        return response()->json([
            'message' => 'Part removed successfully',
        ]);
    }

    /**
     * Update total parts cost on job card.
     */
    private function updateTotalPartsCost(JobCard $jobCard): void
    {
        $totalCost = $jobCard->parts()->get()->sum(fn ($p) => ($p->unit_cost ?? 0) * $p->quantity);
        $jobCard->update(['total_parts_cost' => $totalCost]);
    }
}
