<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChecklistCategory;
use App\Models\ChecklistItem;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChecklistController extends Controller
{
    /**
     * Get all checklist categories.
     */
    public function categories(Request $request): JsonResponse
    {
        $query = ChecklistCategory::query()
            ->withCount('items')
            ->orderBy('display_order')
            ->orderBy('name');

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    /**
     * Get all checklist items.
     */
    public function items(Request $request): JsonResponse
    {
        $query = ChecklistItem::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('is_quarterly_only')) {
            $query->where('is_quarterly_only', $request->boolean('is_quarterly_only'));
        }

        if ($request->filled('machine_type_id')) {
            $query->whereHas('machineTypes', function ($q) use ($request) {
                $q->where('machine_types.id', $request->machine_type_id);
            });
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        $query->orderBy('display_order')->orderBy('name');

        $perPage = $request->get('per_page', 100);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a new checklist item.
     */
    public function storeItem(Request $request): JsonResponse
    {
        // Only administrators can create checklist items
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:checklist_categories,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_quarterly_only' => 'boolean',
            'photo_required_on_repair' => 'boolean',
            'photo_required_on_replace' => 'boolean',
            'display_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        $item = ChecklistItem::create($validated);

        return response()->json([
            'message' => 'Checklist item created successfully',
            'data' => $item->load('category'),
        ], 201);
    }
}
