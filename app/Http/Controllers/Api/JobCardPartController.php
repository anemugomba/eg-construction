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
        $parts = $jobCard->parts()->with('partCatalog')->get();

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
            'part_description' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

        $validated['job_card_id'] = $jobCard->id;

        $part = JobCardPart::create($validated);

        // Update total parts cost
        $this->updateTotalPartsCost($jobCard);

        return response()->json([
            'message' => 'Part added successfully',
            'data' => $part->load('partCatalog'),
        ], 201);
    }

    /**
     * Remove a part from a job card.
     */
    public function destroy(Request $request, JobCardPart $jobCardPart): JsonResponse
    {
        $jobCard = $jobCardPart->jobCard;

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
