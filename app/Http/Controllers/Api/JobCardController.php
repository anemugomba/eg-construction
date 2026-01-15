<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WatchListItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobCardController extends Controller
{
    /**
     * Display a listing of job cards.
     */
    public function index(Request $request): JsonResponse
    {
        $query = JobCard::with(['vehicle', 'site', 'submittedByUser', 'approvedByUser']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by vehicle
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        // Filter by site
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        // Filter by job type
        if ($request->filled('job_type')) {
            $query->where('job_type', $request->job_type);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('job_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('job_date', '<=', $request->to_date);
        }

        // Site DPFs can only see job cards from their sites
        $user = $request->user();
        if ($user->role === User::ROLE_SITE_DPF) {
            $userSiteIds = $user->sites()->pluck('sites.id');
            $query->whereIn('site_id', $userSiteIds);
        }

        $sortBy = $request->get('sort_by', 'job_date');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 50);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created job card.
     */
    public function store(Request $request): JsonResponse
    {
        // Site DPF, senior DPF, or admin can create job cards
        $allowedRoles = [User::ROLE_SITE_DPF, User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR];
        if (!in_array($request->user()->role, $allowedRoles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'job_type' => ['required', Rule::in(['repair', 'tyre_change', 'tyre_repair', 'other'])],
            'job_date' => 'required|date',
            'reading_at_job' => 'required|integer|min:0',
            'site_id' => 'required|exists:sites,id',
            'description' => 'required|string',
            'status' => ['sometimes', Rule::in(['draft', 'pending'])],
        ]);

        $vehicle = Vehicle::find($validated['vehicle_id']);

        // Get current site assignment for analytics
        $currentAssignment = $vehicle->siteAssignments()
            ->whereNull('ended_at')
            ->first();

        $validated['site_assignment_id'] = $currentAssignment?->id;
        $validated['status'] = $validated['status'] ?? 'draft';

        // If submitting immediately
        if ($validated['status'] === 'pending') {
            $validated['submitted_by'] = $request->user()->id;
            $validated['submitted_at'] = now();
        }

        $jobCard = JobCard::create($validated);

        return response()->json([
            'message' => 'Job card created successfully',
            'data' => $jobCard->load(['vehicle', 'site']),
        ], 201);
    }

    /**
     * Display the specified job card.
     */
    public function show(JobCard $jobCard): JsonResponse
    {
        $jobCard->load([
            'vehicle.machineType',
            'site',
            'submittedByUser',
            'approvedByUser',
            'components.component',
            'parts.partCatalog',
        ]);

        return response()->json([
            'data' => $jobCard,
        ]);
    }

    /**
     * Update the specified job card.
     */
    public function update(Request $request, JobCard $jobCard): JsonResponse
    {
        // Can only update draft or rejected job cards
        if (!$jobCard->canBeEdited()) {
            return response()->json([
                'message' => 'Job card can only be edited in draft or rejected status',
            ], 422);
        }

        $validated = $request->validate([
            'job_type' => ['sometimes', Rule::in(['repair', 'tyre_change', 'tyre_repair', 'other'])],
            'job_date' => 'sometimes|date',
            'reading_at_job' => 'sometimes|integer|min:0',
            'site_id' => 'sometimes|exists:sites,id',
            'description' => 'sometimes|string',
        ]);

        $jobCard->update($validated);

        return response()->json([
            'message' => 'Job card updated successfully',
            'data' => $jobCard->fresh()->load(['vehicle', 'site']),
        ]);
    }

    /**
     * Remove the specified job card.
     */
    public function destroy(Request $request, JobCard $jobCard): JsonResponse
    {
        // Can only delete draft job cards
        if (!$jobCard->isDraft()) {
            return response()->json([
                'message' => 'Only draft job cards can be deleted',
            ], 422);
        }

        $jobCard->delete();

        return response()->json([
            'message' => 'Job card deleted successfully',
        ]);
    }

    /**
     * Submit job card for approval.
     */
    public function submit(Request $request, JobCard $jobCard): JsonResponse
    {
        if (!$jobCard->canBeSubmitted()) {
            return response()->json([
                'message' => 'Job card cannot be submitted in current status',
            ], 422);
        }

        $jobCard->submit($request->user()->id);

        return response()->json([
            'message' => 'Job card submitted for approval',
            'data' => $jobCard->fresh()->load(['vehicle', 'site', 'submittedByUser']),
        ]);
    }

    /**
     * Approve a job card.
     */
    public function approve(Request $request, JobCard $jobCard): JsonResponse
    {
        // Only senior DPF or admin can approve
        if (!in_array($request->user()->role, [User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$jobCard->canBeApproved()) {
            return response()->json([
                'message' => 'Job card cannot be approved in current status',
            ], 422);
        }

        $jobCard->approve($request->user()->id);

        return response()->json([
            'message' => 'Job card approved',
            'data' => $jobCard->fresh()->load(['vehicle', 'site', 'approvedByUser']),
        ]);
    }

    /**
     * Reject a job card.
     */
    public function reject(Request $request, JobCard $jobCard): JsonResponse
    {
        // Only senior DPF or admin can reject
        if (!in_array($request->user()->role, [User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$jobCard->canBeApproved()) {
            return response()->json([
                'message' => 'Job card cannot be rejected in current status',
            ], 422);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        $jobCard->reject($request->user()->id, $validated['rejection_reason']);

        return response()->json([
            'message' => 'Job card rejected',
            'data' => $jobCard->fresh()->load(['vehicle', 'site', 'approvedByUser']),
        ]);
    }

    /**
     * Get related watch list items for this job card.
     */
    public function relatedWatchItems(JobCard $jobCard): JsonResponse
    {
        // Get active watch items for this vehicle
        $watchItems = WatchListItem::where('vehicle_id', $jobCard->vehicle_id)
            ->where('status', 'active')
            ->with(['component', 'inspectionResult.checklistItem'])
            ->get();

        return response()->json([
            'data' => $watchItems,
        ]);
    }

    /**
     * Resolve watch list items from this job card.
     */
    public function resolveWatchItems(Request $request, JobCard $jobCard): JsonResponse
    {
        // Job card must be approved
        if (!$jobCard->isApproved()) {
            return response()->json([
                'message' => 'Job card must be approved before resolving watch items',
            ], 422);
        }

        $validated = $request->validate([
            'watch_item_ids' => 'required|array',
            'watch_item_ids.*' => 'exists:watch_list_items,id',
        ]);

        $resolved = 0;
        foreach ($validated['watch_item_ids'] as $watchItemId) {
            $watchItem = WatchListItem::find($watchItemId);

            // Verify it belongs to the same vehicle
            if ($watchItem && $watchItem->vehicle_id === $jobCard->vehicle_id && $watchItem->status === 'active') {
                $watchItem->update([
                    'status' => 'resolved',
                    'resolved_by_job_card_id' => $jobCard->id,
                    'resolved_at' => now(),
                ]);
                $resolved++;
            }
        }

        return response()->json([
            'message' => "{$resolved} watch list items resolved",
            'resolved_count' => $resolved,
        ]);
    }
}
