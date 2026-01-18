<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\InspectionResult;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WatchListItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InspectionController extends Controller
{
    /**
     * Display a listing of inspections.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Inspection::with(['vehicle', 'site', 'template', 'submittedByUser', 'approvedByUser']);

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

        // Filter by template
        if ($request->filled('template_id')) {
            $query->where('template_id', $request->template_id);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('inspection_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('inspection_date', '<=', $request->to_date);
        }

        // Site-based scoping for non-admin users
        $user = $request->user();
        if ($user->role === User::ROLE_SITE_DPF) {
            $userSiteIds = $user->sites()->pluck('sites.id')->toArray();
            // Site DPFs can see inspections from their assigned sites OR inspections they created
            $query->where(function ($q) use ($userSiteIds, $user) {
                if (!empty($userSiteIds)) {
                    $q->whereIn('site_id', $userSiteIds);
                }
                $q->orWhere('created_by', $user->id);
            });
        } elseif ($user->role === User::ROLE_DATA_ENTRY || $user->role === User::ROLE_VIEW_ONLY) {
            $userSiteIds = $user->sites()->pluck('sites.id')->toArray();
            if (!empty($userSiteIds)) {
                $query->whereIn('site_id', $userSiteIds);
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        // Admin and Senior DPF can see all inspections

        $sortBy = $request->get('sort_by', 'inspection_date');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 50);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created inspection.
     */
    public function store(Request $request): JsonResponse
    {
        // Site DPF, senior DPF, or admin can create inspections
        $allowedRoles = [User::ROLE_SITE_DPF, User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR];
        if (!in_array($request->user()->role, $allowedRoles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'template_id' => 'required|exists:inspection_templates,id',
            'inspection_date' => 'required|date',
            'reading_at_inspection' => 'required|integer|min:0',
            'site_id' => 'required|exists:sites,id',
            'notes' => 'nullable|string',
        ]);

        $vehicle = Vehicle::find($validated['vehicle_id']);

        // Get current site assignment for analytics
        $currentAssignment = $vehicle->siteAssignments()
            ->whereNull('ended_at')
            ->first();

        $validated['site_assignment_id'] = $currentAssignment?->id;
        $validated['status'] = 'draft';
        $validated['completion_percentage'] = 0;

        $inspection = Inspection::create($validated);

        return response()->json([
            'message' => 'Inspection created successfully',
            'data' => $inspection->load(['vehicle', 'site', 'template']),
        ], 201);
    }

    /**
     * Display the specified inspection.
     */
    public function show(Inspection $inspection): JsonResponse
    {
        $inspection->load([
            'vehicle.machineType',
            'site',
            'template.checklistItems.category',
            'submittedByUser',
            'approvedByUser',
            'results.checklistItem.category',
        ]);

        return response()->json([
            'data' => $inspection,
        ]);
    }

    /**
     * Update the specified inspection.
     */
    public function update(Request $request, Inspection $inspection): JsonResponse
    {
        // Can only update draft or rejected inspections
        if (!$inspection->canBeEdited()) {
            return response()->json([
                'message' => 'Inspection can only be edited in draft or rejected status',
            ], 422);
        }

        $validated = $request->validate([
            'inspection_date' => 'sometimes|date',
            'reading_at_inspection' => 'sometimes|integer|min:0',
            'site_id' => 'sometimes|exists:sites,id',
            'notes' => 'nullable|string',
        ]);

        $inspection->update($validated);

        return response()->json([
            'message' => 'Inspection updated successfully',
            'data' => $inspection->fresh()->load(['vehicle', 'site', 'template']),
        ]);
    }

    /**
     * Remove the specified inspection.
     */
    public function destroy(Request $request, Inspection $inspection): JsonResponse
    {
        // Can only delete draft inspections
        if (!$inspection->isDraft()) {
            return response()->json([
                'message' => 'Only draft inspections can be deleted',
            ], 422);
        }

        $inspection->delete();

        return response()->json([
            'message' => 'Inspection deleted successfully',
        ]);
    }

    /**
     * Submit inspection for approval.
     */
    public function submit(Request $request, Inspection $inspection): JsonResponse
    {
        if (!$inspection->canBeSubmitted()) {
            return response()->json([
                'message' => 'Inspection cannot be submitted in current status',
            ], 422);
        }

        // Check completion percentage
        if ($inspection->completion_percentage < 100) {
            return response()->json([
                'message' => 'Inspection must be fully completed before submission',
                'completion_percentage' => $inspection->completion_percentage,
            ], 422);
        }

        $inspection->submit($request->user()->id);

        return response()->json([
            'message' => 'Inspection submitted for approval',
            'data' => $inspection->fresh()->load(['vehicle', 'site', 'template', 'submittedByUser']),
        ]);
    }

    /**
     * Approve an inspection.
     */
    public function approve(Request $request, Inspection $inspection): JsonResponse
    {
        // Only senior DPF or admin can approve
        if (!in_array($request->user()->role, [User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$inspection->canBeApproved()) {
            return response()->json([
                'message' => 'Inspection cannot be approved in current status',
            ], 422);
        }

        $inspection->approve($request->user()->id);

        // Create watch list items for service/repair ratings
        $this->createWatchListItems($inspection, $request->user()->id);

        return response()->json([
            'message' => 'Inspection approved',
            'data' => $inspection->fresh()->load(['vehicle', 'site', 'template', 'approvedByUser']),
        ]);
    }

    /**
     * Reject an inspection.
     */
    public function reject(Request $request, Inspection $inspection): JsonResponse
    {
        // Only senior DPF or admin can reject
        if (!in_array($request->user()->role, [User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$inspection->canBeApproved()) {
            return response()->json([
                'message' => 'Inspection cannot be rejected in current status',
            ], 422);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        $inspection->reject($request->user()->id, $validated['rejection_reason']);

        return response()->json([
            'message' => 'Inspection rejected',
            'data' => $inspection->fresh()->load(['vehicle', 'site', 'template', 'approvedByUser']),
        ]);
    }

    /**
     * Get inspection results.
     */
    public function results(Inspection $inspection): JsonResponse
    {
        $results = $inspection->results()
            ->with('checklistItem.category')
            ->get()
            ->groupBy('checklistItem.category.name');

        return response()->json([
            'data' => $results,
            'completion_percentage' => $inspection->completion_percentage,
        ]);
    }

    /**
     * Update inspection results in batch.
     */
    public function updateResults(Request $request, Inspection $inspection): JsonResponse
    {
        // Can only update results on draft or rejected inspections
        if (!$inspection->canBeEdited()) {
            return response()->json([
                'message' => 'Results can only be updated on draft or rejected inspections',
            ], 422);
        }

        $validated = $request->validate([
            'results' => 'required|array',
            'results.*.checklist_item_id' => 'required|exists:checklist_items,id',
            'results.*.rating' => ['required', Rule::in(['good', 'service', 'repair', 'replace'])],
            'results.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($inspection, $validated) {
            foreach ($validated['results'] as $resultData) {
                InspectionResult::updateOrCreate(
                    [
                        'inspection_id' => $inspection->id,
                        'checklist_item_id' => $resultData['checklist_item_id'],
                    ],
                    [
                        'rating' => $resultData['rating'],
                        'notes' => $resultData['notes'] ?? null,
                    ]
                );
            }
        });

        // Update completion percentage
        $this->updateCompletionPercentage($inspection);

        return response()->json([
            'message' => 'Results updated successfully',
            'completion_percentage' => $inspection->fresh()->completion_percentage,
        ]);
    }

    /**
     * Update inspection completion percentage.
     */
    private function updateCompletionPercentage(Inspection $inspection): void
    {
        $template = $inspection->template;
        $totalItems = $template->checklistItems()->count();
        $completedItems = $inspection->results()->count();

        $percentage = $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : 0;

        $inspection->update(['completion_percentage' => $percentage]);
    }

    /**
     * Create watch list items for inspection results that need attention.
     */
    private function createWatchListItems(Inspection $inspection, string $userId): void
    {
        $resultsNeedingAttention = $inspection->results()
            ->whereIn('rating', ['service', 'repair'])
            ->with('checklistItem')
            ->get();

        foreach ($resultsNeedingAttention as $result) {
            // Find or create component based on checklist item
            $componentId = null;
            if ($result->checklistItem) {
                // Try to find matching component by name
                $component = \App\Models\Component::where('name', 'like', "%{$result->checklistItem->name}%")->first();
                $componentId = $component?->id;
            }

            WatchListItem::create([
                'vehicle_id' => $inspection->vehicle_id,
                'component_id' => $componentId,
                'inspection_result_id' => $result->id,
                'rating_at_creation' => $result->rating,
                'notes' => $result->notes,
                'status' => 'active',
                'created_by' => $userId,
            ]);
        }
    }
}
