<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InspectionTemplate;
use App\Models\MachineType;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MachineTypeController extends Controller
{
    /**
     * Display a listing of machine types.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MachineType::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        if ($request->filled('tracking_unit')) {
            $query->where('tracking_unit', $request->tracking_unit);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->withCount('vehicles');

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 50);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created machine type.
     */
    public function store(Request $request): JsonResponse
    {
        // Only administrators can create machine types
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:machine_types,name',
            'tracking_unit' => ['required', Rule::in(['hours', 'kilometers'])],
            'minor_service_interval' => 'required|integer|min:1',
            'major_service_interval' => 'required|integer|min:1|gt:minor_service_interval',
            'warning_threshold' => 'required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $machineType = MachineType::create($validated);

        return response()->json([
            'message' => 'Machine type created successfully',
            'data' => $machineType,
        ], 201);
    }

    /**
     * Display the specified machine type.
     */
    public function show(MachineType $machineType): JsonResponse
    {
        $machineType->loadCount('vehicles');
        $machineType->load('checklistItems');

        return response()->json([
            'data' => $machineType,
        ]);
    }

    /**
     * Update the specified machine type.
     */
    public function update(Request $request, MachineType $machineType): JsonResponse
    {
        // Only administrators can update machine types
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('machine_types')->ignore($machineType->id)],
            'tracking_unit' => ['sometimes', 'required', Rule::in(['hours', 'kilometers'])],
            'minor_service_interval' => 'sometimes|required|integer|min:1',
            'major_service_interval' => 'sometimes|required|integer|min:1',
            'warning_threshold' => 'sometimes|required|integer|min:1',
            'is_active' => 'boolean',
        ]);

        // Validate major > minor if both provided
        $minor = $validated['minor_service_interval'] ?? $machineType->minor_service_interval;
        $major = $validated['major_service_interval'] ?? $machineType->major_service_interval;
        if ($major <= $minor) {
            return response()->json([
                'message' => 'Major service interval must be greater than minor service interval',
            ], 422);
        }

        $machineType->update($validated);

        return response()->json([
            'message' => 'Machine type updated successfully',
            'data' => $machineType->fresh(),
        ]);
    }

    /**
     * Remove the specified machine type (soft delete).
     */
    public function destroy(Request $request, MachineType $machineType): JsonResponse
    {
        // Only administrators can delete machine types
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if machine type is in use
        if ($machineType->vehicles()->exists()) {
            return response()->json([
                'message' => 'Cannot delete machine type that is assigned to vehicles',
            ], 422);
        }

        $machineType->delete();

        return response()->json([
            'message' => 'Machine type deleted successfully',
        ]);
    }

    /**
     * Get checklist items for a machine type.
     */
    public function checklistItems(MachineType $machineType): JsonResponse
    {
        $items = $machineType->checklistItems()
            ->with('category')
            ->orderBy('display_order')
            ->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * Sync checklist items for a machine type.
     */
    public function syncChecklistItems(Request $request, MachineType $machineType): JsonResponse
    {
        // Only administrators can manage checklist items
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'checklist_item_ids' => 'required|array',
            'checklist_item_ids.*' => 'exists:checklist_items,id',
        ]);

        $machineType->checklistItems()->sync($validated['checklist_item_ids']);

        return response()->json([
            'message' => 'Checklist items updated successfully',
            'data' => $machineType->fresh()->load('checklistItems'),
        ]);
    }

    /**
     * Get inspection templates (returns all active templates for now).
     * Note: Templates are not yet linked to machine types in the database.
     */
    public function inspectionTemplates(MachineType $machineType): JsonResponse
    {
        // Since there's no machine_type_id on inspection_templates yet,
        // return all active templates for any machine type
        $templates = InspectionTemplate::where('is_active', true)
            ->withCount('checklistItems')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $templates,
        ]);
    }
}
