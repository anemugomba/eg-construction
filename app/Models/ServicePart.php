<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ServicePart extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'service_id',
        'part_catalog_id',
        'name',
        'quantity',
        'unit_cost',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_cost' => 'decimal:2',
    ];

    protected $appends = ['total_cost'];

    /**
     * The service this part belongs to.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * The catalog entry if linked.
     */
    public function catalogEntry(): BelongsTo
    {
        return $this->belongsTo(PartsCatalog::class, 'part_catalog_id');
    }

    /**
     * Calculate total cost for this line item.
     */
    protected function totalCost(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->unit_cost ? $this->unit_cost * $this->quantity : null
        );
    }
}
