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
        $pendingServices = 0;
        $pendingJobCards = 0;
        $pendingInspections = 0;
        if (in_array($user->role, [User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR])) {
            $pendingServices = Service::where('status', 'pending')->count();
            $pendingJobCards = JobCard::where('status', 'pending')->count();
            $pendingInspections = Inspection::where('status', 'pending')->count();
        }

        // Watch list summary
        $activeWatchItems = WatchListItem::where('status', 'active')->count();
        $serviceWatchItems = WatchListItem::where('status', 'active')->where('rating_at_creation', 'service')->count();
        $repairWatchItems = WatchListItem::where('status', 'active')->where('rating_at_creation', 'repair')->count();
        $overdueReviews = WatchListItem::where('status', 'active')
            ->whereNotNull('review_date')
            ->whereDate('review_date', '<', now())
            ->count();

        // Stale readings count (no reading in 7 days)
        $threshold = now()->subDays(7);
        $staleReadingsCount = Vehicle::where('is_yellow_machine', true)
            ->where('status', 'active')
            ->where(function ($q) use ($threshold) {
                $q->whereNull('last_reading_at')
                    ->orWhere('last_reading_at', '<', $threshold);
            })
            ->count();

        // Readings recorded today
        $todayStart = now()->startOfDay();
        $readingsToday = \App\Models\Reading::where('created_at', '>=', $todayStart)->count();

        return response()->json([
            'data' => [
                'yellow_machines' => [
                    'total' => $yellowMachines->count(),
                    'ok' => $serviceStatusCounts['ok'],
                    'due_soon' => $serviceStatusCounts['due_soon'],
                    'overdue' => $serviceStatusCounts['overdue'],
                    'unknown' => $serviceStatusCounts['unknown'],
                ],
                'pending_approvals' => [
                    'services' => $pendingServices,
                    'job_cards' => $pendingJobCards,
                    'inspections' => $pendingInspections,
                    'total' => $pendingServices + $pendingJobCards + $pendingInspections,
                ],
                'watch_list' => [
                    'total_active' => $activeWatchItems,
                    'service_items' => $serviceWatchItems,
                    'repair_items' => $repairWatchItems,
                    'overdue_reviews' => $overdueReviews,
                ],
                'readings' => [
                    'stale_readings_count' => $staleReadingsCount,
                    'readings_today' => $readingsToday,
                ],
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
            ->map(function ($item) {
                $vehicle = $item['vehicle'];
                $status = $item['status'];
                $trackingUnit = $vehicle->machineType?->tracking_unit ?? 'hours';
                $currentReading = $trackingUnit === 'hours' ? $vehicle->current_hours : $vehicle->current_km;
                $serviceType = $status['type'] ?? 'minor';
                $interval = $serviceType === 'major'
                    ? ($vehicle->machineType?->major_service_interval ?? 0)
                    : ($vehicle->machineType?->minor_service_interval ?? 0);
                $lastServiceReading = $serviceType === 'major'
                    ? ($vehicle->last_major_service_reading ?? 0)
                    : ($vehicle->last_minor_service_reading ?? 0);
                $dueAtReading = $lastServiceReading + $interval;

                return [
                    'vehicle_id' => (string) $vehicle->id,
                    'vehicle_name' => $vehicle->reference_name ?? $vehicle->registration_number,
                    'site_name' => $vehicle->primarySite?->name ?? 'Unassigned',
                    'service_type' => $serviceType,
                    'current_reading' => $currentReading ?? 0,
                    'due_at_reading' => $dueAtReading,
                    'overdue_by' => $status['overdue_by'] ?? 0,
                    'tracking_unit' => $trackingUnit,
                ];
            })
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
     * Get recent fleet activity (services, job cards, inspections approved).
     */
    public function fleetActivity(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);

        // Get recent approved services
        $services = Service::with(['vehicle', 'site', 'approvedByUser'])
            ->where('status', 'approved')
            ->orderBy('approved_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($s) => [
                'id' => (string) $s->id,
                'type' => 'service_approved',
                'description' => ucfirst($s->service_type) . ' service approved',
                'vehicle_name' => $s->vehicle->reference_name ?? $s->vehicle->registration_number,
                'site_name' => $s->site?->name ?? 'Unknown',
                'user_name' => $s->approvedByUser?->name ?? 'System',
                'created_at' => $s->approved_at?->toIso8601String() ?? now()->toIso8601String(),
            ]);

        // Get recent approved job cards
        $jobCards = JobCard::with(['vehicle', 'site', 'approvedByUser'])
            ->where('status', 'approved')
            ->orderBy('approved_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($jc) => [
                'id' => (string) $jc->id,
                'type' => 'job_card_approved',
                'description' => ucfirst($jc->job_type) . ' job completed',
                'vehicle_name' => $jc->vehicle->reference_name ?? $jc->vehicle->registration_number,
                'site_name' => $jc->site?->name ?? 'Unknown',
                'user_name' => $jc->approvedByUser?->name ?? 'System',
                'created_at' => $jc->approved_at?->toIso8601String() ?? now()->toIso8601String(),
            ]);

        // Get recent approved inspections
        $inspections = Inspection::with(['vehicle', 'template', 'approvedByUser', 'site'])
            ->where('status', 'approved')
            ->orderBy('approved_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn ($i) => [
                'id' => (string) $i->id,
                'type' => 'inspection_approved',
                'description' => ($i->template->name ?? 'Inspection') . ' completed',
                'vehicle_name' => $i->vehicle->reference_name ?? $i->vehicle->registration_number,
                'site_name' => $i->site?->name ?? 'Unknown',
                'user_name' => $i->approvedByUser?->name ?? 'System',
                'created_at' => $i->approved_at?->toIso8601String() ?? now()->toIso8601String(),
            ]);

        $all = $services->concat($jobCards)->concat($inspections)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values();

        return response()->json([
            'data' => $all,
        ]);
    }

    /**
     * Get vehicles with stale readings (no reading in last 7 days).
     */
    public function staleReadings(Request $request): JsonResponse
    {
        $daysThreshold = $request->get('days', 7);
        $limit = $request->get('limit', 20);
        $threshold = now()->subDays($daysThreshold);

        $staleVehicles = Vehicle::where('is_yellow_machine', true)
            ->where('status', 'active')
            ->with(['machineType', 'primarySite'])
            ->get()
            ->filter(function ($vehicle) use ($threshold) {
                // No readings at all, or last reading is older than threshold
                return !$vehicle->last_reading_at ||
                    Carbon::parse($vehicle->last_reading_at)->lt($threshold);
            })
            ->sortBy('last_reading_at')
            ->take($limit)
            ->map(function ($v) {
                $trackingUnit = $v->machineType?->tracking_unit ?? 'hours';
                $currentReading = $trackingUnit === 'hours' ? $v->current_hours : $v->current_km;

                return [
                    'id' => (string) $v->id,
                    'reference_name' => $v->reference_name ?? $v->registration_number,
                    'site_name' => $v->primarySite?->name ?? 'Unassigned',
                    'last_reading_at' => $v->last_reading_at,
                    'days_since_reading' => $v->last_reading_at
                        ? (int) Carbon::parse($v->last_reading_at)->diffInDays(now())
                        : 999,
                    'current_reading' => $currentReading,
                    'tracking_unit' => $trackingUnit,
                ];
            })
            ->values();

        return response()->json([
            'data' => $staleVehicles,
        ]);
    }

    /**
     * Get pending submissions for the current user (Site DPF view).
     */
    public function mySubmissions(Request $request): JsonResponse
    {
        $user = $request->user();

        $submissions = collect();

        // Get user's pending/rejected services
        $services = Service::with(['vehicle', 'site'])
            ->where('submitted_by', $user->id)
            ->whereIn('status', ['pending', 'rejected', 'draft'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn ($s) => [
                'type' => 'service',
                'id' => $s->id,
                'description' => ucfirst($s->service_type) . ' service',
                'vehicle_name' => $s->vehicle->reference_name ?? $s->vehicle->registration_number,
                'site' => $s->site?->name ?? 'Unknown',
                'status' => $s->status,
                'rejection_reason' => $s->rejection_reason,
                'submitted_at' => $s->submitted_at,
                'updated_at' => $s->updated_at,
            ]);

        // Get user's pending/rejected job cards
        $jobCards = JobCard::with(['vehicle', 'site'])
            ->where('submitted_by', $user->id)
            ->whereIn('status', ['pending', 'rejected', 'draft'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn ($jc) => [
                'type' => 'job_card',
                'id' => $jc->id,
                'description' => ucfirst($jc->job_type),
                'vehicle_name' => $jc->vehicle->reference_name ?? $jc->vehicle->registration_number,
                'site' => $jc->site?->name ?? 'Unknown',
                'status' => $jc->status,
                'rejection_reason' => $jc->rejection_reason,
                'submitted_at' => $jc->submitted_at,
                'updated_at' => $jc->updated_at,
            ]);

        // Get user's pending/rejected inspections
        $inspections = Inspection::with(['vehicle', 'template'])
            ->where('submitted_by', $user->id)
            ->whereIn('status', ['pending', 'rejected', 'draft'])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn ($i) => [
                'type' => 'inspection',
                'id' => $i->id,
                'description' => $i->template->name ?? 'Inspection',
                'vehicle_name' => $i->vehicle->reference_name ?? $i->vehicle->registration_number,
                'site' => $i->site?->name ?? 'Unknown',
                'status' => $i->status,
                'rejection_reason' => $i->rejection_reason,
                'submitted_at' => $i->submitted_at,
                'updated_at' => $i->updated_at,
            ]);

        $all = $services->concat($jobCards)->concat($inspections)
            ->sortByDesc('updated_at')
            ->values();

        return response()->json([
            'data' => $all,
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
