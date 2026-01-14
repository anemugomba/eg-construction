<?php

namespace App\Models;

use App\Models\Concerns\HasAuditFields;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;

class InspectionResult extends Model
{
    use HasFactory, HasUuids, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'inspection_id',
        'checklist_item_id',
        'rating',
        'notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The inspection this result belongs to.
     */
    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    /**
     * The checklist item this result is for.
     */
    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class);
    }

    /**
     * Watch list item created from this result.
     */
    public function watchListItem(): HasOne
    {
        return $this->hasOne(WatchListItem::class);
    }

    /**
     * Attachments for this result (photos).
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Check if this item needs attention.
     */
    public function needsAttention(): bool
    {
        return in_array($this->rating, ['repair', 'replace']);
    }

    /**
     * Check if photo is required for this rating.
     */
    public function requiresPhoto(): bool
    {
        return $this->checklistItem?->requiresPhotoForRating($this->rating) ?? false;
    }

    /**
     * Check if rating is good.
     */
    public function isGood(): bool
    {
        return $this->rating === 'good';
    }

    /**
     * Check if rating is service.
     */
    public function needsService(): bool
    {
        return $this->rating === 'service';
    }

    /**
     * Check if rating is repair.
     */
    public function needsRepair(): bool
    {
        return $this->rating === 'repair';
    }

    /**
     * Check if rating is replace.
     */
    public function needsReplacement(): bool
    {
        return $this->rating === 'replace';
    }

    /**
     * Scope for results with a specific rating.
     */
    public function scopeWithRating(Builder $query, string $rating): Builder
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope for results needing attention.
     */
    public function scopeNeedingAttention(Builder $query): Builder
    {
        return $query->whereIn('rating', ['repair', 'replace']);
    }
}
