<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\ExpoNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceController extends Controller
{
    /**
     * Display a listing of services.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Service::with(['vehicle', 'site', 'submittedByUser', 'approvedByUser']);

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

        // Filter by service type
        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('service_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('service_date', '<=', $request->to_date);
        }

        // Site-based scoping for non-admin users
        $user = $request->user();
        if ($user->role === User::ROLE_SITE_DPF) {
            $userSiteIds = $user->sites()->pluck('sites.id')->toArray();
            // Site DPFs can see services from their assigned sites OR services they created
            $query->where(function ($q) use ($userSiteIds, $user) {
                if (!empty($userSiteIds)) {
                    $q->whereIn('site_id', $userSiteIds);
                }
                // Always allow seeing their own created services
                $q->orWhere('created_by', $user->id);
            });
        } elseif ($user->role === User::ROLE_DATA_ENTRY || $user->role === User::ROLE_VIEW_ONLY) {
            // Data entry and view-only users only see services from their assigned sites
            $userSiteIds = $user->sites()->pluck('sites.id')->toArray();
            if (!empty($userSiteIds)) {
                $query->whereIn('site_id', $userSiteIds);
            } else {
                // No sites assigned = no services visible
                $query->whereRaw('1 = 0');
            }
        }
        // Admin and Senior DPF can see all services

        $sortBy = $request->get('sort_by', 'service_date');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 50);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created service.
     */
    public function store(Request $request): JsonResponse
    {
        // Site DPF, senior DPF, or admin can create services
        $allowedRoles = [User::ROLE_SITE_DPF, User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR];
        if (!in_array($request->user()->role, $allowedRoles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'service_type' => ['required', Rule::in(['minor', 'major'])],
            'service_date' => 'required|date',
            'reading_at_service' => 'required|integer|min:0',
            'site_id' => 'required|exists:sites,id',
            'notes' => 'nullable|string',
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

        $service = Service::create($validated);

        // Send push notification to approvers for new service (exclude creator)
        $service->load(['vehicle', 'site']);
        if ($service->site) {
            app(ExpoNotificationService::class)->notifyApprovers(
                $service->site,
                'New Service Created',
                "Service - {$service->service_type} ({$validated['status']})",
                ['type' => 'service', 'id' => $service->id],
                $request->user()->id
            );
        }

        return response()->json([
            'message' => 'Service created successfully',
            'data' => $service,
        ], 201);
    }

    /**
     * Display the specified service.
     */
    public function show(Service $service): JsonResponse
    {
        $service->load([
            'vehicle.machineType',
            'site',
            'submittedByUser',
            'approvedByUser',
            'parts.catalogEntry',
        ]);

        return response()->json([
            'data' => $service,
        ]);
    }

    /**
     * Update the specified service.
     */
    public function update(Request $request, Service $service): JsonResponse
    {
        // Can only update draft or rejected services
        if (!$service->canBeEdited()) {
            return response()->json([
                'message' => 'Service can only be edited in draft or rejected status',
            ], 422);
        }

        $validated = $request->validate([
            'service_type' => ['sometimes', Rule::in(['minor', 'major'])],
            'service_date' => 'sometimes|date',
            'reading_at_service' => 'sometimes|integer|min:0',
            'site_id' => 'sometimes|exists:sites,id',
            'notes' => 'nullable|string',
        ]);

        $service->update($validated);

        return response()->json([
            'message' => 'Service updated successfully',
            'data' => $service->fresh()->load(['vehicle', 'site']),
        ]);
    }

    /**
     * Remove the specified service.
     */
    public function destroy(Request $request, Service $service): JsonResponse
    {
        // Can only delete draft services
        if (!$service->isDraft()) {
            return response()->json([
                'message' => 'Only draft services can be deleted',
            ], 422);
        }

        $service->delete();

        return response()->json([
            'message' => 'Service deleted successfully',
        ]);
    }

    /**
     * Submit service for approval.
     */
    public function submit(Request $request, Service $service): JsonResponse
    {
        if (!$service->canBeSubmitted()) {
            return response()->json([
                'message' => 'Service cannot be submitted in current status',
            ], 422);
        }

        $service->submit($request->user()->id);

        // Send push notification to approvers (exclude submitter)
        $service->load(['vehicle', 'site']);
        if ($service->site) {
            app(ExpoNotificationService::class)->notifyApprovers(
                $service->site,
                'Service Pending Approval',
                "Service request for {$service->vehicle->fleet_number} needs approval",
                ['type' => 'approval', 'id' => $service->id],
                $request->user()->id
            );
        }

        return response()->json([
            'message' => 'Service submitted for approval',
            'data' => $service->fresh()->load(['vehicle', 'site', 'submittedByUser']),
        ]);
    }

    /**
     * Approve a service.
     */
    public function approve(Request $request, Service $service): JsonResponse
    {
        // Only senior DPF or admin can approve
        if (!in_array($request->user()->role, [User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$service->canBeApproved()) {
            return response()->json([
                'message' => 'Service cannot be approved in current status',
            ], 422);
        }

        $service->approve($request->user()->id);

        // Update vehicle's last service tracking
        $this->updateVehicleServiceTracking($service);

        // Notify the submitter
        if ($service->submittedByUser) {
            app(ExpoNotificationService::class)->sendToUser(
                $service->submittedByUser,
                'Service Approved',
                "Your {$service->service_type} service request has been approved",
                ['type' => 'service', 'id' => $service->id]
            );
        }

        return response()->json([
            'message' => 'Service approved',
            'data' => $service->fresh()->load(['vehicle', 'site', 'approvedByUser']),
        ]);
    }

    /**
     * Reject a service.
     */
    public function reject(Request $request, Service $service): JsonResponse
    {
        // Only senior DPF or admin can reject
        if (!in_array($request->user()->role, [User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!$service->canBeApproved()) {
            return response()->json([
                'message' => 'Service cannot be rejected in current status',
            ], 422);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:10',
        ]);

        $service->reject($request->user()->id, $validated['rejection_reason']);

        // Notify the submitter
        if ($service->submittedByUser) {
            app(ExpoNotificationService::class)->sendToUser(
                $service->submittedByUser,
                'Service Rejected',
                "Service request rejected: " . substr($validated['rejection_reason'], 0, 50),
                ['type' => 'service', 'id' => $service->id]
            );
        }

        return response()->json([
            'message' => 'Service rejected',
            'data' => $service->fresh()->load(['vehicle', 'site', 'approvedByUser']),
        ]);
    }

    /**
     * Update vehicle service tracking after approval.
     */
    private function updateVehicleServiceTracking(Service $service): void
    {
        $vehicle = $service->vehicle;

        // Update last service info on vehicle
        $updateData = [];

        if ($service->service_type === 'major') {
            $updateData['last_major_service_at'] = $service->service_date;
            $updateData['last_major_service_reading'] = $service->reading_at_service;
            // Major service resets minor service too
            $updateData['last_minor_service_at'] = $service->service_date;
            $updateData['last_minor_service_reading'] = $service->reading_at_service;
        } else {
            $updateData['last_minor_service_at'] = $service->service_date;
            $updateData['last_minor_service_reading'] = $service->reading_at_service;
        }

        $vehicle->update($updateData);
    }
}
