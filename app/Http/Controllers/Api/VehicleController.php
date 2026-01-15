<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IntervalOverride;
use App\Models\SiteAssignment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Vehicle::with(['vehicleType', 'machineType', 'primarySite']);

        if ($request->filled('status')) {
            $query->status($request->status);
        }

        if ($request->filled('vehicle_type_id')) {
            $query->where('vehicle_type_id', $request->vehicle_type_id);
        }

        if ($request->filled('machine_type_id')) {
            $query->where('machine_type_id', $request->machine_type_id);
        }

        if ($request->filled('site_id')) {
            $query->where('primary_site_id', $request->site_id);
        }

        if ($request->filled('is_yellow_machine')) {
            $query->where('is_yellow_machine', $request->boolean('is_yellow_machine'));
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $sortBy = $request->get('sort_by', 'reference_name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 50);
        $vehicles = $query->paginate($perPage);

        return response()->json($vehicles);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reference_name' => 'required|string|max:100|unique:vehicles,reference_name',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'registration_number' => 'nullable|string|max:20',
            'chassis_number' => 'nullable|string|max:50',
            'engine_number' => 'nullable|string|max:50',
            'make' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:50',
            'year_of_manufacture' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'status' => ['required', Rule::in(['active', 'disposed', 'sold'])],
            // Fleet fields
            'is_yellow_machine' => 'boolean',
            'machine_type_id' => 'nullable|exists:machine_types,id',
            'primary_site_id' => 'nullable|exists:sites,id',
            'current_hours' => 'nullable|integer|min:0',
            'current_km' => 'nullable|integer|min:0',
        ]);

        $vehicle = Vehicle::create($validated);
        $vehicle->load(['vehicleType', 'machineType', 'primarySite']);

        // If primary site assigned, create initial site assignment
        if (!empty($validated['primary_site_id'])) {
            SiteAssignment::create([
                'vehicle_id' => $vehicle->id,
                'site_id' => $validated['primary_site_id'],
                'assigned_at' => now()->toDateString(),
                'assigned_by' => $request->user()->id,
                'notes' => 'Initial site assignment',
            ]);
        }

        return response()->json([
            'message' => 'Vehicle created successfully',
            'data' => $vehicle,
        ], 201);
    }

    public function show(Vehicle $vehicle): JsonResponse
    {
        $vehicle->load(['vehicleType', 'machineType', 'primarySite']);

        return response()->json([
            'data' => $vehicle,
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $validated = $request->validate([
            'reference_name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('vehicles', 'reference_name')->ignore($vehicle->id),
            ],
            'vehicle_type_id' => 'sometimes|required|exists:vehicle_types,id',
            'registration_number' => 'nullable|string|max:20',
            'chassis_number' => 'nullable|string|max:50',
            'engine_number' => 'nullable|string|max:50',
            'make' => 'nullable|string|max:50',
            'model' => 'nullable|string|max:50',
            'year_of_manufacture' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'status' => ['sometimes', Rule::in(['active', 'disposed', 'sold'])],
            // Fleet fields
            'is_yellow_machine' => 'boolean',
            'machine_type_id' => 'nullable|exists:machine_types,id',
        ]);

        $vehicle->update($validated);
        $vehicle->load(['vehicleType', 'machineType', 'primarySite']);

        return response()->json([
            'message' => 'Vehicle updated successfully',
            'data' => $vehicle,
        ]);
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $vehicle->delete();

        return response()->json([
            'message' => 'Vehicle deleted successfully',
        ]);
    }

    /**
     * Get site assignment history for a vehicle.
     */
    public function siteAssignments(Vehicle $vehicle): JsonResponse
    {
        $assignments = $vehicle->siteAssignments()
            ->with(['site', 'assignedByUser'])
            ->orderBy('assigned_at', 'desc')
            ->get();

        return response()->json([
            'data' => $assignments,
        ]);
    }

    /**
     * Assign vehicle to a new site.
     */
    public function assignToSite(Request $request, Vehicle $vehicle): JsonResponse
    {
        // Site DPF, senior DPF, or admin can assign vehicles
        $allowedRoles = [User::ROLE_SITE_DPF, User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR];
        if (!in_array($request->user()->role, $allowedRoles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'notes' => 'nullable|string',
        ]);

        // End current assignment if exists
        $currentAssignment = $vehicle->siteAssignments()
            ->whereNull('ended_at')
            ->first();

        if ($currentAssignment) {
            $currentAssignment->update(['ended_at' => now()->toDateString()]);
        }

        // Create new assignment
        $assignment = SiteAssignment::create([
            'vehicle_id' => $vehicle->id,
            'site_id' => $validated['site_id'],
            'assigned_at' => now()->toDateString(),
            'assigned_by' => $request->user()->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Update vehicle's primary site
        $vehicle->update(['primary_site_id' => $validated['site_id']]);

        return response()->json([
            'message' => 'Vehicle assigned to site',
            'data' => $assignment->load('site'),
        ], 201);
    }

    /**
     * Get service status for a vehicle.
     */
    public function serviceStatus(Vehicle $vehicle): JsonResponse
    {
        if (!$vehicle->is_yellow_machine || !$vehicle->machineType) {
            return response()->json([
                'status' => 'not_applicable',
                'message' => 'Vehicle is not a yellow machine',
            ]);
        }

        $machineType = $vehicle->machineType;
        $trackingUnit = $machineType->tracking_unit;
        $currentReading = $trackingUnit === 'hours' ? $vehicle->current_hours : $vehicle->current_km;
        $lastMinorReading = $vehicle->last_minor_service_reading ?? 0;
        $lastMajorReading = $vehicle->last_major_service_reading ?? 0;

        if (!$currentReading) {
            return response()->json([
                'status' => 'unknown',
                'message' => 'No reading recorded',
            ]);
        }

        $sinceLastMinor = $currentReading - $lastMinorReading;
        $sinceLastMajor = $currentReading - $lastMajorReading;

        $minorInterval = $machineType->minor_service_interval;
        $majorInterval = $machineType->major_service_interval;
        $warningThreshold = $machineType->warning_threshold;

        return response()->json([
            'current_reading' => $currentReading,
            'tracking_unit' => $trackingUnit,
            'minor_service' => [
                'interval' => $minorInterval,
                'last_at' => $lastMinorReading,
                'since_last' => $sinceLastMinor,
                'remaining' => max(0, $minorInterval - $sinceLastMinor),
                'status' => $sinceLastMinor >= $minorInterval ? 'overdue'
                    : ($sinceLastMinor >= ($minorInterval - $warningThreshold) ? 'due_soon' : 'ok'),
            ],
            'major_service' => [
                'interval' => $majorInterval,
                'last_at' => $lastMajorReading,
                'since_last' => $sinceLastMajor,
                'remaining' => max(0, $majorInterval - $sinceLastMajor),
                'status' => $sinceLastMajor >= $majorInterval ? 'overdue'
                    : ($sinceLastMajor >= ($majorInterval - $warningThreshold) ? 'due_soon' : 'ok'),
            ],
        ]);
    }

    /**
     * Get interval overrides for a vehicle.
     */
    public function intervalOverrides(Vehicle $vehicle): JsonResponse
    {
        $overrides = IntervalOverride::where('vehicle_id', $vehicle->id)
            ->with('changedByUser')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $overrides,
        ]);
    }

    /**
     * Create an interval override for a vehicle.
     */
    public function createIntervalOverride(Request $request, Vehicle $vehicle): JsonResponse
    {
        // Only senior DPF or admin can create overrides
        if (!in_array($request->user()->role, [User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'override_type' => ['required', Rule::in(['minor_interval', 'major_interval', 'warning_threshold'])],
            'new_value' => 'required|integer|min:1',
            'reason' => 'required|string|min:10',
        ]);

        // Get previous value based on override type
        $previousValue = null;
        if ($vehicle->machineType) {
            switch ($validated['override_type']) {
                case 'minor_interval':
                    $previousValue = $vehicle->warning_threshold_hours ?? $vehicle->machineType->minor_service_interval;
                    break;
                case 'major_interval':
                    $previousValue = $vehicle->machineType->major_service_interval;
                    break;
                case 'warning_threshold':
                    $previousValue = $vehicle->machineType->warning_threshold;
                    break;
            }
        }

        $override = IntervalOverride::create([
            'vehicle_id' => $vehicle->id,
            'override_type' => $validated['override_type'],
            'previous_value' => $previousValue,
            'new_value' => $validated['new_value'],
            'reason' => $validated['reason'],
            'changed_by' => $request->user()->id,
        ]);

        // Apply override to vehicle if applicable
        if ($validated['override_type'] === 'warning_threshold') {
            $vehicle->update([
                'warning_threshold_' . ($vehicle->machineType?->tracking_unit === 'hours' ? 'hours' : 'km') => $validated['new_value'],
            ]);
        }

        return response()->json([
            'message' => 'Interval override created',
            'data' => $override->load('changedByUser'),
        ], 201);
    }

    /**
     * Get services for a vehicle.
     */
    public function services(Request $request, Vehicle $vehicle): JsonResponse
    {
        $query = $vehicle->services()
            ->with(['site', 'submittedByUser', 'approvedByUser']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $query->orderBy('service_date', 'desc');

        $perPage = $request->get('per_page', 50);

        return response()->json($query->paginate($perPage));
    }
}
