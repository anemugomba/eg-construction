<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use App\Models\Concerns\HasApprovalWorkflow;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

class Inspection extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, HasApprovalWorkflow;

    protected $fillable = [
        'vehicle_id',
        'template_id',
        'inspection_date',
        'reading_at_inspection',
        'site_id',
        'site_assignment_id',
        'notes',
        'status',
        'completion_percentage',
        'submitted_by',
        'submitted_at',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'previous_submission_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'reading_at_inspection' => 'integer',
        'completion_percentage' => 'integer',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * The vehicle this inspection belongs to.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * The template used for this inspection.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(InspectionTemplate::class, 'template_id');
    }

    /**
     * The site where inspection was performed.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * The site assignment at time of inspection.
     */
    public function siteAssignment(): BelongsTo
    {
        return $this->belongsTo(SiteAssignment::class);
    }

    /**
     * Results for each checklist item.
     */
    public function results(): HasMany
    {
        return $this->hasMany(InspectionResult::class);
    }

    /**
     * Watch list items created from this inspection.
     */
    public function watchListItems(): HasMany
    {
        return $this->hasMany(WatchListItem::class, 'inspection_result_id');
    }

    /**
     * Attachments for this inspection.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get results with a specific rating.
     */
    public function getResultsByRating(string $rating): HasMany
    {
        return $this->results()->where('rating', $rating);
    }

    /**
     * Calculate and update completion percentage.
     */
    public function updateCompletionPercentage(): self
    {
        $totalItems = $this->template?->checklistItems()->count() ?? 0;
        $completedItems = $this->results()->count();

        $percentage = $totalItems > 0
            ? round(($completedItems / $totalItems) * 100)
            : 0;

        $this->update(['completion_percentage' => $percentage]);

        return $this;
    }

    /**
     * Check if inspection is complete.
     */
    public function isComplete(): bool
    {
        return $this->completion_percentage >= 100;
    }

    /**
     * Get count of items needing attention (repair/replace).
     */
    public function getItemsNeedingAttentionCount(): int
    {
        return $this->results()
            ->whereIn('rating', ['repair', 'replace'])
            ->count();
    }

    /**
     * Scope for a specific vehicle.
     */
    public function scopeForVehicle(Builder $query, string $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    /**
     * Scope for a specific site.
     */
    public function scopeForSite(Builder $query, string $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Scope for a specific template.
     */
    public function scopeUsingTemplate(Builder $query, string $templateId): Builder
    {
        return $query->where('template_id', $templateId);
    }

    /**
     * Scope for inspections in date range.
     */
    public function scopeInDateRange(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('inspection_date', [$from, $to]);
    }
}
