<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class PartsCatalog extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $table = 'parts_catalog';

    protected $fillable = [
        'sku',
        'name',
        'category',
        'unit_cost',
        'supplier',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Service parts using this catalog entry.
     */
    public function serviceParts(): HasMany
    {
        return $this->hasMany(ServicePart::class, 'part_catalog_id');
    }

    /**
     * Job card parts using this catalog entry.
     */
    public function jobCardParts(): HasMany
    {
        return $this->hasMany(JobCardPart::class, 'part_catalog_id');
    }

    /**
     * Scope for active parts only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeInCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope to filter by supplier.
     */
    public function scopeFromSupplier(Builder $query, string $supplier): Builder
    {
        return $query->where('supplier', $supplier);
    }

    /**
     * Scope to search by name or SKU.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('sku', 'like', "%{$search}%");
        });
    }
}
