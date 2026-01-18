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

        $type = $request->get('type'); // 'service(s)', 'job_card(s)', 'inspection(s)', or null for all

        // Normalize singular to plural forms
        $typeMap = [
            'service' => 'services',
            'job_card' => 'job_cards',
            'inspection' => 'inspections',
        ];
        $type = $typeMap[$type] ?? $type;

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
                    'vehicle' => $s->vehicle,
                    'site' => $s->site,
                    'description' => "{$s->service_type} service",
                    'date' => $s->service_date,
                    'submitted_by' => $s->submittedByUser,
                    'submitted_at' => $s->submitted_at,
                    'item' => $s,
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
                    'vehicle' => $jc->vehicle,
                    'site' => $jc->site,
                    'description' => "{$jc->job_type}: " . substr($jc->description, 0, 50),
                    'date' => $jc->job_date,
                    'submitted_by' => $jc->submittedByUser,
                    'submitted_at' => $jc->submitted_at,
                    'item' => $jc,
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
                    'vehicle' => $i->vehicle,
                    'site' => $i->site,
                    'description' => $i->template->name ?? 'Inspection',
                    'date' => $i->inspection_date,
                    'submitted_by' => $i->submittedByUser,
                    'submitted_at' => $i->submitted_at,
                    'item' => $i,
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

    /**
     * Batch approve multiple items.
     */
    public function batchApprove(Request $request): JsonResponse
    {
        // Only senior DPF or admin can approve
        if (!in_array($request->user()->role, [User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:service,job_card,inspection',
            'items.*.id' => 'required|string',
        ]);

        $approved = [];
        $failed = [];

        foreach ($validated['items'] as $item) {
            try {
                $model = $this->resolveModel($item['type'], $item['id']);

                if (!$model) {
                    $failed[] = ['id' => $item['id'], 'error' => 'Not found'];
                    continue;
                }

                if ($model->status !== 'pending') {
                    $failed[] = ['id' => $item['id'], 'error' => 'Not in pending status'];
                    continue;
                }

                $model->update([
                    'status' => 'approved',
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                ]);

                $approved[] = $item['id'];
            } catch (\Exception $e) {
                $failed[] = ['id' => $item['id'], 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'approved' => $approved,
            'failed' => $failed,
            'approved_count' => count($approved),
            'failed_count' => count($failed),
        ]);
    }

    /**
     * Batch reject multiple items.
     */
    public function batchReject(Request $request): JsonResponse
    {
        // Only senior DPF or admin can reject
        if (!in_array($request->user()->role, [User::ROLE_SENIOR_DPF, User::ROLE_ADMINISTRATOR])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|in:service,job_card,inspection',
            'items.*.id' => 'required|string',
            'rejection_reason' => 'required|string|min:10',
        ]);

        $rejected = [];
        $failed = [];

        foreach ($validated['items'] as $item) {
            try {
                $model = $this->resolveModel($item['type'], $item['id']);

                if (!$model) {
                    $failed[] = ['id' => $item['id'], 'error' => 'Not found'];
                    continue;
                }

                if ($model->status !== 'pending') {
                    $failed[] = ['id' => $item['id'], 'error' => 'Not in pending status'];
                    continue;
                }

                $model->update([
                    'status' => 'rejected',
                    'rejection_reason' => $validated['rejection_reason'],
                ]);

                $rejected[] = $item['id'];
            } catch (\Exception $e) {
                $failed[] = ['id' => $item['id'], 'error' => $e->getMessage()];
            }
        }

        return response()->json([
            'rejected' => $rejected,
            'failed' => $failed,
            'rejected_count' => count($rejected),
            'failed_count' => count($failed),
        ]);
    }

    /**
     * Resolve model by type and ID.
     */
    private function resolveModel(string $type, string $id)
    {
        return match ($type) {
            'service' => Service::find($id),
            'job_card' => JobCard::find($id),
            'inspection' => Inspection::find($id),
            default => null,
        };
    }
}
