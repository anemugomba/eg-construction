<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Vehicle extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

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
        // Fleet management fields
        'current_hours',
        'current_km',
        'last_reading_at',
        'is_yellow_machine',
        'machine_type_id',
        'primary_site_id',
        'warning_threshold_hours',
        'warning_threshold_km',
        'last_minor_service_reading',
        'last_major_service_reading',
        'last_minor_service_date',
        'last_major_service_date',
        'reading_stale_days',
        'has_reading_anomaly',
    ];

    protected $casts = [
        'year_of_manufacture' => 'integer',
        'current_hours' => 'integer',
        'current_km' => 'integer',
        'last_reading_at' => 'datetime',
        'is_yellow_machine' => 'boolean',
        'warning_threshold_hours' => 'integer',
        'warning_threshold_km' => 'integer',
        'last_minor_service_reading' => 'integer',
        'last_major_service_reading' => 'integer',
        'last_minor_service_date' => 'date',
        'last_major_service_date' => 'date',
        'reading_stale_days' => 'integer',
        'has_reading_anomaly' => 'boolean',
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

    // ==========================================
    // Fleet Management Relationships
    // ==========================================

    /**
     * The machine type for yellow machines.
     */
    public function machineType(): BelongsTo
    {
        return $this->belongsTo(MachineType::class);
    }

    /**
     * The primary site where this vehicle is assigned.
     */
    public function primarySite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'primary_site_id');
    }

    /**
     * All site assignments (location history).
     */
    public function siteAssignments(): HasMany
    {
        return $this->hasMany(SiteAssignment::class)->orderBy('assigned_at', 'desc');
    }

    /**
     * The current site assignment.
     */
    public function currentSiteAssignment(): HasOne
    {
        return $this->hasOne(SiteAssignment::class)
            ->whereNull('ended_at')
            ->latest('assigned_at');
    }

    /**
     * All readings for this vehicle.
     */
    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class)->orderBy('recorded_at', 'desc');
    }

    /**
     * The latest reading.
     */
    public function latestReading(): HasOne
    {
        return $this->hasOne(Reading::class)->latest('recorded_at');
    }

    /**
     * All services for this vehicle.
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class)->orderBy('service_date', 'desc');
    }

    /**
     * All job cards for this vehicle.
     */
    public function jobCards(): HasMany
    {
        return $this->hasMany(JobCard::class)->orderBy('job_date', 'desc');
    }

    /**
     * All inspections for this vehicle.
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class)->orderBy('inspection_date', 'desc');
    }

    /**
     * Watch list items for this vehicle.
     */
    public function watchListItems(): HasMany
    {
        return $this->hasMany(WatchListItem::class);
    }

    /**
     * Active watch list items.
     */
    public function activeWatchListItems(): HasMany
    {
        return $this->hasMany(WatchListItem::class)->where('status', 'active');
    }

    /**
     * Oil analyses for this vehicle.
     */
    public function oilAnalyses(): HasMany
    {
        return $this->hasMany(OilAnalysis::class)->orderBy('analysis_date', 'desc');
    }

    /**
     * Interval override history.
     */
    public function intervalOverrides(): HasMany
    {
        return $this->hasMany(IntervalOverride::class)->orderBy('created_at', 'desc');
    }

    /**
     * Component replacement history.
     */
    public function componentReplacements(): HasMany
    {
        return $this->hasMany(ComponentReplacement::class)->orderBy('replaced_at', 'desc');
    }

    /**
     * Attachments for this vehicle.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    // ==========================================
    // Fleet Management Attributes
    // ==========================================

    /**
     * Get the tracking unit for this vehicle (hours or kilometers).
     */
    protected function trackingUnit(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_yellow_machine
                ? ($this->machineType?->tracking_unit ?? 'hours')
                : 'kilometers'
        );
    }

    /**
     * Get the current reading based on tracking unit.
     */
    protected function currentReading(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->is_yellow_machine
                ? $this->current_hours
                : $this->current_km
        );
    }

    /**
     * Get the effective minor service interval.
     */
    protected function effectiveMinorInterval(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->machineType?->minor_service_interval
        );
    }

    /**
     * Get the effective major service interval.
     */
    protected function effectiveMajorInterval(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->machineType?->major_service_interval
        );
    }

    /**
     * Get the effective warning threshold.
     */
    protected function effectiveWarningThreshold(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_yellow_machine) {
                    return $this->warning_threshold_hours ?? $this->machineType?->warning_threshold;
                }
                return $this->warning_threshold_km ?? $this->machineType?->warning_threshold;
            }
        );
    }

    /**
     * Get the next minor service due reading.
     */
    protected function nextMinorServiceDue(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->effective_minor_interval) {
                    return null;
                }
                $lastReading = $this->last_minor_service_reading ?? 0;
                return $lastReading + $this->effective_minor_interval;
            }
        );
    }

    /**
     * Get the next major service due reading.
     */
    protected function nextMajorServiceDue(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->effective_major_interval) {
                    return null;
                }
                $lastReading = $this->last_major_service_reading ?? 0;
                return $lastReading + $this->effective_major_interval;
            }
        );
    }

    /**
     * Get remaining until minor service.
     */
    protected function remainingUntilMinorService(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->next_minor_service_due || !$this->current_reading) {
                    return null;
                }
                return $this->next_minor_service_due - $this->current_reading;
            }
        );
    }

    /**
     * Get remaining until major service.
     */
    protected function remainingUntilMajorService(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->next_major_service_due || !$this->current_reading) {
                    return null;
                }
                return $this->next_major_service_due - $this->current_reading;
            }
        );
    }

    /**
     * Check if minor service is due soon (within warning threshold).
     */
    protected function isMinorServiceDueSoon(): Attribute
    {
        return Attribute::make(
            get: function () {
                $remaining = $this->remaining_until_minor_service;
                $threshold = $this->effective_warning_threshold;
                if ($remaining === null || $threshold === null) {
                    return false;
                }
                return $remaining <= $threshold && $remaining > 0;
            }
        );
    }

    /**
     * Check if major service is due soon (within warning threshold).
     */
    protected function isMajorServiceDueSoon(): Attribute
    {
        return Attribute::make(
            get: function () {
                $remaining = $this->remaining_until_major_service;
                $threshold = $this->effective_warning_threshold;
                if ($remaining === null || $threshold === null) {
                    return false;
                }
                return $remaining <= $threshold && $remaining > 0;
            }
        );
    }

    /**
     * Check if minor service is overdue.
     */
    protected function isMinorServiceOverdue(): Attribute
    {
        return Attribute::make(
            get: function () {
                $remaining = $this->remaining_until_minor_service;
                return $remaining !== null && $remaining <= 0;
            }
        );
    }

    /**
     * Check if major service is overdue.
     */
    protected function isMajorServiceOverdue(): Attribute
    {
        return Attribute::make(
            get: function () {
                $remaining = $this->remaining_until_major_service;
                return $remaining !== null && $remaining <= 0;
            }
        );
    }

    /**
     * Get the overall service status.
     */
    protected function serviceStatus(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_minor_service_overdue || $this->is_major_service_overdue) {
                    return 'overdue';
                }
                if ($this->is_minor_service_due_soon || $this->is_major_service_due_soon) {
                    return 'due_soon';
                }
                return 'ok';
            }
        );
    }

    /**
     * Check if the reading is stale.
     */
    protected function readingIsStale(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->last_reading_at) {
                    return true;
                }
                $staleDays = $this->reading_stale_days ?? 7;
                return $this->last_reading_at->diffInDays(Carbon::now()) > $staleDays;
            }
        );
    }

    // ==========================================
    // Fleet Management Scopes
    // ==========================================

    /**
     * Scope for yellow machines only.
     */
    public function scopeYellowMachines(Builder $query): Builder
    {
        return $query->where('is_yellow_machine', true);
    }

    /**
     * Scope for road vehicles only.
     */
    public function scopeRoadVehicles(Builder $query): Builder
    {
        return $query->where('is_yellow_machine', false);
    }

    /**
     * Scope for vehicles at a specific site.
     */
    public function scopeAtSite(Builder $query, string $siteId): Builder
    {
        return $query->where('primary_site_id', $siteId);
    }

    /**
     * Scope for vehicles of a specific machine type.
     */
    public function scopeOfMachineType(Builder $query, string $machineTypeId): Builder
    {
        return $query->where('machine_type_id', $machineTypeId);
    }

    /**
     * Scope for vehicles with stale readings.
     */
    public function scopeWithStaleReadings(Builder $query, int $staleDays = 7): Builder
    {
        return $query->where(function ($q) use ($staleDays) {
            $q->whereNull('last_reading_at')
              ->orWhere('last_reading_at', '<', Carbon::now()->subDays($staleDays));
        });
    }

    /**
     * Scope for vehicles with reading anomalies.
     */
    public function scopeWithReadingAnomaly(Builder $query): Builder
    {
        return $query->where('has_reading_anomaly', true);
    }

    /**
     * Scope for vehicles with service due soon.
     */
    public function scopeServiceDueSoon(Builder $query): Builder
    {
        return $query->whereHas('machineType', function ($q) {
            $q->whereRaw('
                (vehicles.is_yellow_machine = true AND
                 vehicles.current_hours IS NOT NULL AND
                 (
                   (vehicles.last_minor_service_reading + machine_types.minor_service_interval - vehicles.current_hours) <= machine_types.warning_threshold
                   OR
                   (vehicles.last_major_service_reading + machine_types.major_service_interval - vehicles.current_hours) <= machine_types.warning_threshold
                 )
                )
                OR
                (vehicles.is_yellow_machine = false AND
                 vehicles.current_km IS NOT NULL AND
                 (
                   (vehicles.last_minor_service_reading + machine_types.minor_service_interval - vehicles.current_km) <= machine_types.warning_threshold
                   OR
                   (vehicles.last_major_service_reading + machine_types.major_service_interval - vehicles.current_km) <= machine_types.warning_threshold
                 )
                )
            ');
        });
    }

    // ==========================================
    // Fleet Management Methods
    // ==========================================

    /**
     * Record a new reading.
     */
    public function recordReading(int $value, string $source = 'manual', ?int $recordedBy = null): Reading
    {
        $readingType = $this->tracking_unit;

        $reading = $this->readings()->create([
            'reading_value' => $value,
            'reading_type' => $readingType,
            'source' => $source,
            'recorded_by' => $recordedBy,
            'recorded_at' => Carbon::now(),
        ]);

        // Update current reading on vehicle
        if ($readingType === 'hours') {
            $this->current_hours = $value;
        } else {
            $this->current_km = $value;
        }
        $this->last_reading_at = Carbon::now();
        $this->save();

        return $reading;
    }

    /**
     * Transfer vehicle to a new site.
     */
    public function transferToSite(string $siteId, int $assignedBy, ?string $notes = null): SiteAssignment
    {
        // End current assignment
        $currentAssignment = $this->currentSiteAssignment;
        if ($currentAssignment) {
            $currentAssignment->update(['ended_at' => Carbon::today()]);
        }

        // Create new assignment
        $newAssignment = $this->siteAssignments()->create([
            'site_id' => $siteId,
            'assigned_at' => Carbon::today(),
            'assigned_by' => $assignedBy,
            'notes' => $notes,
        ]);

        // Update primary site
        $this->update(['primary_site_id' => $siteId]);

        return $newAssignment;
    }

    /**
     * Record a completed service.
     */
    public function recordService(string $serviceType, int $readingAtService, ?\DateTimeInterface $serviceDate = null): void
    {
        $serviceDate = $serviceDate ?? Carbon::today();

        if ($serviceType === 'major') {
            // Major service resets both counters
            $this->last_major_service_reading = $readingAtService;
            $this->last_major_service_date = $serviceDate;
            $this->last_minor_service_reading = $readingAtService;
            $this->last_minor_service_date = $serviceDate;
        } else {
            $this->last_minor_service_reading = $readingAtService;
            $this->last_minor_service_date = $serviceDate;
        }

        $this->save();
    }
}
