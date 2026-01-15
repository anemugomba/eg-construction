<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Inspection;
use App\Models\JobCard;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WatchListItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary(): JsonResponse
    {
        $today = Carbon::today();
        $in30Days = $today->copy()->addDays(30);
        $grace30Days = $today->copy()->subDays(30);

        // Get all active vehicles with their current tax periods and exemptions
        $vehicles = Vehicle::active()
            ->with(['currentTaxPeriod', 'currentExemption'])
            ->get();

        $totalVehicles = $vehicles->count();
        $expiringSoon = 0;
        $overdue = 0;
        $inPenalty = 0;
        $valid = 0;
        $exempted = 0;

        foreach ($vehicles as $vehicle) {
            $taxStatus = $vehicle->tax_status;

            switch ($taxStatus) {
                case 'exempted':
                    $exempted++;
                    break;
                case 'valid':
                    $valid++;
                    break;
                case 'expiring_soon':
                    $expiringSoon++;
                    break;
                case 'expired':
                    $overdue++;
                    break;
                case 'penalty':
                    $inPenalty++;
                    break;
            }
        }

        // Exempted vehicles count as compliant (they're legally off-road)
        $compliantCount = $valid + $exempted;
        $compliancePercentage = $totalVehicles > 0
            ? round(($compliantCount / $totalVehicles) * 100, 1)
            : 0;

        return response()->json([
            'total_vehicles' => $totalVehicles,
            'expiring_soon' => $expiringSoon,
            'overdue' => $overdue,
            'in_penalty' => $inPenalty,
            'valid' => $valid,
            'exempted' => $exempted,
            'compliance_percentage' => $compliancePercentage,
        ]);
    }

    public function alerts(): JsonResponse
    {
        $today = Carbon::today();
        $in7Days = $today->copy()->addDays(7);

        // Get vehicles that are:
        // 1. Expiring within 7 days
        // 2. Already expired (overdue)
        // 3. In penalty zone
        // Excludes exempted vehicles
        $vehicles = Vehicle::active()
            ->with(['currentTaxPeriod', 'vehicleType', 'currentExemption'])
            ->get()
            ->filter(function ($vehicle) use ($in7Days) {
                $taxStatus = $vehicle->tax_status;
                $daysRemaining = $vehicle->days_remaining;

                // Skip exempted vehicles
                if ($taxStatus === 'exempted') {
                    return false;
                }

                // No tax period at all
                if ($taxStatus === 'no_tax') {
                    return true;
                }

                // Already expired or in penalty
                if (in_array($taxStatus, ['expired', 'penalty'])) {
                    return true;
                }

                // Expiring within 7 days
                if ($taxStatus === 'expiring_soon' && $daysRemaining !== null && $daysRemaining <= 7) {
                    return true;
                }

                return false;
            })
            ->sortBy(function ($vehicle) {
                // Sort by urgency: penalty first, then expired, then expiring soon
                $order = [
                    'penalty' => 0,
                    'expired' => 1,
                    'no_tax' => 2,
                    'expiring_soon' => 3,
                    'valid' => 4,
                ];
                return $order[$vehicle->tax_status] ?? 5;
            })
            ->values()
            ->map(function ($vehicle) {
                return [
                    'id' => $vehicle->id,
                    'reference_name' => $vehicle->reference_name,
                    'vehicle_type' => $vehicle->vehicleType?->name,
                    'tax_status' => $vehicle->tax_status,
                    'tax_expiry_date' => $vehicle->tax_expiry_date,
                    'days_remaining' => $vehicle->days_remaining,
                ];
            });

        return response()->json([
            'data' => $vehicles,
            'count' => $vehicles->count(),
        ]);
    }

    public function activity(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        $activities = Activity::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'type' => $activity->type,
                    'message' => $activity->message,
                    'vehicle_id' => $activity->vehicle_id,
                    'vehicle_name' => $activity->vehicle_name,
                    'created_at' => $activity->created_at->toIso8601String(),
                ];
            });

        return response()->json([
            'data' => $activities,
        ]);
    }

    /**
     * Fleet summary - overview of fleet maintenance status.
     */
    public function fleetSummary(Request $request): JsonResponse
    {
        // Get yellow machines
        $yellowMachines = Vehicle::where('is_yellow_machine', true)
            ->with('machineType')
            ->get();

        $serviceStatusCounts = [
            'ok' => 0,
            'due_soon' => 0,
            'overdue' => 0,
            'unknown' => 0,
        ];

        foreach ($yellowMachines as $machine) {
            $status = $this->calculateServiceStatus($machine);
            $serviceStatusCounts[$status['status']]++;
        }

        // Pending approvals count (for senior DPF and admin)
        $user = $request->user();
        $pendingApprovals = 0;
        if (in_array($user->role, [User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR])) {
            $pendingApprovals = Service::where('status', 'pending')->count()
                + JobCard::where('status', 'pending')->count()
                + Inspection::where('status', 'pending')->count();
        }

        // Active watch list items
        $activeWatchItems = WatchListItem::where('status', 'active')->count();

        // Recent activity counts (last 7 days)
        $lastWeek = now()->subDays(7);
        $recentServices = Service::where('status', 'approved')
            ->where('approved_at', '>=', $lastWeek)
            ->count();
        $recentJobCards = JobCard::where('status', 'approved')
            ->where('approved_at', '>=', $lastWeek)
            ->count();

        return response()->json([
            'yellow_machines' => [
                'total' => $yellowMachines->count(),
                'service_status' => $serviceStatusCounts,
            ],
            'pending_approvals' => $pendingApprovals,
            'active_watch_items' => $activeWatchItems,
            'recent_activity' => [
                'services_last_7_days' => $recentServices,
                'job_cards_last_7_days' => $recentJobCards,
            ],
        ]);
    }

    /**
     * Get pending approvals for the dashboard.
     */
    public function pendingApprovals(Request $request): JsonResponse
    {
        // Only senior DPF or admin can view
        if (!in_array($request->user()->role, [User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $limit = $request->get('limit', 5);

        $pending = collect();

        // Get pending services
        $services = Service::with(['vehicle', 'site'])
            ->where('status', 'pending')
            ->orderBy('submitted_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(fn ($s) => [
                'type' => 'service',
                'id' => $s->id,
                'description' => "{$s->service_type} service - " . ($s->vehicle->reference_name ?? $s->vehicle->registration_number),
                'submitted_at' => $s->submitted_at,
            ]);

        // Get pending job cards
        $jobCards = JobCard::with(['vehicle', 'site'])
            ->where('status', 'pending')
            ->orderBy('submitted_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(fn ($jc) => [
                'type' => 'job_card',
                'id' => $jc->id,
                'description' => "{$jc->job_type} - " . ($jc->vehicle->reference_name ?? $jc->vehicle->registration_number),
                'submitted_at' => $jc->submitted_at,
            ]);

        // Get pending inspections
        $inspections = Inspection::with(['vehicle', 'template'])
            ->where('status', 'pending')
            ->orderBy('submitted_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(fn ($i) => [
                'type' => 'inspection',
                'id' => $i->id,
                'description' => ($i->template->name ?? 'Inspection') . ' - ' . ($i->vehicle->reference_name ?? $i->vehicle->registration_number),
                'submitted_at' => $i->submitted_at,
            ]);

        $all = $services->concat($jobCards)->concat($inspections)
            ->sortBy('submitted_at')
            ->take($limit)
            ->values();

        return response()->json([
            'data' => $all,
        ]);
    }

    /**
     * Get upcoming services.
     */
    public function upcomingServices(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);

        $yellowMachines = Vehicle::where('is_yellow_machine', true)
            ->with(['machineType', 'primarySite'])
            ->get()
            ->map(function ($machine) {
                $status = $this->calculateServiceStatus($machine);
                return [
                    'vehicle' => $machine,
                    'status' => $status,
                ];
            })
            ->filter(fn ($item) => $item['status']['status'] === 'due_soon')
            ->sortBy(fn ($item) => $item['status']['remaining'] ?? PHP_INT_MAX)
            ->take($limit)
            ->map(fn ($item) => [
                'id' => $item['vehicle']->id,
                'reference_name' => $item['vehicle']->reference_name,
                'registration_number' => $item['vehicle']->registration_number,
                'machine_type' => $item['vehicle']->machineType->name ?? 'Unknown',
                'site' => $item['vehicle']->primarySite->name ?? 'Unassigned',
                'service_type' => $item['status']['type'] ?? 'minor',
                'remaining' => $item['status']['remaining'] ?? null,
            ])
            ->values();

        return response()->json([
            'data' => $yellowMachines,
        ]);
    }

    /**
     * Get overdue services.
     */
    public function overdueServices(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);

        $yellowMachines = Vehicle::where('is_yellow_machine', true)
            ->with(['machineType', 'primarySite'])
            ->get()
            ->map(function ($machine) {
                $status = $this->calculateServiceStatus($machine);
                return [
                    'vehicle' => $machine,
                    'status' => $status,
                ];
            })
            ->filter(fn ($item) => $item['status']['status'] === 'overdue')
            ->sortByDesc(fn ($item) => $item['status']['overdue_by'] ?? 0)
            ->take($limit)
            ->map(fn ($item) => [
                'id' => $item['vehicle']->id,
                'reference_name' => $item['vehicle']->reference_name,
                'registration_number' => $item['vehicle']->registration_number,
                'machine_type' => $item['vehicle']->machineType->name ?? 'Unknown',
                'site' => $item['vehicle']->primarySite->name ?? 'Unassigned',
                'service_type' => $item['status']['type'] ?? 'minor',
                'overdue_by' => $item['status']['overdue_by'] ?? null,
            ])
            ->values();

        return response()->json([
            'data' => $yellowMachines,
        ]);
    }

    /**
     * Get watch list summary.
     */
    public function watchListSummary(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);

        $watchItems = WatchListItem::with(['vehicle', 'component'])
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($item) => [
                'id' => $item->id,
                'vehicle' => $item->vehicle->reference_name ?? $item->vehicle->registration_number,
                'vehicle_id' => $item->vehicle_id,
                'component' => $item->component->name ?? 'Unknown',
                'rating' => $item->rating_at_creation,
                'review_date' => $item->review_date,
                'is_overdue' => $item->review_date && Carbon::parse($item->review_date)->isPast(),
                'created_at' => $item->created_at,
            ]);

        $summary = [
            'total_active' => WatchListItem::where('status', 'active')->count(),
            'service_items' => WatchListItem::where('status', 'active')->where('rating_at_creation', 'service')->count(),
            'repair_items' => WatchListItem::where('status', 'active')->where('rating_at_creation', 'repair')->count(),
            'overdue_reviews' => WatchListItem::where('status', 'active')
                ->whereNotNull('review_date')
                ->whereDate('review_date', '<', now())
                ->count(),
        ];

        return response()->json([
            'summary' => $summary,
            'data' => $watchItems,
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
