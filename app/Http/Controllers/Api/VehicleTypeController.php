<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VehicleTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = VehicleType::withCount('vehicles')->orderBy('name')->get();

        return response()->json([
            'data' => $types,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:vehicle_types,name',
        ]);

        $type = VehicleType::create($validated);

        return response()->json([
            'message' => 'Vehicle type created successfully',
            'data' => $type,
        ], 201);
    }

    public function show(VehicleType $vehicleType): JsonResponse
    {
        $vehicleType->loadCount('vehicles');

        return response()->json([
            'data' => $vehicleType,
        ]);
    }

    public function update(Request $request, VehicleType $vehicleType): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vehicle_types', 'name')->ignore($vehicleType->id),
            ],
        ]);

        $vehicleType->update($validated);

        return response()->json([
            'message' => 'Vehicle type updated successfully',
            'data' => $vehicleType,
        ]);
    }

    public function destroy(VehicleType $vehicleType): JsonResponse
    {
        if ($vehicleType->vehicles()->exists()) {
            return response()->json([
                'message' => 'Cannot delete vehicle type that has vehicles assigned',
            ], 422);
        }

        $vehicleType->delete();

        return response()->json([
            'message' => 'Vehicle type deleted successfully',
        ]);
    }
}
