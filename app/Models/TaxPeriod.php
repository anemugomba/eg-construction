<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class TaxPeriod extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'vehicle_id',
        'start_date',
        'end_date',
        'amount_paid',
        'status',
        'penalty_incurred',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount_paid' => 'decimal:2',
        'penalty_incurred' => 'boolean',
    ];

    protected $appends = ['tax_status', 'days_remaining'];

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Calculate tax status based on end_date
     * - valid: >30 days until expiry
     * - expiring_soon: 1-30 days until expiry
     * - expired: 0-30 days past expiry (grace period)
     * - penalty: >30 days past expiry
     */
    protected function taxStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                $endDate = Carbon::parse($this->end_date);
                $today = Carbon::today();
                $daysUntilExpiry = $today->diffInDays($endDate, false);

                if ($daysUntilExpiry > 30) {
                    return 'valid';
                }
                if ($daysUntilExpiry > 0) {
                    return 'expiring_soon';
                }
                if ($daysUntilExpiry >= -30) {
                    return 'expired';
                }
                return 'penalty';
            }
        );
    }

    /**
     * Days remaining until expiry (negative = days overdue)
     */
    protected function daysRemaining(): Attribute
    {
        return Attribute::make(
            get: function () {
                $endDate = Carbon::parse($this->end_date);
                $today = Carbon::today();
                return $today->diffInDays($endDate, false);
            }
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForVehicle(Builder $query, string $vehicleId): Builder
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('end_date', '>=', Carbon::today()->subDays(30))
                     ->orderBy('end_date', 'desc');
    }
}
