<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class VehicleExemption extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vehicle_id',
        'start_date',
        'end_date',
        'duration_months',
        'status',
        'reason',
        'ended_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'ended_at' => 'date',
        'duration_months' => 'integer',
    ];

    protected $appends = ['days_remaining', 'is_active'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'active' && Carbon::today()->lte($this->end_date)
        );
    }

    protected function daysRemaining(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->status !== 'active') {
                    return null;
                }
                return Carbon::today()->diffInDays($this->end_date, false);
            }
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
                     ->where('end_date', '>=', Carbon::today());
    }

    public function scopeForVehicle(Builder $query, int $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeExpiringSoon(Builder $query, int $days = 30): Builder
    {
        return $query->active()
                     ->where('end_date', '<=', Carbon::today()->addDays($days));
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', 'active')
                     ->where('end_date', '<', Carbon::today());
    }

    public function endExemption(): void
    {
        $this->update([
            'status' => 'ended',
            'ended_at' => Carbon::today(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'ended_at' => Carbon::today(),
        ]);
    }
}
