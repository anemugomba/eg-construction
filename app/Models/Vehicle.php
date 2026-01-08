<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_name',
        'vehicle_type_id',
        'registration_number',
        'chassis_number',
        'engine_number',
        'make',
        'model',
        'year_of_manufacture',
        'status',
    ];

    protected $casts = [
        'year_of_manufacture' => 'integer',
    ];

    protected $appends = ['tax_status', 'tax_expiry_date', 'days_remaining', 'is_exempted', 'exemption_end_date'];

    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function taxPeriods(): HasMany
    {
        return $this->hasMany(TaxPeriod::class)->orderBy('end_date', 'desc');
    }

    public function currentTaxPeriod(): HasOne
    {
        return $this->hasOne(TaxPeriod::class)
            ->orderBy('end_date', 'desc')
            ->latest();
    }

    public function exemptions(): HasMany
    {
        return $this->hasMany(VehicleExemption::class)->orderBy('end_date', 'desc');
    }

    public function currentExemption(): HasOne
    {
        return $this->hasOne(VehicleExemption::class)
            ->where('status', 'active')
            ->where('end_date', '>=', Carbon::today())
            ->latest();
    }

    protected function isExempted(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->currentExemption !== null
        );
    }

    protected function exemptionEndDate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->currentExemption?->end_date?->format('Y-m-d')
        );
    }

    protected function taxStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Check if exempted first
                if ($this->currentExemption) {
                    return 'exempted';
                }

                $currentPeriod = $this->currentTaxPeriod;
                if (!$currentPeriod) {
                    return 'no_tax';
                }
                return $currentPeriod->tax_status;
            }
        );
    }

    protected function taxExpiryDate(): Attribute
    {
        return Attribute::make(
            get: function () {
                $currentPeriod = $this->currentTaxPeriod;
                return $currentPeriod?->end_date?->format('Y-m-d');
            }
        );
    }

    protected function daysRemaining(): Attribute
    {
        return Attribute::make(
            get: function () {
                $currentPeriod = $this->currentTaxPeriod;
                return $currentPeriod?->days_remaining;
            }
        );
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('reference_name', 'like', "%{$search}%")
              ->orWhere('registration_number', 'like', "%{$search}%")
              ->orWhere('make', 'like', "%{$search}%")
              ->orWhere('model', 'like', "%{$search}%");
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeExempted(Builder $query): Builder
    {
        return $query->whereHas('exemptions', function ($q) {
            $q->where('status', 'active')
              ->where('end_date', '>=', Carbon::today());
        });
    }

    public function scopeNotExempted(Builder $query): Builder
    {
        return $query->whereDoesntHave('exemptions', function ($q) {
            $q->where('status', 'active')
              ->where('end_date', '>=', Carbon::today());
        });
    }
}
