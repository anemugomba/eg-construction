<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class ChecklistItem extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'category_id',
        'name',
        'description',
        'is_quarterly_only',
        'photo_required_on_repair',
        'photo_required_on_replace',
        'display_order',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_quarterly_only' => 'boolean',
        'photo_required_on_repair' => 'boolean',
        'photo_required_on_replace' => 'boolean',
        'display_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * The category this item belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ChecklistCategory::class, 'category_id');
    }

    /**
     * Machine types this item applies to.
     */
    public function machineTypes(): BelongsToMany
    {
        return $this->belongsToMany(MachineType::class, 'machine_type_checklist_items');
    }

    /**
     * Inspection templates this item belongs to.
     */
    public function inspectionTemplates(): BelongsToMany
    {
        return $this->belongsToMany(InspectionTemplate::class, 'inspection_template_items', 'checklist_item_id', 'template_id')
            ->withPivot('is_required');
    }

    /**
     * Check if photo is required for a given rating.
     */
    public function requiresPhotoForRating(string $rating): bool
    {
        return match ($rating) {
            'repair' => $this->photo_required_on_repair,
            'replace' => $this->photo_required_on_replace,
            default => false,
        };
    }

    /**
     * Scope to filter active items only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter quarterly-only items.
     */
    public function scopeQuarterlyOnly(Builder $query): Builder
    {
        return $query->where('is_quarterly_only', true);
    }

    /**
     * Scope to filter monthly items (not quarterly-only).
     */
    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('is_quarterly_only', false);
    }

    /**
     * Scope to order by display order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order');
    }

    /**
     * Scope for items applicable to a machine type.
     */
    public function scopeForMachineType(Builder $query, string $machineTypeId): Builder
    {
        return $query->whereHas('machineTypes', function ($q) use ($machineTypeId) {
            $q->where('machine_types.id', $machineTypeId);
        });
    }
}
