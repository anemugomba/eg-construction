<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobCardComponent extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'job_card_id',
        'component_id',
        'component_description',
        'action_taken',
        'reading_at_action',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'reading_at_action' => 'integer',
    ];

    /**
     * The job card this component belongs to.
     */
    public function jobCard(): BelongsTo
    {
        return $this->belongsTo(JobCard::class);
    }

    /**
     * The component from catalog if linked.
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * Check if component was replaced.
     */
    public function wasReplaced(): bool
    {
        return $this->action_taken === 'replaced';
    }

    /**
     * Check if component was repaired.
     */
    public function wasRepaired(): bool
    {
        return $this->action_taken === 'repaired';
    }
}
