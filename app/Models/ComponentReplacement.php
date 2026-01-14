<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class ComponentReplacement extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'vehicle_id',
        'component_id',
        'job_card_id',
        'replaced_at',
        'reading_at_replacement',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'replaced_at' => 'date',
        'reading_at_replacement' => 'integer',
    ];

    /**
     * The vehicle this replacement is for.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * The component that was replaced.
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * The job card that performed this replacement.
     */
    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    /**
     * Scope for a specific vehicle.
     */
    public function scopeForVehicle(Builder $query, string $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    /**
     * Scope for a specific component.
     */
    public function scopeForComponent(Builder $query, string $componentId): Builder
    {
        return $query->where('component_id', $componentId);
    }

    /**
     * Scope to get latest first.
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('replaced_at', 'desc');
    }
}
