<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class IntervalOverride extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'vehicle_id',
        'override_type',
        'previous_value',
        'new_value',
        'reason',
        'changed_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'previous_value' => 'integer',
        'new_value' => 'integer',
    ];

    /**
     * The vehicle this override is for.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * The user who made this change.
     */
    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Check if this is a minor interval override.
     */
    public function isMinorInterval(): bool
    {
        return $this->override_type === 'minor_interval';
    }

    /**
     * Check if this is a major interval override.
     */
    public function isMajorInterval(): bool
    {
        return $this->override_type === 'major_interval';
    }

    /**
     * Check if this is a warning threshold override.
     */
    public function isWarningThreshold(): bool
    {
        return $this->override_type === 'warning_threshold';
    }

    /**
     * Get the change amount.
     */
    public function getChangeAttribute(): ?int
    {
        if ($this->previous_value === null) {
            return null;
        }

        return $this->new_value - $this->previous_value;
    }

    /**
     * Scope for a specific vehicle.
     */
    public function scopeForVehicle(Builder $query, string $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    /**
     * Scope for a specific override type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('override_type', $type);
    }

    /**
     * Scope to get latest first.
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }
}
