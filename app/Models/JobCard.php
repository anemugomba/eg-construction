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

class JobCard extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, HasApprovalWorkflow;

    protected $fillable = [
        'vehicle_id',
        'job_type',
        'job_date',
        'reading_at_job',
        'site_id',
        'site_assignment_id',
        'description',
        'total_parts_cost',
        'status',
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
        'job_date' => 'date',
        'reading_at_job' => 'integer',
        'total_parts_cost' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * The vehicle this job card belongs to.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * The site where work was performed.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * The site assignment at time of work.
     */
    public function siteAssignment(): BelongsTo
    {
        return $this->belongsTo(SiteAssignment::class);
    }

    /**
     * Components involved in this job card.
     */
    public function components(): HasMany
    {
        return $this->hasMany(JobCardComponent::class);
    }

    /**
     * Parts used in this job card.
     */
    public function parts(): HasMany
    {
        return $this->hasMany(JobCardPart::class);
    }

    /**
     * Watch list items resolved by this job card.
     */
    public function resolvedWatchListItems(): HasMany
    {
        return $this->hasMany(WatchListItem::class, 'resolved_by_job_card_id');
    }

    /**
     * Attachments for this job card.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Check if this is a repair job.
     */
    public function isRepair(): bool
    {
        return $this->job_type === 'repair';
    }

    /**
     * Check if this is a tyre change.
     */
    public function isTyreChange(): bool
    {
        return $this->job_type === 'tyre_change';
    }

    /**
     * Check if this is a tyre repair.
     */
    public function isTyreRepair(): bool
    {
        return $this->job_type === 'tyre_repair';
    }

    /**
     * Recalculate total parts cost.
     */
    public function recalculatePartsCost(): self
    {
        $total = $this->parts()
            ->whereNotNull('unit_cost')
            ->get()
            ->sum(fn ($part) => $part->unit_cost * $part->quantity);

        $this->update(['total_parts_cost' => $total]);

        return $this;
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
     * Scope for a specific job type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('job_type', $type);
    }

    /**
     * Scope for job cards in date range.
     */
    public function scopeInDateRange(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('job_date', [$from, $to]);
    }
}
