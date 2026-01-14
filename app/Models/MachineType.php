<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class MachineType extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'name',
        'tracking_unit',
        'minor_service_interval',
        'major_service_interval',
        'warning_threshold',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'minor_service_interval' => 'integer',
        'major_service_interval' => 'integer',
        'warning_threshold' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Check if this type tracks by hours.
     */
    public function tracksHours(): bool
    {
        return $this->tracking_unit === 'hours';
    }

    /**
     * Check if this type tracks by kilometers.
     */
    public function tracksKilometers(): bool
    {
        return $this->tracking_unit === 'kilometers';
    }

    /**
     * Vehicles/machines of this type.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'machine_type_id');
    }

    /**
     * Checklist items applicable to this machine type.
     */
    public function checklistItems(): BelongsToMany
    {
        return $this->belongsToMany(ChecklistItem::class, 'machine_type_checklist_items');
    }

    /**
     * Scope to filter active types only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by tracking unit.
     */
    public function scopeByTrackingUnit(Builder $query, string $unit): Builder
    {
        return $query->where('tracking_unit', $unit);
    }

    /**
     * Get the label for the tracking unit.
     */
    public function getTrackingUnitLabelAttribute(): string
    {
        return $this->tracking_unit === 'hours' ? 'Hours' : 'Kilometers';
    }

    /**
     * Get the abbreviated label for the tracking unit.
     */
    public function getTrackingUnitAbbreviationAttribute(): string
    {
        return $this->tracking_unit === 'hours' ? 'hrs' : 'km';
    }
}
