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

class OilAnalysis extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $table = 'oil_analyses';

    protected $fillable = [
        'vehicle_id',
        'analysis_date',
        'reading_at_analysis',
        'lab_reference',
        'results_json',
        'iron_ppm',
        'silicon_ppm',
        'viscosity_40c',
        'viscosity_100c',
        'interpretation',
        'recommendations',
        'next_analysis_due',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'analysis_date' => 'date',
        'reading_at_analysis' => 'integer',
        'results_json' => 'array',
        'iron_ppm' => 'integer',
        'silicon_ppm' => 'integer',
        'viscosity_40c' => 'decimal:2',
        'viscosity_100c' => 'decimal:2',
        'next_analysis_due' => 'date',
    ];

    /**
     * The vehicle this analysis belongs to.
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Check if next analysis is due.
     */
    protected function isNextAnalysisDue(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->next_analysis_due && $this->next_analysis_due <= Carbon::today()
        );
    }

    /**
     * Days until next analysis is due.
     */
    protected function daysUntilNextAnalysis(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->next_analysis_due
                ? Carbon::today()->diffInDays($this->next_analysis_due, false)
                : null
        );
    }

    /**
     * Get a specific metric from results_json.
     */
    public function getMetric(string $key, $default = null)
    {
        return data_get($this->results_json, $key, $default);
    }

    /**
     * Set a specific metric in results_json.
     */
    public function setMetric(string $key, $value): self
    {
        $results = $this->results_json ?? [];
        data_set($results, $key, $value);
        $this->results_json = $results;

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
     * Scope for analyses due soon.
     */
    public function scopeNextAnalysisDueWithin(Builder $query, int $days): Builder
    {
        return $query->whereNotNull('next_analysis_due')
            ->where('next_analysis_due', '<=', Carbon::today()->addDays($days));
    }

    /**
     * Scope for overdue analyses.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('next_analysis_due')
            ->where('next_analysis_due', '<', Carbon::today());
    }

    /**
     * Scope for analyses in date range.
     */
    public function scopeInDateRange(Builder $query, $from, $to): Builder
    {
        return $query->whereBetween('analysis_date', [$from, $to]);
    }

    /**
     * Get latest analysis first.
     */
    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderBy('analysis_date', 'desc');
    }
}
