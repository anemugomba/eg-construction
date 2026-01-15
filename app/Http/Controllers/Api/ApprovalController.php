<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inspection;
use App\Models\JobCard;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    /**
     * Get all pending approvals (services, job cards, inspections).
     */
    public function index(Request $request): JsonResponse
    {
        // Only senior DPF or admin can view approvals
        if (!in_array($request->user()->role, [User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $type = $request->get('type'); // 'services', 'job_cards', 'inspections', or null for all

        $results = [];

        // Get pending services
        if (!$type || $type === 'services') {
            $services = Service::with(['vehicle', 'site', 'submittedByUser'])
                ->where('status', 'pending')
                ->orderBy('submitted_at', 'asc')
                ->get()
                ->map(fn ($s) => [
                    'type' => 'service',
                    'id' => $s->id,
                    'vehicle' => $s->vehicle->reference_name ?? $s->vehicle->registration_number,
                    'vehicle_id' => $s->vehicle_id,
                    'site' => $s->site->name ?? null,
                    'description' => "{$s->service_type} service",
                    'date' => $s->service_date,
                    'submitted_by' => $s->submittedByUser->name ?? null,
                    'submitted_at' => $s->submitted_at,
                    'data' => $s,
                ]);
            $results = array_merge($results, $services->toArray());
        }

        // Get pending job cards
        if (!$type || $type === 'job_cards') {
            $jobCards = JobCard::with(['vehicle', 'site', 'submittedByUser'])
                ->where('status', 'pending')
                ->orderBy('submitted_at', 'asc')
                ->get()
                ->map(fn ($jc) => [
                    'type' => 'job_card',
                    'id' => $jc->id,
                    'vehicle' => $jc->vehicle->reference_name ?? $jc->vehicle->registration_number,
                    'vehicle_id' => $jc->vehicle_id,
                    'site' => $jc->site->name ?? null,
                    'description' => "{$jc->job_type}: " . substr($jc->description, 0, 50),
                    'date' => $jc->job_date,
                    'submitted_by' => $jc->submittedByUser->name ?? null,
                    'submitted_at' => $jc->submitted_at,
                    'data' => $jc,
                ]);
            $results = array_merge($results, $jobCards->toArray());
        }

        // Get pending inspections
        if (!$type || $type === 'inspections') {
            $inspections = Inspection::with(['vehicle', 'site', 'template', 'submittedByUser'])
                ->where('status', 'pending')
                ->orderBy('submitted_at', 'asc')
                ->get()
                ->map(fn ($i) => [
                    'type' => 'inspection',
                    'id' => $i->id,
                    'vehicle' => $i->vehicle->reference_name ?? $i->vehicle->registration_number,
                    'vehicle_id' => $i->vehicle_id,
                    'site' => $i->site->name ?? null,
                    'description' => $i->template->name ?? 'Inspection',
                    'date' => $i->inspection_date,
                    'submitted_by' => $i->submittedByUser->name ?? null,
                    'submitted_at' => $i->submitted_at,
                    'data' => $i,
                ]);
            $results = array_merge($results, $inspections->toArray());
        }

        // Sort all by submitted_at
        usort($results, fn ($a, $b) => strtotime($a['submitted_at']) - strtotime($b['submitted_at']));

        return response()->json([
            'data' => $results,
            'count' => count($results),
        ]);
    }

    /**
     * Get count of pending approvals.
     */
    public function count(Request $request): JsonResponse
    {
        // Only senior DPF or admin can view approvals
        if (!in_array($request->user()->role, [User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $counts = [
            'services' => Service::where('status', 'pending')->count(),
            'job_cards' => JobCard::where('status', 'pending')->count(),
            'inspections' => Inspection::where('status', 'pending')->count(),
        ];

        $counts['total'] = array_sum($counts);

        return response()->json($counts);
    }
}
