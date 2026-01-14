<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Reading extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'vehicle_id',
        'reading_value',
        'reading_type',
        'source',
        'is_anomaly_override',
        'anomaly_reason',
        'recorded_by',
        'recorded_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'reading_value' => 'integer',
        'is_anomaly_override' => 'boolean',
        'recorded_at' => 'datetime',
    ];

    /**
     * The vehicle this reading belongs to.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * The user who recorded this reading.
     */
    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Check if this is an hours reading.
     */
    public function isHours(): bool
    {
        return $this->reading_type === 'hours';
    }

    /**
     * Check if this is a kilometers reading.
     */
    public function isKilometers(): bool
    {
        return $this->reading_type === 'kilometers';
    }

    /**
     * Check if this reading is from telematics.
     */
    public function isFromTelematics(): bool
    {
        return $this->source === 'telematics';
    }

    /**
     * Scope for a specific vehicle.
     */
    public function scopeForVehicle(Builder $query, string $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    /**
     * Scope for a specific reading type.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('reading_type', $type);
    }

    /**
     * Scope for readings from a specific source.
     */
    public function scopeFromSource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    /**
     * Scope to get latest reading first.
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('recorded_at', 'desc');
    }

    /**
     * Scope for anomaly readings.
     */
    public function scopeAnomalies(Builder $query): Builder
    {
        return $query->where('is_anomaly_override', true);
    }
}
