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

class SiteAssignment extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'vehicle_id',
        'site_id',
        'assigned_at',
        'ended_at',
        'assigned_by',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'assigned_at' => 'date',
        'ended_at' => 'date',
    ];

    /**
     * The vehicle/machine this assignment is for.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * The site the vehicle is assigned to.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * The user who made this assignment.
     */
    public function assignedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Check if assignment is currently active.
     */
    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->ended_at === null
        );
    }

    /**
     * Get duration of assignment in days.
     */
    protected function durationDays(): Attribute
    {
        return Attribute::make(
            get: function () {
                $endDate = $this->ended_at ?? Carbon::today();
                return $this->assigned_at->diffInDays($endDate);
            }
        );
    }

    /**
     * End this assignment.
     */
    public function endAssignment(?Carbon $endDate = null): self
    {
        $this->update([
            'ended_at' => $endDate ?? Carbon::today(),
        ]);

        return $this;
    }

    /**
     * Scope to filter active assignments only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('ended_at');
    }

    /**
     * Scope to filter by vehicle.
     */
    public function scopeForVehicle(Builder $query, string $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    /**
     * Scope to filter by site.
     */
    public function scopeForSite(Builder $query, string $siteId): Builder
    {
        return $query->where('site_id', $siteId);
    }

    /**
     * Scope to find assignment active on a specific date.
     */
    public function scopeActiveOn(Builder $query, Carbon $date): Builder
    {
        return $query->where('assigned_at', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('ended_at')
                  ->orWhere('ended_at', '>=', $date);
            });
    }
}
