<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartsCatalog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartsCatalogController extends Controller
{
    /**
     * Display a listing of parts catalog.
     */
    public function index(Request $request): JsonResponse
    {
        $query = PartsCatalog::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%")
                  ->orWhere('category', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('supplier')) {
            $query->where('supplier', $request->supplier);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created part in the catalog.
     */
    public function store(Request $request): JsonResponse
    {
        // Only administrators can manage parts catalog
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'sku' => 'nullable|string|max:50|unique:parts_catalog,sku',
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'unit_cost' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $part = PartsCatalog::create($validated);

        return response()->json([
            'message' => 'Part created successfully',
            'data' => $part,
        ], 201);
    }

    /**
     * Display the specified part.
     */
    public function show(PartsCatalog $partsCatalog): JsonResponse
    {
        return response()->json([
            'data' => $partsCatalog,
        ]);
    }

    /**
     * Update the specified part in the catalog.
     */
    public function update(Request $request, PartsCatalog $partsCatalog): JsonResponse
    {
        // Only administrators can manage parts catalog
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('parts_catalog')->ignore($partsCatalog->id)],
            'name' => 'sometimes|required|string|max:255',
            'category' => 'nullable|string|max:100',
            'unit_cost' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $partsCatalog->update($validated);

        return response()->json([
            'message' => 'Part updated successfully',
            'data' => $partsCatalog->fresh(),
        ]);
    }

    /**
     * Remove the specified part from the catalog.
     */
    public function destroy(Request $request, PartsCatalog $partsCatalog): JsonResponse
    {
        // Only administrators can manage parts catalog
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $partsCatalog->delete();

        return response()->json([
            'message' => 'Part deleted successfully',
        ]);
    }
}
