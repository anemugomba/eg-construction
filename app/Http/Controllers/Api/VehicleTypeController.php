<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use Illuminate\Http\JsonResponse;

class VehicleTypeController extends Controller
{
    public function index(): JsonResponse
    {
        $types = VehicleType::orderBy('name')->get();

        return response()->json([
            'data' => $types,
        ]);
    }
}
