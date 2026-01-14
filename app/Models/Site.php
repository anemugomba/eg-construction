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

class Site extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'name',
        'location',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Users assigned to this site.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_sites')
            ->withPivot('created_at');
    }

    /**
     * Machines currently assigned to this site.
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'primary_site_id');
    }

    /**
     * All site assignments (machine location history).
     */
    public function siteAssignments(): HasMany
    {
        return $this->hasMany(SiteAssignment::class);
    }

    /**
     * Active site assignments (machines currently at this site).
     */
    public function activeSiteAssignments(): HasMany
    {
        return $this->hasMany(SiteAssignment::class)
            ->whereNull('ended_at');
    }

    /**
     * Scope to filter active sites only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to search by name or location.
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('location', 'like', "%{$search}%");
        });
    }
}
