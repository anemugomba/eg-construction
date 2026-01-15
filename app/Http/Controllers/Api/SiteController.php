<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiteController extends Controller
{
    /**
     * Display a listing of sites.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Site::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('location', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $query->withCount(['vehicles', 'users']);

        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->get('per_page', 50);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a newly created site.
     */
    public function store(Request $request): JsonResponse
    {
        // Only administrators can create sites
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:sites,name',
            'location' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $site = Site::create($validated);

        return response()->json([
            'message' => 'Site created successfully',
            'data' => $site,
        ], 201);
    }

    /**
     * Display the specified site.
     */
    public function show(Site $site): JsonResponse
    {
        $site->loadCount(['vehicles', 'users']);

        return response()->json([
            'data' => $site,
        ]);
    }

    /**
     * Update the specified site.
     */
    public function update(Request $request, Site $site): JsonResponse
    {
        // Only administrators can update sites
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('sites')->ignore($site->id)],
            'location' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $site->update($validated);

        return response()->json([
            'message' => 'Site updated successfully',
            'data' => $site->fresh(),
        ]);
    }

    /**
     * Remove the specified site (soft delete).
     */
    public function destroy(Request $request, Site $site): JsonResponse
    {
        // Only administrators can delete sites
        if ($request->user()->role !== User::ROLE_ADMINISTRATOR) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if site has active vehicles
        if ($site->vehicles()->exists()) {
            return response()->json([
                'message' => 'Cannot delete site with assigned vehicles. Reassign vehicles first.',
            ], 422);
        }

        $site->delete();

        return response()->json([
            'message' => 'Site deleted successfully',
        ]);
    }

    /**
     * Get machines/vehicles at a specific site.
     */
    public function machines(Request $request, Site $site): JsonResponse
    {
        $query = Vehicle::where('primary_site_id', $site->id)
            ->with(['vehicleType', 'machineType']);

        if ($request->filled('is_yellow_machine')) {
            $query->where('is_yellow_machine', $request->boolean('is_yellow_machine'));
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('registration_number', 'like', "%{$request->search}%")
                  ->orWhere('reference_name', 'like', "%{$request->search}%");
            });
        }

        $perPage = $request->get('per_page', 50);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Get staff/users assigned to a specific site.
     */
    public function staff(Request $request, Site $site): JsonResponse
    {
        $query = $site->users();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $perPage = $request->get('per_page', 50);

        return response()->json($query->paginate($perPage));
    }
}
