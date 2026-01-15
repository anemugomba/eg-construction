<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WatchListItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WatchListController extends Controller
{
    /**
     * Display a listing of watch list items.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WatchListItem::with([
            'vehicle',
            'component',
            'inspectionResult.checklistItem',
            'resolvedByJobCard',
            'createdByUser',
        ]);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by vehicle
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        // Filter by rating
        if ($request->filled('rating')) {
            $query->where('rating_at_creation', $request->rating);
        }

        // Filter by review date range
        if ($request->filled('review_from')) {
            $query->whereDate('review_date', '>=', $request->review_from);
        }
        if ($request->filled('review_to')) {
            $query->whereDate('review_date', '<=', $request->review_to);
        }

        // Filter by overdue review
        if ($request->boolean('overdue_only')) {
            $query->where('status', 'active')
                  ->whereNotNull('review_date')
                  ->whereDate('review_date', '<', now());
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 50);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a new watch list item (manual creation).
     */
    public function store(Request $request): JsonResponse
    {
        // Site DPF, senior DPF, or admin can create watch list items
        $allowedRoles = [User::ROLE_SITE_DPF, User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR];
        if (!in_array($request->user()->role, $allowedRoles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'component_id' => 'nullable|exists:components,id',
            'rating_at_creation' => ['required', Rule::in(['service', 'repair'])],
            'review_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $validated['status'] = 'active';
        $validated['created_by'] = $request->user()->id;

        $watchItem = WatchListItem::create($validated);

        return response()->json([
            'message' => 'Watch list item created successfully',
            'data' => $watchItem->load(['vehicle', 'component']),
        ], 201);
    }

    /**
     * Display the specified watch list item.
     */
    public function show(WatchListItem $watchListItem): JsonResponse
    {
        $watchListItem->load([
            'vehicle',
            'component',
            'inspectionResult.checklistItem.category',
            'inspectionResult.inspection',
            'resolvedByJobCard',
            'createdByUser',
        ]);

        return response()->json([
            'data' => $watchListItem,
        ]);
    }

    /**
     * Update the specified watch list item.
     */
    public function update(Request $request, WatchListItem $watchListItem): JsonResponse
    {
        // Can only update active items
        if ($watchListItem->status !== 'active') {
            return response()->json([
                'message' => 'Only active watch list items can be updated',
            ], 422);
        }

        $validated = $request->validate([
            'review_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $watchListItem->update($validated);

        return response()->json([
            'message' => 'Watch list item updated successfully',
            'data' => $watchListItem->fresh()->load(['vehicle', 'component']),
        ]);
    }

    /**
     * Remove the specified watch list item.
     */
    public function destroy(Request $request, WatchListItem $watchListItem): JsonResponse
    {
        // Only admin can delete watch list items
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $watchListItem->delete();

        return response()->json([
            'message' => 'Watch list item deleted successfully',
        ]);
    }

    /**
     * Manually resolve a watch list item.
     */
    public function resolve(Request $request, WatchListItem $watchListItem): JsonResponse
    {
        // Site DPF, senior DPF, or admin can resolve
        $allowedRoles = [User::ROLE_SITE_DPF, User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR];
        if (!in_array($request->user()->role, $allowedRoles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($watchListItem->status !== 'active') {
            return response()->json([
                'message' => 'Only active items can be resolved',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        $watchListItem->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'notes' => $validated['notes'] ?? $watchListItem->notes,
        ]);

        return response()->json([
            'message' => 'Watch list item resolved',
            'data' => $watchListItem->fresh(),
        ]);
    }
}
