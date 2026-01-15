<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Component;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComponentController extends Controller
{
    /**
     * Display a listing of components.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Component::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('category', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('is_system_defined')) {
            $query->where('is_system_defined', $request->boolean('is_system_defined'));
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created component (custom).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'category' => 'nullable|string|max:50',
        ]);

        // Custom components are not system-defined
        $validated['is_system_defined'] = false;

        $component = Component::create($validated);

        return response()->json([
            'message' => 'Component created successfully',
            'data' => $component,
        ], 201);
    }

    /**
     * Display the specified component.
     */
    public function show(Component $component): JsonResponse
    {
        return response()->json([
            'data' => $component,
        ]);
    }

    /**
     * Update the specified component.
     */
    public function update(Request $request, Component $component): JsonResponse
    {
        // Cannot update system-defined components
        if ($component->is_system_defined) {
            return response()->json([
                'message' => 'Cannot modify system-defined components',
            ], 422);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'category' => 'nullable|string|max:50',
        ]);

        $component->update($validated);

        return response()->json([
            'message' => 'Component updated successfully',
            'data' => $component->fresh(),
        ]);
    }

    /**
     * Remove the specified component.
     */
    public function destroy(Request $request, Component $component): JsonResponse
    {
        // Cannot delete system-defined components
        if ($component->is_system_defined) {
            return response()->json([
                'message' => 'Cannot delete system-defined components',
            ], 422);
        }

        // Check if component is in use
        if ($component->jobCardComponents()->exists() || $component->watchListItems()->exists()) {
            return response()->json([
                'message' => 'Cannot delete component that is in use',
            ], 422);
        }

        $component->delete();

        return response()->json([
            'message' => 'Component deleted successfully',
        ]);
    }
}
