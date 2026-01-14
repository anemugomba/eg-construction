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

class Service extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields, HasApprovalWorkflow;

    protected $fillable = [
        'vehicle_id',
        'service_type',
        'service_date',
        'reading_at_service',
        'site_id',
        'site_assignment_id',
        'notes',
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
        'service_date' => 'date',
        'reading_at_service' => 'integer',
        'total_parts_cost' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    /**
     * The vehicle this service belongs to.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * The site where service was performed.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * The site assignment at time of service.
     */
    public function siteAssignment(): BelongsTo
    {
        return $this->belongsTo(SiteAssignment::class);
    }

    /**
     * Parts used in this service.
     */
    public function parts(): HasMany
    {
        return $this->hasMany(ServicePart::class);
    }

    /**
     * Attachments for this service.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Check if this is a minor service.
     */
    public function isMinor(): bool
    {
        return $this->service_type === 'minor';
    }

    /**
     * Check if this is a major service.
     */
    public function isMajor(): bool
    {
        return $this->service_type === 'major';
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
     * Scope for minor services.
     */
    public function scopeMinor(Builder $query): Builder
    {
        return $query->where('service_type', 'minor');
    }

    /**
     * Scope for major services.
     */
    public function scopeMajor(Builder $query): Builder
    {
        return $query->where('service_type', 'major');
    }

    /**
     * Scope for services in date range.
     */
    public function scopeInDateRange(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('service_date', [$from, $to]);
    }
}
