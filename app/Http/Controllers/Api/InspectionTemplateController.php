<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InspectionTemplate;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InspectionTemplateController extends Controller
{
    /**
     * Display a listing of inspection templates.
     */
    public function index(Request $request): JsonResponse
    {
        $query = InspectionTemplate::query();

        if ($request->filled('frequency')) {
            $query->where('frequency', $request->frequency);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        $query->withCount('checklistItems');

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 50);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created inspection template.
     */
    public function store(Request $request): JsonResponse
    {
        // Only administrators can create inspection templates
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:inspection_templates,name',
            'description' => 'nullable|string',
            'frequency' => ['required', Rule::in(['monthly', 'quarterly', 'custom'])],
            'is_active' => 'boolean',
        ]);

        $template = InspectionTemplate::create($validated);

        return response()->json([
            'message' => 'Inspection template created successfully',
            'data' => $template,
        ], 201);
    }

    /**
     * Display the specified inspection template.
     */
    public function show(InspectionTemplate $inspectionTemplate): JsonResponse
    {
        $inspectionTemplate->load(['checklistItems.category']);
        $inspectionTemplate->loadCount('checklistItems');

        return response()->json([
            'data' => $inspectionTemplate,
        ]);
    }

    /**
     * Update the specified inspection template.
     */
    public function update(Request $request, InspectionTemplate $inspectionTemplate): JsonResponse
    {
        // Only administrators can update inspection templates
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('inspection_templates')->ignore($inspectionTemplate->id)],
            'description' => 'nullable|string',
            'frequency' => ['sometimes', 'required', Rule::in(['monthly', 'quarterly', 'custom'])],
            'is_active' => 'boolean',
        ]);

        $inspectionTemplate->update($validated);

        return response()->json([
            'message' => 'Inspection template updated successfully',
            'data' => $inspectionTemplate->fresh(),
        ]);
    }

    /**
     * Remove the specified inspection template (soft delete).
     */
    public function destroy(Request $request, InspectionTemplate $inspectionTemplate): JsonResponse
    {
        // Only administrators can delete inspection templates
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if template is in use
        if ($inspectionTemplate->inspections()->exists()) {
            return response()->json([
                'message' => 'Cannot delete template that has been used for inspections',
            ], 422);
        }

        $inspectionTemplate->delete();

        return response()->json([
            'message' => 'Inspection template deleted successfully',
        ]);
    }

    /**
     * Get checklist items for an inspection template.
     */
    public function items(InspectionTemplate $inspectionTemplate): JsonResponse
    {
        $items = $inspectionTemplate->checklistItems()
            ->with('category')
            ->withPivot('is_required')
            ->orderBy('display_order')
            ->get();

        return response()->json([
            'data' => $items,
        ]);
    }

    /**
     * Sync checklist items for an inspection template.
     */
    public function syncItems(Request $request, InspectionTemplate $inspectionTemplate): JsonResponse
    {
        // Only administrators can manage template items
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.checklist_item_id' => 'required|exists:checklist_items,id',
            'items.*.is_required' => 'boolean',
        ]);

        // Transform to sync format with pivot data
        $syncData = collect($validated['items'])->mapWithKeys(function ($item) {
            return [$item['checklist_item_id'] => ['is_required' => $item['is_required'] ?? true]];
        })->toArray();

        $inspectionTemplate->checklistItems()->sync($syncData);

        return response()->json([
            'message' => 'Template items updated successfully',
            'data' => $inspectionTemplate->fresh()->load('checklistItems'),
        ]);
    }
}
