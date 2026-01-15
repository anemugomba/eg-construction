<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\Service;
use App\Models\Site;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Fleet status report - overview of all vehicles/machines.
     */
    public function fleetStatus(Request $request): JsonResponse
    {
        $query = Vehicle::with(['vehicleType', 'machineType', 'primarySite']);

        // Filter by site
        if ($request->filled('site_id')) {
            $query->where('primary_site_id', $request->site_id);
        }

        // Filter by yellow machines only
        if ($request->boolean('yellow_machines_only')) {
            $query->where('is_yellow_machine', true);
        }

        $vehicles = $query->get()->map(function ($vehicle) {
            $serviceStatus = $this->calculateServiceStatus($vehicle);

            return [
                'id' => $vehicle->id,
                'registration_number' => $vehicle->registration_number,
                'reference_name' => $vehicle->reference_name,
                'type' => $vehicle->is_yellow_machine
                    ? ($vehicle->machineType->name ?? 'Yellow Machine')
                    : ($vehicle->vehicleType->name ?? 'Vehicle'),
                'site' => $vehicle->primarySite->name ?? 'Unassigned',
                'current_reading' => $vehicle->is_yellow_machine
                    ? $vehicle->current_hours
                    : $vehicle->current_km,
                'reading_type' => $vehicle->is_yellow_machine ? 'hours' : 'km',
                'last_reading_at' => $vehicle->last_reading_at,
                'service_status' => $serviceStatus,
            ];
        });

        // Group by status
        $summary = [
            'total' => $vehicles->count(),
            'ok' => $vehicles->where('service_status.status', 'ok')->count(),
            'due_soon' => $vehicles->where('service_status.status', 'due_soon')->count(),
            'overdue' => $vehicles->where('service_status.status', 'overdue')->count(),
        ];

        return response()->json([
            'summary' => $summary,
            'data' => $vehicles,
        ]);
    }

    /**
     * Service history report.
     */
    public function serviceHistory(Request $request): JsonResponse
    {
        $query = Service::with(['vehicle', 'site', 'submittedByUser', 'approvedByUser'])
            ->where('status', 'approved');

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('service_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('service_date', '<=', $request->to_date);
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        $query->orderBy('service_date', 'desc');

        $services = $query->get();

        $summary = [
            'total_services' => $services->count(),
            'minor_services' => $services->where('service_type', 'minor')->count(),
            'major_services' => $services->where('service_type', 'major')->count(),
            'total_parts_cost' => $services->sum('total_parts_cost'),
        ];

        return response()->json([
            'summary' => $summary,
            'data' => $services,
        ]);
    }

    /**
     * Job card history report.
     */
    public function jobCardHistory(Request $request): JsonResponse
    {
        $query = JobCard::with(['vehicle', 'site', 'components', 'submittedByUser', 'approvedByUser'])
            ->where('status', 'approved');

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('job_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('job_date', '<=', $request->to_date);
        }

        if ($request->filled('job_type')) {
            $query->where('job_type', $request->job_type);
        }

        $query->orderBy('job_date', 'desc');

        $jobCards = $query->get();

        $summary = [
            'total_job_cards' => $jobCards->count(),
            'by_type' => $jobCards->groupBy('job_type')->map->count(),
            'total_parts_cost' => $jobCards->sum('total_parts_cost'),
        ];

        return response()->json([
            'summary' => $summary,
            'data' => $jobCards,
        ]);
    }

    /**
     * Component lifespan report.
     */
    public function componentLifespan(Request $request): JsonResponse
    {
        $query = DB::table('job_card_components')
            ->join('job_cards', 'job_card_components.job_card_id', '=', 'job_cards.id')
            ->join('vehicles', 'job_cards.vehicle_id', '=', 'vehicles.id')
            ->leftJoin('components', 'job_card_components.component_id', '=', 'components.id')
            ->where('job_cards.status', 'approved')
            ->where('job_card_components.action_taken', 'replaced')
            ->select(
                'job_card_components.component_description',
                'components.name as component_name',
                'vehicles.reference_name as vehicle_name',
                'vehicles.registration_number',
                'job_card_components.reading_at_action',
                'job_cards.job_date'
            );

        if ($request->filled('vehicle_id')) {
            $query->where('job_cards.vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('component_id')) {
            $query->where('job_card_components.component_id', $request->component_id);
        }

        $query->orderBy('job_cards.job_date', 'desc');

        $replacements = $query->get();

        return response()->json([
            'data' => $replacements,
            'total_replacements' => $replacements->count(),
        ]);
    }

    /**
     * Site performance report.
     */
    public function sitePerformance(Request $request): JsonResponse
    {
        $sites = Site::withCount('vehicles')->get();

        $siteStats = $sites->map(function ($site) use ($request) {
            $serviceQuery = Service::where('site_id', $site->id)->where('status', 'approved');
            $jobCardQuery = JobCard::where('site_id', $site->id)->where('status', 'approved');

            if ($request->filled('from_date')) {
                $serviceQuery->whereDate('service_date', '>=', $request->from_date);
                $jobCardQuery->whereDate('job_date', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $serviceQuery->whereDate('service_date', '<=', $request->to_date);
                $jobCardQuery->whereDate('job_date', '<=', $request->to_date);
            }

            return [
                'site_id' => $site->id,
                'site_name' => $site->name,
                'location' => $site->location,
                'vehicle_count' => $site->vehicles_count,
                'services_completed' => $serviceQuery->count(),
                'job_cards_completed' => $jobCardQuery->count(),
                'total_service_cost' => $serviceQuery->sum('total_parts_cost'),
                'total_job_card_cost' => $jobCardQuery->sum('total_parts_cost'),
            ];
        });

        return response()->json([
            'data' => $siteStats,
        ]);
    }

    /**
     * Cost analysis report.
     */
    public function costAnalysis(Request $request): JsonResponse
    {
        $fromDate = $request->get('from_date', now()->startOfYear()->toDateString());
        $toDate = $request->get('to_date', now()->toDateString());

        // Service costs
        $serviceCosts = Service::where('status', 'approved')
            ->whereBetween('service_date', [$fromDate, $toDate])
            ->selectRaw('
                SUM(total_parts_cost) as total_cost,
                COUNT(*) as count,
                service_type
            ')
            ->groupBy('service_type')
            ->get();

        // Job card costs
        $jobCardCosts = JobCard::where('status', 'approved')
            ->whereBetween('job_date', [$fromDate, $toDate])
            ->selectRaw('
                SUM(total_parts_cost) as total_cost,
                COUNT(*) as count,
                job_type
            ')
            ->groupBy('job_type')
            ->get();

        // Monthly breakdown
        $monthlyServices = Service::where('status', 'approved')
            ->whereBetween('service_date', [$fromDate, $toDate])
            ->selectRaw('
                DATE_FORMAT(service_date, "%Y-%m") as month,
                SUM(total_parts_cost) as cost,
                COUNT(*) as count
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $monthlyJobCards = JobCard::where('status', 'approved')
            ->whereBetween('job_date', [$fromDate, $toDate])
            ->selectRaw('
                DATE_FORMAT(job_date, "%Y-%m") as month,
                SUM(total_parts_cost) as cost,
                COUNT(*) as count
            ')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'period' => [
                'from' => $fromDate,
                'to' => $toDate,
            ],
            'services' => [
                'by_type' => $serviceCosts,
                'total_cost' => $serviceCosts->sum('total_cost'),
                'total_count' => $serviceCosts->sum('count'),
            ],
            'job_cards' => [
                'by_type' => $jobCardCosts,
                'total_cost' => $jobCardCosts->sum('total_cost'),
                'total_count' => $jobCardCosts->sum('count'),
            ],
            'monthly' => [
                'services' => $monthlyServices,
                'job_cards' => $monthlyJobCards,
            ],
            'grand_total' => $serviceCosts->sum('total_cost') + $jobCardCosts->sum('total_cost'),
        ]);
    }

    /**
     * Calculate service status for a vehicle.
     */
    private function calculateServiceStatus(Vehicle $vehicle): array
    {
        if (!$vehicle->is_yellow_machine || !$vehicle->machineType) {
            return ['status' => 'unknown', 'message' => 'Not a yellow machine or no machine type'];
        }

        $machineType = $vehicle->machineType;
        $trackingUnit = $machineType->tracking_unit;
        $currentReading = $trackingUnit === 'hours' ? $vehicle->current_hours : $vehicle->current_km;
        $lastMinorReading = $vehicle->last_minor_service_reading ?? 0;
        $lastMajorReading = $vehicle->last_major_service_reading ?? 0;

        if (!$currentReading) {
            return ['status' => 'unknown', 'message' => 'No reading recorded'];
        }

        $sinceLastMinor = $currentReading - $lastMinorReading;
        $sinceLastMajor = $currentReading - $lastMajorReading;

        $minorInterval = $machineType->minor_service_interval;
        $majorInterval = $machineType->major_service_interval;
        $warningThreshold = $machineType->warning_threshold;

        // Check major service first
        if ($sinceLastMajor >= $majorInterval) {
            return [
                'status' => 'overdue',
                'type' => 'major',
                'overdue_by' => $sinceLastMajor - $majorInterval,
            ];
        }

        if ($sinceLastMajor >= ($majorInterval - $warningThreshold)) {
            return [
                'status' => 'due_soon',
                'type' => 'major',
                'remaining' => $majorInterval - $sinceLastMajor,
            ];
        }

        // Check minor service
        if ($sinceLastMinor >= $minorInterval) {
            return [
                'status' => 'overdue',
                'type' => 'minor',
                'overdue_by' => $sinceLastMinor - $minorInterval,
            ];
        }

        if ($sinceLastMinor >= ($minorInterval - $warningThreshold)) {
            return [
                'status' => 'due_soon',
                'type' => 'minor',
                'remaining' => $minorInterval - $sinceLastMinor,
            ];
        }

        return [
            'status' => 'ok',
            'next_minor_in' => $minorInterval - $sinceLastMinor,
            'next_major_in' => $majorInterval - $sinceLastMajor,
        ];
    }
}
