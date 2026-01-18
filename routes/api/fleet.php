<?php

use App\Http\Controllers\Api\ApprovalController;
use App\Http\Controllers\Api\ChecklistController;
use App\Http\Controllers\Api\ComponentController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InspectionController;
use App\Http\Controllers\Api\InspectionTemplateController;
use App\Http\Controllers\Api\JobCardController;
use App\Http\Controllers\Api\JobCardComponentController;
use App\Http\Controllers\Api\JobCardPartController;
use App\Http\Controllers\Api\MachineTypeController;
use App\Http\Controllers\Api\OilAnalysisController;
use App\Http\Controllers\Api\PartsCatalogController;
use App\Http\Controllers\Api\ReadingController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ServicePartController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\WatchListController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fleet Maintenance Routes
|--------------------------------------------------------------------------
*/

// Sites
Route::apiResource('sites', SiteController::class);
Route::get('sites/{site}/machines', [SiteController::class, 'machines']);
Route::get('sites/{site}/staff', [SiteController::class, 'staff']);

// Machine Types
Route::apiResource('machine-types', MachineTypeController::class);
Route::get('machine-types/{machineType}/checklist-items', [MachineTypeController::class, 'checklistItems']);
Route::post('machine-types/{machineType}/checklist-items', [MachineTypeController::class, 'syncChecklistItems']);
Route::get('machine-types/{machineType}/inspection-templates', [MachineTypeController::class, 'inspectionTemplates']);

// Checklists
Route::get('checklist-categories', [ChecklistController::class, 'categories']);
Route::get('checklist-items', [ChecklistController::class, 'items']);
Route::post('checklist-items', [ChecklistController::class, 'storeItem']);

// Inspection Templates
Route::apiResource('inspection-templates', InspectionTemplateController::class);
Route::get('inspection-templates/{inspectionTemplate}/items', [InspectionTemplateController::class, 'items']);
Route::post('inspection-templates/{inspectionTemplate}/items', [InspectionTemplateController::class, 'syncItems']);

// Readings
Route::get('readings', [ReadingController::class, 'all']);
Route::get('vehicles/{vehicle}/readings', [ReadingController::class, 'index']);
Route::post('vehicles/{vehicle}/readings', [ReadingController::class, 'store']);
Route::post('readings/bulk', [ReadingController::class, 'bulkStore']);

// Services
Route::apiResource('services', ServiceController::class);
Route::post('services/{service}/submit', [ServiceController::class, 'submit']);
Route::post('services/{service}/approve', [ServiceController::class, 'approve']);
Route::post('services/{service}/reject', [ServiceController::class, 'reject']);
Route::get('services/{service}/parts', [ServicePartController::class, 'index']);
Route::post('services/{service}/parts', [ServicePartController::class, 'store']);
Route::put('services/{service}/parts/{servicePart}', [ServicePartController::class, 'update']);
Route::delete('services/{service}/parts/{servicePart}', [ServicePartController::class, 'destroy']);
Route::put('service-parts/{servicePart}', [ServicePartController::class, 'updateFlat']);
Route::delete('service-parts/{servicePart}', [ServicePartController::class, 'destroyFlat']);

// Job Cards
Route::apiResource('job-cards', JobCardController::class);
Route::post('job-cards/{jobCard}/submit', [JobCardController::class, 'submit']);
Route::post('job-cards/{jobCard}/approve', [JobCardController::class, 'approve']);
Route::post('job-cards/{jobCard}/reject', [JobCardController::class, 'reject']);
Route::get('job-cards/{jobCard}/related-watch-items', [JobCardController::class, 'relatedWatchItems']);
Route::post('job-cards/{jobCard}/resolve-watch-items', [JobCardController::class, 'resolveWatchItems']);

// Job Card Components
Route::get('job-cards/{jobCard}/components', [JobCardComponentController::class, 'index']);
Route::post('job-cards/{jobCard}/components', [JobCardComponentController::class, 'store']);
Route::put('job-cards/{jobCard}/components/{jobCardComponent}', [JobCardComponentController::class, 'update']);
Route::delete('job-cards/{jobCard}/components/{jobCardComponent}', [JobCardComponentController::class, 'destroy']);
Route::put('job-card-components/{jobCardComponent}', [JobCardComponentController::class, 'updateFlat']);
Route::delete('job-card-components/{jobCardComponent}', [JobCardComponentController::class, 'destroyFlat']);

// Job Card Parts
Route::get('job-cards/{jobCard}/parts', [JobCardPartController::class, 'index']);
Route::post('job-cards/{jobCard}/parts', [JobCardPartController::class, 'store']);
Route::put('job-cards/{jobCard}/parts/{jobCardPart}', [JobCardPartController::class, 'update']);
Route::delete('job-cards/{jobCard}/parts/{jobCardPart}', [JobCardPartController::class, 'destroy']);
Route::put('job-card-parts/{jobCardPart}', [JobCardPartController::class, 'updateFlat']);
Route::delete('job-card-parts/{jobCardPart}', [JobCardPartController::class, 'destroyFlat']);

// Inspections
Route::apiResource('inspections', InspectionController::class);
Route::post('inspections/{inspection}/submit', [InspectionController::class, 'submit']);
Route::post('inspections/{inspection}/approve', [InspectionController::class, 'approve']);
Route::post('inspections/{inspection}/reject', [InspectionController::class, 'reject']);
Route::get('inspections/{inspection}/results', [InspectionController::class, 'results']);
Route::put('inspections/{inspection}/results', [InspectionController::class, 'updateResults']);

// Watch List
Route::apiResource('watch-list', WatchListController::class);
Route::post('watch-list/{watchListItem}/resolve', [WatchListController::class, 'resolve']);

// Oil Analysis
Route::get('vehicles/{vehicle}/oil-analyses', [OilAnalysisController::class, 'index']);
Route::post('vehicles/{vehicle}/oil-analyses', [OilAnalysisController::class, 'store']);
Route::get('oil-analyses/{oilAnalysis}', [OilAnalysisController::class, 'show']);
Route::put('oil-analyses/{oilAnalysis}', [OilAnalysisController::class, 'update']);
Route::delete('oil-analyses/{oilAnalysis}', [OilAnalysisController::class, 'destroy']);

// Components & Parts Catalog
Route::apiResource('components', ComponentController::class);
Route::apiResource('parts-catalog', PartsCatalogController::class);

// Vehicle Fleet Extensions
Route::get('vehicles/{vehicle}/site-assignments', [VehicleController::class, 'siteAssignments']);
Route::post('vehicles/{vehicle}/site-assignments', [VehicleController::class, 'assignToSite']);
Route::get('vehicles/{vehicle}/service-status', [VehicleController::class, 'serviceStatus']);
Route::get('vehicles/{vehicle}/interval-overrides', [VehicleController::class, 'intervalOverrides']);
Route::post('vehicles/{vehicle}/interval-overrides', [VehicleController::class, 'createIntervalOverride']);
Route::get('vehicles/{vehicle}/services', [VehicleController::class, 'services']);

// Approvals Queue
Route::get('approvals', [ApprovalController::class, 'index']);
Route::get('approvals/count', [ApprovalController::class, 'count']);
Route::post('approvals/batch-approve', [ApprovalController::class, 'batchApprove']);
Route::post('approvals/batch-reject', [ApprovalController::class, 'batchReject']);

// Fleet Dashboard (nested under /dashboard/fleet/)
Route::prefix('dashboard/fleet')->group(function () {
    Route::get('summary', [DashboardController::class, 'fleetSummary']);
    Route::get('pending-approvals', [DashboardController::class, 'pendingApprovals']);
    Route::get('upcoming-services', [DashboardController::class, 'upcomingServices']);
    Route::get('overdue-services', [DashboardController::class, 'overdueServices']);
    Route::get('watch-list-summary', [DashboardController::class, 'watchListSummary']);
    Route::get('activity', [DashboardController::class, 'fleetActivity']);
    Route::get('stale-readings', [DashboardController::class, 'staleReadings']);
    Route::get('my-submissions', [DashboardController::class, 'mySubmissions']);
});

// Reports
Route::prefix('reports')->group(function () {
    Route::get('fleet-status', [ReportController::class, 'fleetStatus']);
    Route::get('service-history', [ReportController::class, 'serviceHistory']);
    Route::get('job-card-history', [ReportController::class, 'jobCardHistory']);
    Route::get('component-lifespan', [ReportController::class, 'componentLifespan']);
    Route::get('site-performance', [ReportController::class, 'sitePerformance']);
    Route::get('cost-analysis', [ReportController::class, 'costAnalysis']);
    Route::get('inspection-history', [ReportController::class, 'inspectionHistory']);
    // Export endpoints
    Route::get('{reportType}/export', [ReportController::class, 'export']);
});
