<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class InspectionTemplate extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'name',
        'description',
        'frequency',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Checklist items in this template.
     */
    public function checklistItems(): BelongsToMany
    {
        return $this->belongsToMany(ChecklistItem::class, 'inspection_template_items', 'template_id', 'checklist_item_id')
            ->withPivot('is_required');
    }

    /**
     * Required checklist items in this template.
     */
    public function requiredChecklistItems(): BelongsToMany
    {
        return $this->belongsToMany(ChecklistItem::class, 'inspection_template_items', 'template_id', 'checklist_item_id')
            ->wherePivot('is_required', true);
    }

    /**
     * Inspections using this template.
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class, 'template_id');
    }

    /**
     * Check if this is a monthly template.
     */
    public function isMonthly(): bool
    {
        return $this->frequency === 'monthly';
    }

    /**
     * Check if this is a quarterly template.
     */
    public function isQuarterly(): bool
    {
        return $this->frequency === 'quarterly';
    }

    /**
     * Scope to filter active templates only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by frequency.
     */
    public function scopeByFrequency(Builder $query, string $frequency): Builder
    {
        return $query->where('frequency', $frequency);
    }

    /**
     * Get the total number of checklist items.
     */
    public function getItemCountAttribute(): int
    {
        return $this->checklistItems()->count();
    }

    /**
     * Get the number of required checklist items.
     */
    public function getRequiredItemCountAttribute(): int
    {
        return $this->requiredChecklistItems()->count();
    }
}
