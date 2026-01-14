<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Component extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'name',
        'category',
        'is_system_defined',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_system_defined' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Watch list items for this component.
     */
    public function watchListItems(): HasMany
    {
        return $this->hasMany(WatchListItem::class);
    }

    /**
     * Component replacements tracking.
     */
    public function replacements(): HasMany
    {
        return $this->hasMany(ComponentReplacement::class);
    }

    /**
     * Job card components referencing this component.
     */
    public function jobCardComponents(): HasMany
    {
        return $this->hasMany(JobCardComponent::class);
    }

    /**
     * Scope to filter active components only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter system-defined components.
     */
    public function scopeSystemDefined(Builder $query): Builder
    {
        return $query->where('is_system_defined', true);
    }

    /**
     * Scope to filter user-created components.
     */
    public function scopeUserCreated(Builder $query): Builder
    {
        return $query->where('is_system_defined', false);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeInCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to search by name.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'like', "%{$search}%");
    }
}
