<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class ChecklistCategory extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'name',
        'display_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    /**
     * Checklist items in this category.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ChecklistItem::class, 'category_id')
            ->orderBy('display_order');
    }

    /**
     * Active checklist items in this category.
     */
    public function activeItems(): HasMany
    {
        return $this->hasMany(ChecklistItem::class, 'category_id')
            ->where('is_active', true)
            ->orderBy('display_order');
    }

    /**
     * Scope to order by display order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order');
    }
}
