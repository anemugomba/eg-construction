<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\JobCardComponent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobCardComponentController extends Controller
{
    /**
     * Display components for a job card.
     */
    public function index(JobCard $jobCard): JsonResponse
    {
        $components = $jobCard->components()->with('component')->get();

        return response()->json([
            'data' => $components,
        ]);
    }

    /**
     * Store a new component for a job card.
     */
    public function store(Request $request, JobCard $jobCard): JsonResponse
    {
        // Can only add components to draft or rejected job cards
        if (!$jobCard->canBeEdited()) {
            return response()->json([
                'message' => 'Components can only be added to draft or rejected job cards',
            ], 422);
        }

        $validated = $request->validate([
            'component_id' => 'nullable|exists:components,id',
            'name' => 'nullable|string|max:255',
            'action' => 'nullable|string|max:100',
            'reading_at_action' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $component = JobCardComponent::create([
            'job_card_id' => $jobCard->id,
            'component_id' => $validated['component_id'] ?? null,
            'name' => $validated['name'] ?? 'Component',
            'action' => $validated['action'] ?? 'other',
            'reading_at_action' => $validated['reading_at_action'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'Component added successfully',
            'data' => $component->load('component'),
        ], 201);
    }

    /**
     * Update a job card component (nested route).
     */
    public function update(Request $request, JobCard $jobCard, JobCardComponent $jobCardComponent): JsonResponse
    {
        return $this->performUpdate($request, $jobCardComponent);
    }

    /**
     * Update a job card component (flat route).
     */
    public function updateFlat(Request $request, JobCardComponent $jobCardComponent): JsonResponse
    {
        return $this->performUpdate($request, $jobCardComponent);
    }

    /**
     * Perform the actual update.
     */
    private function performUpdate(Request $request, JobCardComponent $jobCardComponent): JsonResponse
    {
        $jobCard = $jobCardComponent->jobCard;

        // Can only update components on draft or rejected job cards
        if (!$jobCard->canBeEdited()) {
            return response()->json([
                'message' => 'Components can only be updated on draft or rejected job cards',
            ], 422);
        }

        $validated = $request->validate([
            'component_id' => 'nullable|exists:components,id',
            'name' => 'nullable|string|max:255',
            'action' => 'nullable|string|max:100',
            'reading_at_action' => 'nullable|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $jobCardComponent->update($validated);

        return response()->json([
            'message' => 'Component updated successfully',
            'data' => $jobCardComponent->fresh()->load('component'),
        ]);
    }

    /**
     * Remove a component from a job card (nested route).
     */
    public function destroy(Request $request, JobCard $jobCard, JobCardComponent $jobCardComponent): JsonResponse
    {
        return $this->performDestroy($jobCardComponent);
    }

    /**
     * Remove a component from a job card (flat route).
     */
    public function destroyFlat(Request $request, JobCardComponent $jobCardComponent): JsonResponse
    {
        return $this->performDestroy($jobCardComponent);
    }

    /**
     * Perform the actual delete.
     */
    private function performDestroy(JobCardComponent $jobCardComponent): JsonResponse
    {
        $jobCard = $jobCardComponent->jobCard;

        // Can only remove components from draft or rejected job cards
        if (!$jobCard->canBeEdited()) {
            return response()->json([
                'message' => 'Components can only be removed from draft or rejected job cards',
            ], 422);
        }

        $jobCardComponent->delete();

        return response()->json([
            'message' => 'Component removed successfully',
        ]);
    }
}
