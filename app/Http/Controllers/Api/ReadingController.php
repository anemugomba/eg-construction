<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reading;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ReadingController extends Controller
{
    /**
     * Display readings for a vehicle.
     */
    public function index(Request $request, Vehicle $vehicle): JsonResponse
    {
        $query = $vehicle->readings()->with('recordedByUser');

        if ($request->filled('reading_type')) {
            $query->where('reading_type', $request->reading_type);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('recorded_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('recorded_at', '<=', $request->to_date);
        }

        $query->orderBy('recorded_at', 'desc');

        $perPage = $request->get('per_page', 50);

        return response()->json($query->paginate($perPage));
    }

    /**
     * Store a new reading for a vehicle.
     */
    public function store(Request $request, Vehicle $vehicle): JsonResponse
    {
        // Data entry, site DPF, senior DPF, or admin can record readings
        $allowedRoles = [User::ROLE_DATA_ENTRY, User::ROLE_SITE_DPF, User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR];
        if (!in_array($request->user()->role, $allowedRoles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Determine expected reading type based on vehicle/machine type
        $expectedType = $vehicle->is_yellow_machine && $vehicle->machineType
            ? $vehicle->machineType->tracking_unit
            : 'kilometers';

        $validated = $request->validate([
            'reading_value' => 'required|integer|min:0',
            'reading_type' => ['sometimes', Rule::in(['hours', 'kilometers'])],
            'source' => ['sometimes', Rule::in(['manual', 'telematics', 'import', 'adjustment'])],
            'is_anomaly_override' => 'boolean',
            'anomaly_reason' => 'required_if:is_anomaly_override,true|nullable|string',
            'recorded_at' => 'sometimes|date',
        ]);

        $validated['reading_type'] = $validated['reading_type'] ?? $expectedType;
        $validated['source'] = $validated['source'] ?? 'manual';
        $validated['recorded_at'] = $validated['recorded_at'] ?? now();
        $validated['recorded_by'] = $request->user()->id;
        $validated['vehicle_id'] = $vehicle->id;

        // Validate reading doesn't go backwards (unless anomaly override)
        $lastReading = $vehicle->readings()
            ->where('reading_type', $validated['reading_type'])
            ->orderBy('recorded_at', 'desc')
            ->first();

        if ($lastReading && $validated['reading_value'] < $lastReading->reading_value) {
            if (!($validated['is_anomaly_override'] ?? false)) {
                return response()->json([
                    'message' => 'Reading value cannot be less than previous reading. Use anomaly override if this is correct.',
                    'last_reading' => $lastReading->reading_value,
                    'last_reading_date' => $lastReading->recorded_at,
                ], 422);
            }

            // Only senior DPF or admin can override
            if (!in_array($request->user()->role, [User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR])) {
                return response()->json([
                    'message' => 'Only Senior DPF or Administrator can override anomalous readings',
                ], 403);
            }
        }

        $reading = Reading::create($validated);

        // Update vehicle's current reading
        $this->updateVehicleReading($vehicle, $validated['reading_type'], $validated['reading_value']);

        return response()->json([
            'message' => 'Reading recorded successfully',
            'data' => $reading->load('recordedByUser'),
        ], 201);
    }

    /**
     * Bulk store readings for multiple vehicles.
     */
    public function bulkStore(Request $request): JsonResponse
    {
        // Data entry, site DPF, senior DPF, or admin can record readings
        $allowedRoles = [User::ROLE_DATA_ENTRY, User::ROLE_SITE_DPF, User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR];
        if (!in_array($request->user()->role, $allowedRoles)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'readings' => 'required|array|min:1',
            'readings.*.vehicle_id' => 'required|exists:vehicles,id',
            'readings.*.reading_value' => 'required|integer|min:0',
            'readings.*.reading_type' => ['sometimes', Rule::in(['hours', 'kilometers'])],
            'readings.*.source' => ['sometimes', Rule::in(['manual', 'telematics', 'import', 'adjustment'])],
            'readings.*.recorded_at' => 'sometimes|date',
        ]);

        $results = [
            'success' => [],
            'failed' => [],
        ];

        DB::beginTransaction();
        try {
            foreach ($validated['readings'] as $index => $readingData) {
                $vehicle = Vehicle::find($readingData['vehicle_id']);

                // Determine expected reading type
                $expectedType = $vehicle->is_yellow_machine && $vehicle->machineType
                    ? $vehicle->machineType->tracking_unit
                    : 'kilometers';

                $readingData['reading_type'] = $readingData['reading_type'] ?? $expectedType;
                $readingData['source'] = $readingData['source'] ?? 'manual';
                $readingData['recorded_at'] = $readingData['recorded_at'] ?? now();
                $readingData['recorded_by'] = $request->user()->id;

                // Check for backwards reading
                $lastReading = $vehicle->readings()
                    ->where('reading_type', $readingData['reading_type'])
                    ->orderBy('recorded_at', 'desc')
                    ->first();

                if ($lastReading && $readingData['reading_value'] < $lastReading->reading_value) {
                    $results['failed'][] = [
                        'index' => $index,
                        'vehicle_id' => $vehicle->id,
                        'error' => 'Reading value less than previous reading',
                        'last_reading' => $lastReading->reading_value,
                    ];
                    continue;
                }

                $reading = Reading::create($readingData);
                $this->updateVehicleReading($vehicle, $readingData['reading_type'], $readingData['reading_value']);

                $results['success'][] = [
                    'index' => $index,
                    'vehicle_id' => $vehicle->id,
                    'reading_id' => $reading->id,
                ];
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Bulk operation failed',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'message' => 'Bulk readings processed',
            'results' => $results,
            'success_count' => count($results['success']),
            'failed_count' => count($results['failed']),
        ], count($results['failed']) > 0 ? 207 : 201);
    }

    /**
     * Update vehicle's current reading.
     */
    private function updateVehicleReading(Vehicle $vehicle, string $type, int $value): void
    {
        $updateData = ['last_reading_at' => now()];

        if ($type === 'hours') {
            $updateData['current_hours'] = $value;
        } else {
            $updateData['current_km'] = $value;
        }

        $vehicle->update($updateData);
    }
}
