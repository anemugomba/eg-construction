<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class WatchListItem extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'vehicle_id',
        'component_id',
        'inspection_result_id',
        'rating_at_creation',
        'review_date',
        'notes',
        'status',
        'resolved_by_job_card_id',
        'resolved_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'review_date' => 'date',
        'resolved_at' => 'datetime',
    ];

    /**
     * The vehicle this watch item is for.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * The component being watched.
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * The inspection result that created this watch item.
     */
    public function inspectionResult(): BelongsTo
    {
        return $this->belongsTo(InspectionResult::class);
    }

    /**
     * The job card that resolved this watch item.
     */
    public function resolvedByJobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class, 'resolved_by_job_card_id');
    }

    /**
     * Check if watch item is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if watch item is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Check if review is due.
     */
    protected function isReviewDue(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->review_date && $this->review_date <= Carbon::today()
        );
    }

    /**
     * Days until review is due.
     */
    protected function daysUntilReview(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->review_date
                ? Carbon::today()->diffInDays($this->review_date, false)
                : null
        );
    }

    /**
     * Resolve this watch item.
     */
    public function resolve(?string $jobCardId = null): self
    {
        $this->update([
            'status' => 'resolved',
            'resolved_by_job_card_id' => $jobCardId,
            'resolved_at' => Carbon::now(),
        ]);

        return $this;
    }

    /**
     * Mark as machine disposed.
     */
    public function markMachineDisposed(): self
    {
        $this->update([
            'status' => 'machine_disposed',
        ]);

        return $this;
    }

    /**
     * Scope for active watch items.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for resolved watch items.
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope for a specific vehicle.
     */
    public function scopeForVehicle(Builder $query, string $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    /**
     * Scope for items with review due within days.
     */
    public function scopeReviewDueWithin(Builder $query, int $days): Builder
    {
        return $query->where('status', 'active')
            ->whereNotNull('review_date')
            ->where('review_date', '<=', Carbon::today()->addDays($days));
    }

    /**
     * Scope for overdue reviews.
     */
    public function scopeOverdueReview(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereNotNull('review_date')
            ->where('review_date', '<', Carbon::today());
    }
}
